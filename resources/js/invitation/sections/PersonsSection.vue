<script setup>
/*
| Who the event is about (M4.3).
*/
defineProps({
    heading: { type: String, default: '' },
    persons: { type: Array, required: true },
});
</script>

<template>
    <section class="mx-auto max-w-2xl px-6 py-16 text-center">
        <h2 class="reveal font-heading text-3xl">{{ heading }}</h2>

        <div class="mt-10 space-y-12">
            <article v-for="person in persons" :key="person.full_name" class="reveal">
                <img
                    v-if="person.photo_url"
                    :src="person.photo_url"
                    :alt="person.full_name"
                    loading="lazy"
                    decoding="async"
                    width="160"
                    height="160"
                    class="mx-auto h-40 w-40 rounded-full object-cover ring-4 ring-[var(--accent)]/40"
                >

                <h3 class="mt-5 font-heading text-2xl">{{ person.full_name }}</h3>

                <p v-if="person.child_order" class="mt-2 font-body text-sm opacity-75">
                    {{ person.child_order }}
                </p>

                <p v-if="person.parents.father || person.parents.mother" class="font-body text-sm opacity-75">
                    Putra/Putri dari {{ [person.parents.father, person.parents.mother].filter(Boolean).join(' & ') }}
                </p>

                <p v-if="person.bio" class="mt-3 font-body text-sm opacity-80">{{ person.bio }}</p>

                <a
                    v-if="person.instagram"
                    :href="`https://instagram.com/${person.instagram.replace('@', '')}`"
                    target="_blank"
                    rel="noopener"
                    class="mt-3 inline-block font-body text-sm underline opacity-80"
                >
                    {{ person.instagram }}
                </a>
            </article>
        </div>
    </section>
</template>
