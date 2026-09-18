<script setup>
/*
| "Sekar Ayu" — the first template (22.2).
|
| Mobile-first at a 375px baseline: every size here is chosen for a phone held
| one-handed, and the wider breakpoints only relax the columns. That is the
| device the invitation is actually opened on, in a WhatsApp in-app browser,
| usually on mobile data.
|
| The template composes the shared sections and owns the styling: the theme
| tokens come in as CSS custom properties, the ornament and type choices are
| this template's own. A second template reuses the sections and looks nothing
| like this one.
|
| Section order follows the client's own `sections` ordering from the builder,
| because that is what the SectionsManager is for — this file decides how each
| one looks, not when it appears.
*/
import { computed, nextTick, ref, watch } from 'vue';
import CoverSection from '@/invitation/sections/CoverSection.vue';
import PersonsSection from '@/invitation/sections/PersonsSection.vue';
import EventsSection from '@/invitation/sections/EventsSection.vue';
import CountdownSection from '@/invitation/sections/CountdownSection.vue';
import StorySection from '@/invitation/sections/StorySection.vue';
import GallerySection from '@/invitation/sections/GallerySection.vue';
import GiftsSection from '@/invitation/sections/GiftsSection.vue';
import RsvpSection from '@/invitation/sections/RsvpSection.vue';
import WishesSection from '@/invitation/sections/WishesSection.vue';
import ClosingSection from '@/invitation/sections/ClosingSection.vue';
import { useReveal } from '@/composables/useReveal';

const props = defineProps({
    payload: { type: Object, required: true },
    isOpen: { type: Boolean, default: false },
    guestName: { type: String, default: null },
    guestToken: { type: String, default: null },
    guestMaxPax: { type: Number, default: null },
    rsvpStoreUrl: { type: String, default: '' },
    rsvpLookupUrl: { type: String, default: '' },
    csrfToken: { type: String, default: '' },
});

defineEmits(['open']);

const body = ref(null);
const { refresh: refreshReveal } = useReveal(body);

/*
| The body does not exist until the cover is opened, so the observer has to be
| pointed at it once it does. Without this every section stays at opacity 0 and
| the invitation is a blank page under the header.
*/
watch(
    () => props.isOpen,
    async (isOpen) => {
        if (!isOpen) {
            return;
        }

        await nextTick();
        refreshReveal();
    },
);

const invitation = computed(() => props.payload.invitation);
const settings = computed(() => props.payload.settings);
const theme = computed(() => props.payload.theme ?? {});

const cover = computed(
    () => props.payload.media.find((item) => item.is_cover && item.type === 'image') ?? null,
);

const firstEvent = computed(() => props.payload.events[0] ?? null);

const sections = computed(() => props.payload.sections);
const headingFor = (key) => sections.value.find((section) => section.key === key)?.heading ?? '';
const has = (key) => sections.value.some((section) => section.key === key);

const customSections = computed(() =>
    sections.value.filter((section) => section.key === 'custom' && section.body),
);

/*
| Theme tokens as custom properties, with the template's own fallbacks. The
| schema declares these keys (22.7); a client who has changed nothing still
| gets the defaults the template shipped with.
*/
const tokens = computed(() => ({
    '--primary': theme.value.colors?.primary ?? '#8B7355',
    '--accent': theme.value.colors?.secondary ?? '#D4C5B0',
    '--ink': theme.value.colors?.text ?? '#3A3A3A',
    '--surface': theme.value.colors?.surface ?? '#FBF8F4',
    '--font-heading': `'${theme.value.fonts?.heading ?? 'Playfair Display'}', Georgia, serif`,
    '--font-body': `'${theme.value.fonts?.body ?? 'Lato'}', system-ui, sans-serif`,
}));

const showCountdown = computed(
    () => (theme.value.options?.show_countdown ?? true) && settings.value.countdown_enabled && firstEvent.value !== null,
);
</script>

