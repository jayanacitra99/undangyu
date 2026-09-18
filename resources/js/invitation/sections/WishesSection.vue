<script setup>
/*
| The guestbook (M6.4, M6.5, 29.3).
|
| A form and a feed. The feed is the server's cached first page, paginated for
| anyone who scrolls; the form posts and prepends its own message when the
| invitation publishes immediately, or says it is waiting when the client
| moderates.
|
| Every message is rendered with `{{ }}`, which escapes. There is no v-html in
| this file and there must never be: this is the one place on a wedding
| invitation where a stranger's text is printed.
*/
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    heading: { type: String, default: '' },
    storeUrl: { type: String, required: true },
    feedUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    token: { type: String, default: null },
    guestName: { type: String, default: null },
    maxLength: { type: Number, default: 500 },
});

const wishes = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const state = ref('idle');
const errors = ref({});

const form = ref({
    name: props.guestName ?? '',
    message: '',
    // The honeypot. Hidden from people, irresistible to the sort of bot that
    // fills every field it finds.
    website: '',
});

const hasMore = computed(() => meta.value.current_page < meta.value.last_page);
const remaining = computed(() => props.maxLength - form.value.message.length);

async function request(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': props.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        ...options,
        body: options.body ? JSON.stringify(options.body) : null,
    });

    const payload = await response.json().catch(() => null);

    return { ok: response.ok, status: response.status, payload };
}

async function load(page = 1) {
    loading.value = true;

    const { ok, payload } = await request(`${props.feedUrl}?page=${page}`);

    loading.value = false;

    if (!ok || !payload) {
        return;
    }

    // Append on "load more", replace on the first read.
    wishes.value = page === 1 ? payload.data : [...wishes.value, ...payload.data];
    meta.value = payload.meta;
}

onMounted(() => load(1));

async function submit() {
    state.value = 'saving';
    errors.value = {};

    const { ok, status, payload } = await request(props.storeUrl, {
        method: 'POST',
        body: { ...form.value, token: props.token },
    });

    if (ok) {
        // A published wish joins the feed immediately; a held one does not,
        // and the guest is told why rather than left looking for it.
        if (payload?.data) {
            wishes.value = [payload.data, ...wishes.value];
            meta.value = { ...meta.value, total: meta.value.total + 1 };
            state.value = 'published';
        } else {
            state.value = 'held';
        }

        form.value.message = '';

        return;
    }

    if (status === 422) {
        errors.value = payload?.errors ?? {};
    }

    state.value = status === 429 ? 'throttled' : 'failed';
}

const errorFor = (field) => errors.value[field]?.[0] ?? null;

const formatDate = (value) => new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
}).format(new Date(value));
</script>

<template>
    <section class="mx-auto max-w-md px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <p class="reveal mt-3 text-center font-body text-sm opacity-75">
            Tinggalkan ucapan dan doa untuk kami.
        </p>

        <form class="reveal mt-8 space-y-3" @submit.prevent="submit">
            <fieldset :disabled="state === 'saving'" class="space-y-3">
                <div>
                    <label for="wish-name" class="font-body text-xs uppercase tracking-widest opacity-70">Nama</label>
                    <input
                        id="wish-name"
                        v-model="form.name"
                        type="text"
                        maxlength="190"
                        required
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    >
                    <p v-if="errorFor('name')" class="mt-1 font-body text-xs text-red-700">{{ errorFor('name') }}</p>
                </div>

                <div>
                    <label for="wish-message" class="font-body text-xs uppercase tracking-widest opacity-70">
                        Ucapan
                    </label>
                    <textarea
                        id="wish-message"
                        v-model="form.message"
                        rows="4"
                        :maxlength="maxLength"
                        required
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    ></textarea>
                    <div class="flex justify-between font-body text-xs opacity-60">
                        <span v-if="errorFor('message')" class="text-red-700">{{ errorFor('message') }}</span>
                        <span v-else></span>
                        <span>{{ remaining }}</span>
                    </div>
                </div>

                <!-- The honeypot: off-screen rather than display:none, because
                     a bot that reads computed styles skips the hidden ones. -->
                <div class="absolute left-[-9999px]" aria-hidden="true">
                    <label for="wish-website">Website</label>
                    <input id="wish-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <button
                    type="submit"
                    class="w-full rounded-full bg-[var(--primary)] py-3 font-body text-sm font-medium text-white"
                >
                    {{ state === 'saving' ? 'Mengirim…' : 'Kirim ucapan' }}
                </button>
            </fieldset>

            <p class="text-center font-body text-xs opacity-70" role="status">
                <span v-if="state === 'published'" class="text-[var(--primary)]">
                    Terima kasih! Ucapan Anda sudah tampil.
                </span>
                <span v-else-if="state === 'held'">
                    Terima kasih! Ucapan Anda menunggu persetujuan mempelai.
                </span>
                <span v-else-if="state === 'throttled'" class="text-red-700">
                    Terlalu banyak kiriman. Coba lagi sebentar lagi.
                </span>
                <span v-else-if="state === 'failed'" class="text-red-700">
                    Gagal mengirim. Coba lagi.
                </span>
            </p>
        </form>

        <ul v-if="wishes.length" class="reveal mt-10 space-y-4">
            <li
                v-for="wish in wishes"
                :key="wish.id"
                class="rounded-2xl border border-[var(--accent)]/30 p-4"
                :class="{ 'border-[var(--primary)]/60': wish.is_pinned }"
            >
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-body text-sm font-medium">{{ wish.name }}</span>
                    <span class="font-body text-xs opacity-60">{{ formatDate(wish.created_at) }}</span>
                </div>
                <!-- Escaped. Never v-html. -->
                <p class="mt-1 whitespace-pre-line font-body text-sm opacity-85">{{ wish.message }}</p>
            </li>
        </ul>

        <p v-else-if="!loading" class="reveal mt-10 text-center font-body text-sm opacity-60">
            Jadilah yang pertama mengirim ucapan.
        </p>

        <div v-if="hasMore" class="mt-6 text-center">
            <button
                type="button"
                class="rounded-full border border-[var(--primary)] px-6 py-2 font-body text-sm"
                :disabled="loading"
                @click="load(meta.current_page + 1)"
            >
                {{ loading ? 'Memuat…' : 'Lihat ucapan lainnya' }}
            </button>
        </div>
    </section>
</template>
