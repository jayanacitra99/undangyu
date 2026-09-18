<script setup>
/*
| The dated occasions, with the two actions a guest actually wants: directions
| and a calendar entry (M4.4, 22.5).
*/
import { computed } from 'vue';
import { downloadIcs, googleCalendarUrl, mapsUrl } from '@/composables/useCalendarLinks';

const props = defineProps({
    heading: { type: String, default: '' },
    events: { type: Array, required: true },
    invitation: { type: Object, required: true },
});

const locale = computed(() => (props.invitation.language === 'en' ? 'en-GB' : 'id-ID'));

const dateOf = (value) =>
    new Intl.DateTimeFormat(locale.value, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));

// The offset is in the string, so the clock time is read from it rather than
// converted into the guest's own zone.
const timeOf = (value) => value.slice(11, 16);

// Indonesia's three zones. A Makassar wedding reads WITA, not WIB — hardcoding
// one of them is wrong for two thirds of the country.
const ZONE_LABELS = {
    'Asia/Jakarta': 'WIB',
    'Asia/Makassar': 'WITA',
    'Asia/Jayapura': 'WIT',
};

const zoneLabel = computed(() => ZONE_LABELS[props.invitation.timezone] ?? '');
</script>

<template>
    <section class="mx-auto max-w-3xl px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <div class="mt-10 grid gap-6 sm:grid-cols-2">
            <article
                v-for="event in events"
                :key="event.name"
                class="reveal rounded-2xl border border-[var(--accent)]/30 bg-white/70 p-6 text-center backdrop-blur"
            >
                <h3 class="font-heading text-2xl">{{ event.name }}</h3>

                <p class="mt-3 font-body text-sm">{{ dateOf(event.start_at) }}</p>
                <p class="font-body text-sm">
                    {{ timeOf(event.start_at) }}<span v-if="event.end_at"> – {{ timeOf(event.end_at) }}</span>
                    <span class="opacity-70"> {{ zoneLabel }}</span>
                </p>

                <p class="mt-4 font-body font-medium">{{ event.venue_name }}</p>
                <p v-if="event.address" class="font-body text-sm opacity-75">{{ event.address }}</p>
                <p v-if="event.dress_code" class="mt-2 font-body text-xs uppercase tracking-widest opacity-70">
                    {{ event.dress_code }}
                </p>

                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <a
                        :href="mapsUrl(event)"
                        target="_blank"
                        rel="noopener"
                        class="rounded-full bg-[var(--primary)] px-4 py-2 font-body text-xs text-white"
                    >
                        Buka di Maps
                    </a>

                    <a
                        :href="googleCalendarUrl(event, invitation)"
                        target="_blank"
                        rel="noopener"
                        class="rounded-full border border-[var(--primary)] px-4 py-2 font-body text-xs"
                    >
                        Google Calendar
                    </a>

                    <button
                        type="button"
                        class="rounded-full border border-[var(--primary)] px-4 py-2 font-body text-xs"
                        @click="downloadIcs(event, invitation)"
                    >
                        Simpan (.ics)
                    </button>
                </div>

                <a
                    v-if="event.live_stream_url"
                    :href="event.live_stream_url"
                    target="_blank"
                    rel="noopener"
                    class="mt-3 inline-block font-body text-xs underline opacity-80"
                >
                    Ikuti live streaming
                </a>
            </article>
        </div>
    </section>
</template>
