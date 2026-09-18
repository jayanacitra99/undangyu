/*
| Scroll animations (22.4).
|
| IntersectionObserver rather than AOS: it is already in every browser we
| target, it costs no bundle, and the elements it animates keep their layout
| box the whole time — a fade plus a small translate, never a height change.
| **No layout shift** is the requirement, so nothing here animates a property
| that reflows.
|
| Targets are collected on demand, not once at mount. The invitation's content
| does not exist until the guest taps the cover open, so a one-shot pass at
| mount finds nothing and leaves every section at opacity 0 — a blank page
| below the fold. `refresh()` is what the caller runs when new content appears.
|
| Honours `prefers-reduced-motion` by revealing everything immediately.
*/
import { onBeforeUnmount, onMounted } from 'vue';

export const REVEAL_CLASS = 'reveal';
export const REVEALED_CLASS = 'reveal-in';

export function useReveal(root) {
    let observer = null;

    const reducedMotion = () =>
        typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const targets = () => (root?.value ?? document).querySelectorAll?.(`.${REVEAL_CLASS}`) ?? [];

    function refresh() {
        const found = targets();

        if (reducedMotion() || typeof IntersectionObserver === 'undefined') {
            found.forEach((target) => target.classList.add(REVEALED_CLASS));

            return;
        }

        observer ??= new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add(REVEALED_CLASS);
                    // One-way: a guest scrolling back up should not watch the
                    // page animate itself again.
                    observer.unobserve(entry.target);
                });
            },
            { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
        );

        found.forEach((target) => {
            if (!target.classList.contains(REVEALED_CLASS)) {
                observer.observe(target);
            }
        });
    }

    onMounted(refresh);
    onBeforeUnmount(() => observer?.disconnect());

    return { refresh };
}
