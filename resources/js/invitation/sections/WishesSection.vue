<script setup>
/*
| The guestbook (M6.4, M6.5).
|
| Same position as RsvpSection: the design is here, the write path is Session
| 29. Existing wishes are not fetched either — there is no endpoint to fetch
| them from yet, and inventing sample messages on a real couple's invitation
| would be worse than an empty state.
*/
defineProps({
    heading: { type: String, default: '' },
});
</script>

<template>
    <section class="mx-auto max-w-md px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <p class="reveal mt-3 text-center font-body text-sm opacity-75">
            Tinggalkan ucapan dan doa untuk kami.
        </p>

        <div class="reveal mt-8 rounded-2xl border border-dashed border-[var(--accent)]/50 p-6 text-center">
            <p class="font-body text-sm opacity-75">Buku tamu akan dibuka sebentar lagi.</p>
        </div>
    </section>
</template>
