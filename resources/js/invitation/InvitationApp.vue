<script setup>
/*
| The published invitation's Vue root (21.3, 22.6).
|
| It owns three things and delegates everything else:
|
|   - which template component to load, by `view_key`, lazily;
|   - whether the invitation has been opened yet (the cover gate);
|   - the background music, which starts on that same tap and never before.
|
| The template draws. This decides nothing about how it looks.
*/
import { computed, onMounted, shallowRef } from 'vue';
import { loadTemplate } from '@/invitation/templates/registry';
import { useInvitationOpen } from '@/composables/useInvitationOpen';
import { useBackgroundMusic } from '@/composables/useBackgroundMusic';

const props = defineProps({
    payload: { type: Object, required: true },
    // Session 27 resolves the ?to= token into a name; the slot is here so the
    // cover already has somewhere to put it.
    guestName: { type: String, default: null },
    // The rest of what a personalised visit knows about itself, for the RSVP
    // form (28.3): who is answering, how many they may bring, and where to
    // post it.
    guestToken: { type: String, default: null },
    guestMaxPax: { type: Number, default: null },
    rsvpStoreUrl: { type: String, default: '' },
    rsvpLookupUrl: { type: String, default: '' },
    wishStoreUrl: { type: String, default: '' },
    wishFeedUrl: { type: String, default: '' },
    wishMaxLength: { type: Number, default: 500 },
    csrfToken: { type: String, default: '' },
});

const template = shallowRef(null);

const { isOpen, open } = useInvitationOpen();

const audioTrack = computed(() => {
    if (!props.payload.settings.music_enabled) {
        return null;
    }

    return props.payload.media.find((item) => item.type === 'audio')?.url ?? null;
});

const { isPlaying, isAvailable, start, toggle } = useBackgroundMusic(
    audioTrack.value,
    `undangyu.music.${props.payload.invitation.uuid}`,
);

onMounted(async () => {
    template.value = await loadTemplate(props.payload.template.view_key);
});

/*
| One tap does both: it is the gesture the browser requires before it will
| play anything, and pretending otherwise is how a page ends up silent with a
| console warning (22.3).
*/
function openInvitation() {
    open();

    if (props.payload.settings.music_autoplay) {
        start();
    }
}
</script>

<template>
    <div>
        <component
            :is="template"
            v-if="template"
            :payload="payload"
            :is-open="isOpen"
            :guest-name="guestName"
            :guest-token="guestToken"
            :guest-max-pax="guestMaxPax"
            :rsvp-store-url="rsvpStoreUrl"
            :rsvp-lookup-url="rsvpLookupUrl"
            :wish-store-url="wishStoreUrl"
            :wish-feed-url="wishFeedUrl"
            :wish-max-length="wishMaxLength"
            :csrf-token="csrfToken"
            @open="openInvitation"
        />

        <!-- Before the chunk lands. The server-rendered summary is still in the
             DOM until Vue mounts, so this is only ever seen on a slow network
             after mount. -->
        <div v-else class="flex min-h-[100svh] items-center justify-center px-6 text-center">
            <p class="text-sm text-stone-500">{{ payload.invitation.title }}</p>
        </div>

        <button
            v-if="isOpen && isAvailable"
            type="button"
            class="fixed bottom-5 right-5 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-white/90 shadow-lg backdrop-blur"
            :aria-label="isPlaying ? 'Matikan musik' : 'Nyalakan musik'"
            @click="toggle"
        >
            <span aria-hidden="true">{{ isPlaying ? '🔊' : '🔇' }}</span>
        </button>
    </div>
</template>
