<script setup>
/*
| The digital envelope (M4.7, 22.5).
|
| The account number is the one thing a guest interacts with, standing in a
| queue, on a phone. So it is large, monospaced, and one tap copies it with a
| toast that says so.
*/
import { useClipboard } from '@/composables/useClipboard';

defineProps({
    heading: { type: String, default: '' },
    gifts: { type: Array, required: true },
});

const { toast, copy } = useClipboard();
</script>

<template>
    <section class="mx-auto max-w-xl px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <p class="reveal mt-3 text-center font-body text-sm opacity-75">
            Kehadiran Anda adalah hadiah terbaik. Bila ingin mengirim tanda kasih, berikut caranya.
        </p>

        <div class="mt-10 space-y-4">
            <article
                v-for="gift in gifts"
                :key="`${gift.type}-${gift.account_number ?? gift.recipient_name}`"
                class="reveal rounded-2xl border border-[var(--accent)]/30 bg-white/70 p-6 text-center backdrop-blur"
            >
                <p class="font-body text-xs uppercase tracking-widest opacity-70">{{ gift.label }}</p>

                <p v-if="gift.provider_name" class="mt-2 font-heading text-xl">{{ gift.provider_name }}</p>

                <template v-if="gift.account_number">
                    <p class="mt-1 font-mono text-lg tracking-wider">{{ gift.account_number }}</p>
                    <p v-if="gift.account_name" class="font-body text-sm opacity-75">a.n. {{ gift.account_name }}</p>

                    <button
                        type="button"
                        class="mt-3 rounded-full bg-[var(--primary)] px-5 py-2 font-body text-xs text-white"
                        @click="copy(gift.account_number, `Nomor ${gift.provider_name ?? ''} disalin`.trim())"
                    >
                        Salin nomor
                    </button>
                </template>

                <img
                    v-if="gift.qris_url"
                    :src="gift.qris_url"
                    alt="QRIS"
                    loading="lazy"
                    decoding="async"
                    class="mx-auto mt-4 h-48 w-48 rounded-xl bg-white object-contain p-2"
                >

                <template v-if="gift.recipient_name">
                    <p class="mt-2 font-body font-medium">{{ gift.recipient_name }}</p>
                    <p v-if="gift.address" class="font-body text-sm opacity-75">{{ gift.address }}</p>

                    <button
                        type="button"
                        class="mt-3 rounded-full border border-[var(--primary)] px-5 py-2 font-body text-xs"
                        @click="copy([gift.recipient_name, gift.address].filter(Boolean).join(', '), 'Alamat disalin')"
                    >
                        Salin alamat
                    </button>
                </template>

                <p v-if="gift.notes" class="mt-3 font-body text-xs opacity-70">{{ gift.notes }}</p>
            </article>
        </div>

        <!-- aria-live, so a screen reader announces the copy too. -->
        <transition name="toast">
            <p
                v-if="toast"
                role="status"
                aria-live="polite"
                class="fixed inset-x-0 bottom-6 z-50 mx-auto w-fit rounded-full bg-[var(--ink)] px-5 py-2 font-body text-sm text-white shadow-lg"
            >
                {{ toast }}
            </p>
        </transition>
    </section>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
}
</style>
