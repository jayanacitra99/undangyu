<script setup>
/*
| RSVP (M6.1, M6.3, M6.9, 28.3).
|
| One form, three audiences: a named guest on a personalised link, the same
| guest coming back to change their answer, and somebody who was forwarded the
| public link and has no token at all.
|
| The answer posts straight to the server and the guest is told it landed.
| Anything less — an optimistic tick, a queued write they cannot see — is how
| one guest submits four times.
*/
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    heading: { type: String, default: '' },
    storeUrl: { type: String, required: true },
    lookupUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    token: { type: String, default: null },
    guestName: { type: String, default: null },
    maxPax: { type: Number, default: 5 },
    events: { type: Array, default: () => [] },
});

const form = ref({
    name: props.guestName ?? '',
    phone: '',
    attendance: 'yes',
    pax: 1,
    notes: '',
    invitation_event_id: props.events.length === 1 ? props.events[0].id : null,
});

const state = ref('idle');
const errors = ref({});
const saved = ref(null);

// Not coming means nobody is coming, so the number stops being a question.
const asksForPax = computed(() => form.value.attendance !== 'no');

const paxOptions = computed(
    () => Array.from({ length: Math.max(1, props.maxPax) }, (_, index) => index + 1),
);

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

/*
| A guest who already answered sees their own answer, not an empty form: they
| came back to change it (M6.3).
*/
onMounted(async () => {
    if (!props.token) {
        return;
    }

    const { ok, payload } = await request(`${props.lookupUrl}?to=${encodeURIComponent(props.token)}`);
    const existing = ok ? (payload?.data?.[0] ?? null) : null;

    if (existing) {
        saved.value = existing;
        form.value = {
            name: existing.name,
            phone: existing.phone ?? '',
            attendance: existing.attendance,
            pax: existing.pax || 1,
            notes: existing.notes ?? '',
            invitation_event_id: existing.invitation_event_id,
        };
        state.value = 'done';
    }
});

async function submit() {
    state.value = 'saving';
    errors.value = {};

    const { ok, status, payload } = await request(props.storeUrl, {
        method: 'POST',
        body: {
            ...form.value,
            pax: asksForPax.value ? form.value.pax : 0,
            token: props.token,
        },
    });

    if (ok) {
        saved.value = payload.data;
        state.value = 'done';

        return;
    }

    if (status === 422) {
        errors.value = payload?.errors ?? {};
    }

    // 429 is the rate limiter. Said plainly, because a guest who is told
    // "error" taps again immediately and a guest who is told "wait a moment"
    // waits a moment.
    state.value = status === 429 ? 'throttled' : 'failed';
}

function edit() {
    state.value = 'idle';
}

const errorFor = (field) => errors.value[field]?.[0] ?? null;
</script>

<template>
    <section class="mx-auto max-w-md px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <p class="reveal mt-3 text-center font-body text-sm opacity-75">
            Mohon konfirmasi kehadiran Anda.
        </p>

        <!-- The answer, once it is in. The guest can still change it. -->
        <div v-if="state === 'done'" class="reveal mt-8 rounded-2xl border border-[var(--primary)]/30 p-6 text-center">
            <p class="font-body text-sm">
                Terima kasih, <span class="font-medium">{{ saved?.name }}</span>.
            </p>
            <p class="mt-2 font-body text-sm opacity-80">
                Jawaban Anda: <span class="font-medium">{{ saved?.attendance_label }}</span>
                <template v-if="saved?.pax > 0"> · {{ saved.pax }} orang</template>
            </p>
            <button
                type="button"
                class="mt-4 rounded-full border border-[var(--primary)] px-6 py-2 font-body text-sm"
                @click="edit"
            >
                Ubah jawaban
            </button>
        </div>

        <form v-else class="reveal mt-8 space-y-4" aria-describedby="rsvp-status" @submit.prevent="submit">
            <fieldset :disabled="state === 'saving'" class="space-y-4">
                <div>
                    <label for="rsvp-name" class="font-body text-xs uppercase tracking-widest opacity-70">Nama</label>
                    <input
                        id="rsvp-name"
                        v-model="form.name"
                        type="text"
                        maxlength="190"
                        required
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    >
                    <p v-if="errorFor('name')" class="mt-1 font-body text-xs text-red-700">{{ errorFor('name') }}</p>
                </div>

                <div>
                    <label for="rsvp-phone" class="font-body text-xs uppercase tracking-widest opacity-70">
                        WhatsApp <span class="opacity-60">(opsional)</span>
                    </label>
                    <input
                        id="rsvp-phone"
                        v-model="form.phone"
                        type="tel"
                        maxlength="30"
                        placeholder="0812…"
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    >
                </div>

                <div>
                    <span class="font-body text-xs uppercase tracking-widest opacity-70">Kehadiran</span>
                    <div class="mt-2 flex gap-2">
                        <button
                            v-for="option in [
                                { value: 'yes', label: 'Hadir' },
                                { value: 'maybe', label: 'Masih ragu' },
                                { value: 'no', label: 'Tidak hadir' },
                            ]"
                            :key="option.value"
                            type="button"
                            class="flex-1 rounded-lg border py-2 font-body text-sm"
                            :class="form.attendance === option.value
                                ? 'border-[var(--primary)] bg-[var(--primary)]/10 font-medium'
                                : 'border-[var(--accent)]/40'"
                            :aria-pressed="form.attendance === option.value"
                            @click="form.attendance = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div v-if="asksForPax">
                    <label for="rsvp-pax" class="font-body text-xs uppercase tracking-widest opacity-70">
                        Jumlah orang
                    </label>
                    <select
                        id="rsvp-pax"
                        v-model.number="form.pax"
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    >
                        <option v-for="value in paxOptions" :key="value" :value="value">{{ value }}</option>
                    </select>
                    <p v-if="errorFor('pax')" class="mt-1 font-body text-xs text-red-700">{{ errorFor('pax') }}</p>
                </div>

                <!-- Only when there is a choice to make. One session needs no
                     question, and asking it anyway is a field a guest has to
                     read before they can answer the one that matters. -->
                <div v-if="events.length > 1">
                    <label for="rsvp-event" class="font-body text-xs uppercase tracking-widest opacity-70">Acara</label>
                    <select
                        id="rsvp-event"
                        v-model="form.invitation_event_id"
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    >
                        <option :value="null">Semua acara</option>
                        <option v-for="event in events" :key="event.id" :value="event.id">{{ event.name }}</option>
                    </select>
                </div>

                <div>
                    <label for="rsvp-notes" class="font-body text-xs uppercase tracking-widest opacity-70">
                        Pesan <span class="opacity-60">(opsional)</span>
                    </label>
                    <textarea
                        id="rsvp-notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="500"
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-full bg-[var(--primary)] py-3 font-body text-sm font-medium text-white"
                >
                    {{ state === 'saving' ? 'Mengirim…' : 'Kirim konfirmasi' }}
                </button>
            </fieldset>

            <p id="rsvp-status" class="text-center font-body text-xs opacity-70">
                <span v-if="state === 'throttled'" class="text-red-700">
                    Terlalu banyak kiriman. Coba lagi sebentar lagi.
                </span>
                <span v-else-if="state === 'failed'" class="text-red-700">
                    Gagal mengirim. Periksa koneksi Anda dan coba lagi.
                </span>
                <span v-else>Jawaban Anda bisa diubah kapan saja lewat tautan ini.</span>
            </p>
        </form>
    </section>
</template>
