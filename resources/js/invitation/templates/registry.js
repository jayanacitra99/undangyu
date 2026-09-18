/*
| The template registry (22.6).
|
| `view_key` from the payload to a dynamic import, so a guest downloads the one
| template their invitation uses and not the catalogue. Vite splits each of
| these into its own chunk because the import is lazy — that is the entire
| point, and it is why this map holds functions rather than components.
|
| A template whose folder is not here yet falls back, so an invitation sold
| against a template still in development renders rather than white-screening.
*/
export const FALLBACK_VIEW_KEY = 'sekar-ayu';

const templates = {
    'sekar-ayu': () => import('./sekar-ayu/index.vue'),
};

export function hasTemplate(viewKey) {
    return Object.hasOwn(templates, viewKey);
}

export function templateKeys() {
    return Object.keys(templates);
}

/**
 * Resolves to the component. Unknown keys resolve to the fallback rather than
 * throwing — a missing folder is our problem, not the guest's.
 */
export async function loadTemplate(viewKey) {
    const load = templates[viewKey] ?? templates[FALLBACK_VIEW_KEY];

    const module = await load();

    return module.default;
}
