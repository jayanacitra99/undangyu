<script setup>
/*
| RSVP (M6.1).
|
| The layout and copy, with the form inert: the endpoint, the guest token and
| the attendance table all land in Session 28. It renders only when the client
| has RSVP switched on, so an invitation whose package excludes it shows
| nothing rather than a promise.
|
| Deliberately not a working-looking form. A guest who fills in a form that
| silently goes nowhere is worse served than one who sees "coming soon" — so
| the fields are disabled and say why.
*/
defineProps({
    heading: { type: String, default: '' },
});
</script>

<template>
    <section class="mx-auto max-w-md px-6 py-16">
        <h2 class="reveal text-center font-heading text-3xl">{{ heading }}</h2>

        <p class="reveal mt-3 text-center font-body text-sm opacity-75">
            Mohon konfirmasi kehadiran Anda.
        </p>

        <form class="reveal mt-8 space-y-4" aria-describedby="rsvp-status" @submit.prevent>
            <fieldset disabled class="space-y-4 opacity-60">
                <div>
                    <label for="rsvp-name" class="font-body text-xs uppercase tracking-widest opacity-70">Nama</label>
                    <input
                        id="rsvp-name"
                        type="text"
                        class="mt-1 w-full rounded-lg border-[var(--accent)]/40 bg-white/80 font-body"
                    >
                </div>

                <div>
                    <span class="font-body text-xs uppercase tracking-widest opacity-70">Kehadiran</span>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="flex-1 rounded-lg border border-[var(--primary)] py-2 font-body text-sm">
                            Hadir
                        </button>
                        <button type="button" class="flex-1 rounded-lg border border-[var(--accent)]/40 py-2 font-body text-sm">
                            Tidak hadir
                        </button>
                    </div>
                </div>
            </fieldset>

            <p id="rsvp-status" class="text-center font-body text-xs opacity-70">
                Formulir RSVP akan aktif sebentar lagi.
            </p>
        </form>
    </section>
</template>
