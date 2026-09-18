<script setup>
/*
| The cover (22.3, M4.12).
|
| Full-screen, the guest's name when we know it, and one button that opens the
| invitation. Nothing scrolls behind it and no audio plays before that tap.
|
| `guestName` is null until Session 27 resolves the `?to=` token — the prop
| exists now so the design has a place for it rather than being retrofitted.
*/
const props = defineProps({
    invitation: { type: Object, required: true },
    eventType: { type: Object, required: true },
    persons: { type: Array, default: () => [] },
    firstEvent: { type: Object, default: null },
    cover: { type: Object, default: null },
    guestName: { type: String, default: null },
});

defineEmits(['open']);

const names = props.persons.map((person) => person.display_name).join(' & ');

const eventDate = props.firstEvent
    ? new Intl.DateTimeFormat(props.invitation.language === 'en' ? 'en-GB' : 'id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(props.firstEvent.start_at))
    : null;
</script>

<template>
    <section class="relative flex min-h-[100svh] flex-col items-center justify-center overflow-hidden px-6 text-center">
        <!-- The cover photo is the only above-the-fold image, so it is the one
             thing not lazy-loaded: it is the LCP element. -->
        <img
            v-if="cover"
            :src="cover.full"
            :srcset="`${cover.medium} 1000w, ${cover.full} 2000w`"
            sizes="100vw"
            :alt="invitation.title"
            fetchpriority="high"
            decoding="async"
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div
            class="absolute inset-0"
            style="background: linear-gradient(to bottom, color-mix(in srgb, var(--ink) 35%, transparent), color-mix(in srgb, var(--ink) 65%, transparent))"
        ></div>

        <div class="relative text-white">
            <p class="font-body text-xs uppercase tracking-[0.35em] opacity-90">
                {{ eventType.name }}
            </p>

            <h1 class="mt-6 font-heading text-4xl leading-tight sm:text-6xl">
                {{ names || invitation.title }}
            </h1>

            <p v-if="eventDate" class="mt-5 font-body text-sm tracking-[0.2em] opacity-90">
                {{ eventDate }}
            </p>

            <div class="mt-12">
                <p v-if="guestName" class="font-body text-sm opacity-90">
                    Kepada
                    <span class="font-medium">{{ guestName }}</span>
                </p>
                <p v-else class="font-body text-sm opacity-80">Kepada Bapak/Ibu/Saudara/i</p>

                <button
                    type="button"
                    class="mt-4 rounded-full bg-white/95 px-8 py-3 font-body text-sm font-medium tracking-wide text-[var(--ink)] shadow-lg transition hover:bg-white"
                    @click="$emit('open')"
                >
                    Buka Undangan
                </button>
            </div>
        </div>
    </section>
</template>
