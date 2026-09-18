<script setup>
/*
| Countdown to the first event (M4.x cover config).
|
| The four boxes are fixed-width and the digits are tabular, so the layout does
| not jump every time a second ticks over — the same "no layout shift" rule
| that governs the images.
*/
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    startAt: { type: String, required: true },
});

const remaining = ref(distance());

function distance() {
    return Math.max(0, new Date(props.startAt).getTime() - Date.now());
}

const timer = window.setInterval(() => {
    remaining.value = distance();
}, 1000);

onBeforeUnmount(() => window.clearInterval(timer));

const parts = computed(() => {
    const total = Math.floor(remaining.value / 1000);

    return [
        { label: 'Hari', value: Math.floor(total / 86400) },
        { label: 'Jam', value: Math.floor((total % 86400) / 3600) },
        { label: 'Menit', value: Math.floor((total % 3600) / 60) },
        { label: 'Detik', value: total % 60 },
    ];
});

const hasPassed = computed(() => remaining.value === 0);
</script>

<template>
    <section class="px-6 py-12 text-center">
        <p v-if="hasPassed" class="reveal font-heading text-2xl">Hari bahagia telah tiba</p>

        <div v-else class="reveal flex justify-center gap-3">
            <div
                v-for="part in parts"
                :key="part.label"
                class="w-20 rounded-xl border border-[var(--accent)]/30 bg-white/70 py-3 backdrop-blur"
            >
                <p class="font-heading text-2xl tabular-nums">{{ String(part.value).padStart(2, '0') }}</p>
                <p class="font-body text-[0.65rem] uppercase tracking-widest opacity-70">{{ part.label }}</p>
            </div>
        </div>
    </section>
</template>
