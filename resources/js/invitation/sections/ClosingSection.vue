<script setup>
/*
| The closing passage (M4.9-ish: the quote section's role at the end).
*/
defineProps({
    heading: { type: String, default: '' },
    body: { type: String, default: null },
    persons: { type: Array, default: () => [] },
    cover: { type: Object, default: null },
});
</script>

<template>
    <section class="relative overflow-hidden px-6 py-20 text-center">
        <img
            v-if="cover"
            :src="cover.medium"
            alt=""
            aria-hidden="true"
            loading="lazy"
            decoding="async"
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div class="absolute inset-0" style="background: color-mix(in srgb, var(--ink) 70%, transparent)"></div>

        <div class="relative text-white">
            <h2 v-if="heading" class="reveal font-heading text-2xl">{{ heading }}</h2>

            <p class="reveal mx-auto mt-4 max-w-md font-body text-sm leading-relaxed opacity-90">
                {{ body ?? 'Merupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.' }}
            </p>

            <p v-if="persons.length" class="reveal mt-8 font-heading text-2xl">
                {{ persons.map((person) => person.display_name).join(' & ') }}
            </p>
        </div>
    </section>
</template>
