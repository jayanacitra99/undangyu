<script setup>
/*
| The published invitation's Vue root (21.3).
|
| It draws the payload directly for now. Session 22 puts the real template
| components under `invitation/templates/{view_key}/` and this becomes the
| shell that picks one — which is why the payload is passed down whole rather
| than destructured here.
|
| Tailwind only. Bootstrap and AdminLTE never load on this page.
*/
import { computed } from 'vue';

const props = defineProps({
    payload: { type: Object, required: true },
});

const invitation = computed(() => props.payload.invitation);
const theme = computed(() => props.payload.theme ?? {});
const cover = computed(() => props.payload.media.find((item) => item.is_cover) ?? null);
const photos = computed(() => props.payload.media.filter((item) => item.type === 'image'));

const visible = (key) => props.payload.sections.some((section) => section.key === key);
const headingFor = (key) =>
    props.payload.sections.find((section) => section.key === key)?.heading ?? '';

/*
| Wall-clock strings from the server, already in the invitation's timezone —
| so they are formatted, never converted. Reading them as local time would put
| a Jakarta wedding an hour off for a guest in Makassar.
*/
const formatDate = (value) =>
    new Intl.DateTimeFormat(invitation.value.language === 'en' ? 'en-GB' : 'id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));

const formatTime = (value) => value.slice(11, 16);

const styles = computed(() => ({
    '--invitation-primary': theme.value.colors?.primary ?? '#8B7355',
    '--invitation-secondary': theme.value.colors?.secondary ?? '#D4C5B0',
    '--invitation-text': theme.value.colors?.text ?? '#3A3A3A',
}));
</script>

<template>
    <div :style="styles" class="min-h-full" style="color: var(--invitation-text)">
        <header class="relative flex min-h-[70vh] items-center justify-center overflow-hidden px-6 text-center">
            <img
                v-if="cover"
                :src="cover.full"
                :alt="invitation.title"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <div class="absolute inset-0" style="background: color-mix(in srgb, var(--invitation-secondary) 55%, transparent)"></div>

            <div class="relative">
                <p class="text-sm uppercase tracking-[0.3em]">{{ payload.event_type.name }}</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ invitation.title }}</h1>

                <p v-if="payload.persons.length" class="mt-4 text-lg">
                    {{ payload.persons.map((person) => person.display_name).join(' & ') }}
                </p>
            </div>
        </header>

        <section v-if="visible('persons') && payload.persons.length" class="mx-auto max-w-3xl px-6 py-14">
            <h2 class="text-center text-2xl font-semibold">{{ headingFor('persons') }}</h2>

            <div class="mt-8 grid gap-8 sm:grid-cols-2">
                <article v-for="person in payload.persons" :key="person.full_name" class="text-center">
                    <h3 class="text-xl font-medium">{{ person.full_name }}</h3>
                    <p v-if="person.child_order" class="mt-1 text-sm opacity-80">{{ person.child_order }}</p>
                    <p v-if="person.parents.father || person.parents.mother" class="mt-1 text-sm opacity-80">
                        {{ [person.parents.father, person.parents.mother].filter(Boolean).join(' & ') }}
                    </p>
                </article>
            </div>
        </section>

        <section v-if="visible('events') && payload.events.length" class="mx-auto max-w-3xl px-6 py-14">
            <h2 class="text-center text-2xl font-semibold">{{ headingFor('events') }}</h2>

            <div class="mt-8 grid gap-6 sm:grid-cols-2">
                <article
                    v-for="event in payload.events"
                    :key="event.name"
                    class="rounded-lg p-6 text-center"
                    style="background: color-mix(in srgb, var(--invitation-secondary) 30%, white)"
                >
                    <h3 class="text-lg font-medium">{{ event.name }}</h3>
                    <p class="mt-2">{{ formatDate(event.start_at) }}</p>
                    <p>{{ formatTime(event.start_at) }}<span v-if="event.end_at"> – {{ formatTime(event.end_at) }}</span></p>
                    <p class="mt-2 font-medium">{{ event.venue_name }}</p>
                    <p v-if="event.address" class="text-sm opacity-80">{{ event.address }}</p>
                    <a
                        v-if="event.maps_url"
                        :href="event.maps_url"
                        target="_blank"
                        rel="noopener"
                        class="mt-3 inline-block text-sm underline"
                    >
                        Lihat lokasi
                    </a>
                </article>
            </div>
        </section>

        <section v-if="visible('story') && payload.story.length" class="mx-auto max-w-2xl px-6 py-14">
            <h2 class="text-center text-2xl font-semibold">{{ headingFor('story') }}</h2>

            <ol class="mt-8 space-y-6">
                <li v-for="moment in payload.story" :key="moment.title">
                    <p v-if="moment.date" class="text-sm opacity-70">{{ formatDate(moment.date) }}</p>
                    <h3 class="text-lg font-medium">{{ moment.title }}</h3>
                    <p v-if="moment.description" class="mt-1 opacity-90">{{ moment.description }}</p>
                </li>
            </ol>
        </section>

        <section v-if="visible('gallery') && photos.length" class="mx-auto max-w-4xl px-6 py-14">
            <h2 class="text-center text-2xl font-semibold">{{ headingFor('gallery') }}</h2>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <img
                    v-for="photo in photos"
                    :key="photo.url"
                    :src="photo.medium"
                    :alt="photo.caption ?? invitation.title"
                    loading="lazy"
                    class="aspect-square w-full rounded-md object-cover"
                >
            </div>
        </section>

        <section v-if="visible('gifts') && payload.settings.gift_enabled && payload.gifts.length" class="mx-auto max-w-2xl px-6 py-14">
            <h2 class="text-center text-2xl font-semibold">{{ headingFor('gifts') }}</h2>

            <div class="mt-8 space-y-4">
                <article
                    v-for="gift in payload.gifts"
                    :key="`${gift.type}-${gift.account_number ?? gift.recipient_name}`"
                    class="rounded-lg p-5 text-center"
                    style="background: color-mix(in srgb, var(--invitation-secondary) 30%, white)"
                >
                    <p class="text-sm uppercase tracking-wide opacity-70">{{ gift.label }}</p>
                    <p v-if="gift.provider_name" class="mt-1 font-medium">{{ gift.provider_name }}</p>
                    <p v-if="gift.account_number" class="font-mono text-lg">{{ gift.account_number }}</p>
                    <p v-if="gift.account_name" class="text-sm opacity-80">{{ gift.account_name }}</p>
                    <p v-if="gift.recipient_name">{{ gift.recipient_name }}</p>
                    <p v-if="gift.address" class="text-sm opacity-80">{{ gift.address }}</p>
                </article>
            </div>
        </section>

        <section
            v-for="section in payload.sections.filter((item) => item.key === 'custom' && item.body)"
            :key="section.heading"
            class="mx-auto max-w-2xl px-6 py-14 text-center"
        >
            <h2 class="text-2xl font-semibold">{{ section.heading }}</h2>
            <!-- Plain text, deliberately: never v-html on client content. -->
            <p class="mt-4 whitespace-pre-line opacity-90">{{ section.body }}</p>
        </section>

        <footer class="px-6 py-10 text-center text-sm opacity-60">
            {{ invitation.title }}
        </footer>
    </div>
</template>
