<script setup>
/*
| The story timeline (M4.5).
*/
import { computed } from 'vue';

const props = defineProps({
    heading: { type: String, default: '' },
    story: { type: Array, required: true },
    invitation: { type: Object, required: true },
});

const locale = computed(() => (props.invitation.language === 'en' ? 'en-GB' : 'id-ID'));

// A plain date, so it is formatted without a time: "Juli 2019" is a memory.
const dateOf = (value) =>
    new Intl.DateTimeFormat(locale.value, { month: 'long', year: 'numeric' }).format(new Date(value));
</script>

<template>
    <section class="mx-auto max-w-2xl px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <ol class="mt-10 space-y-8 border-l border-[var(--accent)]/40 pl-6">
            <li v-for="moment in story" :key="moment.title" class="reveal relative">
                <span class="absolute -left-[1.6rem] mt-2 h-3 w-3 rounded-full bg-[var(--primary)]"></span>

                <p v-if="moment.date" class="font-body text-xs uppercase tracking-widest opacity-70">
                    {{ dateOf(moment.date) }}
                </p>
                <h3 class="mt-1 font-heading text-xl">{{ moment.title }}</h3>

                <img
                    v-if="moment.image_url"
                    :src="moment.image_url"
                    :alt="moment.title"
                    loading="lazy"
                    decoding="async"
                    class="mt-3 aspect-[4/3] w-full rounded-xl object-cover"
                >

                <p v-if="moment.description" class="mt-2 font-body text-sm opacity-85">{{ moment.description }}</p>
            </li>
        </ol>
    </section>
</template>
