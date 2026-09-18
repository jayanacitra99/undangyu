/*
| Background music, started by a gesture (22.3, M4.8).
|
| `autoplay` is deliberately absent. Chrome and Safari refuse audio until the
| user has interacted with the page, so an autoplay attempt is silence plus a
| console warning. The cover's "Buka Undangan" tap is the gesture, and the
| mute toggle stays on screen from then on.
|
| The chosen state is remembered per invitation: a guest who muted it and came
| back to check the address should not be sung at again.
*/
import { onBeforeUnmount, ref } from 'vue';

export function useBackgroundMusic(src, storageKey) {
    const isPlaying = ref(false);
    const isAvailable = ref(Boolean(src));

    let audio = null;

    const remembered = () => {
        try {
            return window.localStorage.getItem(storageKey);
        } catch {
            // Private mode, or storage blocked. Not a reason to fail.
            return null;
        }
    };

    const remember = (value) => {
        try {
            window.localStorage.setItem(storageKey, value);
        } catch {
            // Ignored for the same reason.
        }
    };

    function element() {
        if (audio === null && src) {
            audio = new Audio(src);
            audio.loop = true;
            audio.preload = 'none';

            // A missing or unplayable track hides the control rather than
            // leaving a button that does nothing.
            audio.addEventListener('error', () => {
                isAvailable.value = false;
                isPlaying.value = false;
            });
        }

        return audio;
    }

    async function start() {
        if (!src || remembered() === 'muted') {
            return;
        }

        try {
            await element()?.play();
            isPlaying.value = true;
        } catch {
            // The browser refused. The toggle is still there for the guest.
            isPlaying.value = false;
        }
    }

    async function toggle() {
        const player = element();

        if (player === null) {
            return;
        }

        if (isPlaying.value) {
            player.pause();
            isPlaying.value = false;
            remember('muted');

            return;
        }

        try {
            await player.play();
            isPlaying.value = true;
            remember('playing');
        } catch {
            isPlaying.value = false;
        }
    }

    onBeforeUnmount(() => {
        audio?.pause();
        audio = null;
    });

    return { isPlaying, isAvailable, start, toggle };
}
