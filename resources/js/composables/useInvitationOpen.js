/*
| The cover gate (22.3).
|
| A guest arrives at a full-screen cover and taps "Buka Undangan". Until they
| do, the page does not scroll — the invitation is meant to be opened, and a
| half-scrolled cover with the music silent is nobody's design.
|
| That tap is also the interaction the audio needs: browsers block autoplay,
| and a page that fights them plays nothing while the guest hunts for a mute
| button. Never autoplay.
*/
import { onBeforeUnmount, ref } from 'vue';

export function useInvitationOpen() {
    const isOpen = ref(false);

    const lock = () => {
        document.body.style.overflow = 'hidden';
    };

    const unlock = () => {
        document.body.style.overflow = '';
    };

    lock();

    function open() {
        if (isOpen.value) {
            return;
        }

        isOpen.value = true;
        unlock();

        // Top of the content, not wherever a restored scroll position left
        // the page.
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    // A component that unmounts mid-gate must not leave the page unscrollable.
    onBeforeUnmount(unlock);

    return { isOpen, open };
}