<template>
    <div :style="tokens" class="sekar-ayu min-h-[100svh]" style="background: var(--surface); color: var(--ink)">
        <CoverSection
            v-if="!isOpen"
            :invitation="invitation"
            :event-type="payload.event_type"
            :persons="payload.persons"
            :first-event="firstEvent"
            :cover="cover"
            :guest-name="guestName"
            @open="$emit('open')"
        />

        <div v-else ref="body">
            <header class="relative overflow-hidden px-6 py-20 text-center">
                <img
                    v-if="cover"
                    :src="cover.medium"
                    alt=""
                    aria-hidden="true"
                    class="absolute inset-0 h-full w-full object-cover opacity-30"
                >
                <div class="relative">
                    <p class="font-body text-xs uppercase tracking-[0.35em] opacity-70">
                        {{ payload.event_type.name }}
                    </p>
                    <h1 class="mt-4 font-heading text-4xl">
                        {{ payload.persons.map((person) => person.display_name).join(' & ') || invitation.title }}
                    </h1>
                </div>
            </header>

            <CountdownSection v-if="showCountdown" :start-at="firstEvent.start_at" />

            <PersonsSection
                v-if="has('persons') && payload.persons.length"
                :heading="headingFor('persons')"
                :persons="payload.persons"
            />

            <EventsSection
                v-if="has('events') && payload.events.length"
                :heading="headingFor('events')"
                :events="payload.events"
                :invitation="invitation"
            />

            <StorySection
                v-if="has('story') && payload.story.length"
                :heading="headingFor('story')"
                :story="payload.story"
                :invitation="invitation"
            />

            <GallerySection
                v-if="has('gallery') && payload.media.length"
                :heading="headingFor('gallery')"
                :media="payload.media"
            />

            <GiftsSection
                v-if="has('gifts') && settings.gift_enabled && payload.gifts.length"
                :heading="headingFor('gifts')"
                :gifts="payload.gifts"
            />

            <RsvpSection
                v-if="has('rsvp') && settings.rsvp_enabled"
                :heading="headingFor('rsvp')"
                :store-url="rsvpStoreUrl"
                :lookup-url="rsvpLookupUrl"
                :csrf-token="csrfToken"
                :token="guestToken"
                :guest-name="guestName"
                :max-pax="guestMaxPax ?? 5"
                :events="payload.events"
            />

            <WishesSection v-if="has('guestbook') && settings.guestbook_enabled" :heading="headingFor('guestbook')" />

            <section
                v-for="section in customSections"
                :key="section.heading"
                class="mx-auto max-w-2xl px-6 py-14 text-center"
            >
                <h2 class="reveal font-heading text-2xl">{{ section.heading }}</h2>
                <!-- Plain text: never v-html on client content. -->
                <p class="reveal mt-4 whitespace-pre-line font-body text-sm opacity-85">{{ section.body }}</p>
            </section>

            <ClosingSection
                :heading="headingFor('quote')"
                :body="sections.find((section) => section.key === 'quote')?.body ?? null"
                :persons="payload.persons"
                :cover="cover"
            />

            <footer class="px-6 pb-10 text-center font-body text-xs opacity-60">
                Undangan digital oleh Undangyu
            </footer>
        </div>
    </div>
</template>

<style scoped>
.sekar-ayu :deep(.font-heading) {
    font-family: var(--font-heading);
}

.sekar-ayu :deep(.font-body),
.sekar-ayu {
    font-family: var(--font-body);
}

/*
| The reveal animation (22.4). Opacity and transform only — neither reflows,
| so nothing below a revealing element moves. The elements hold their space
| from first paint.
*/
.sekar-ayu :deep(.reveal) {
    opacity: 0;
    transform: translateY(12px);
    transition:
        opacity 0.6s ease-out,
        transform 0.6s ease-out;
    will-change: opacity, transform;
}

.sekar-ayu :deep(.reveal-in) {
    opacity: 1;
    transform: none;
}

@media (prefers-reduced-motion: reduce) {
    .sekar-ayu :deep(.reveal) {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
</style>
