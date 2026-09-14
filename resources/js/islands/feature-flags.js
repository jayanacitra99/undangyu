/*
| Island entry for the package feature-flag editor (docs/05 § 2).
|
| Mounts into every [data-island="feature-flags"] on the page and reads its props
| from data-* attributes, JSON-encoded. AdminLTE keeps the rest of the form.
*/
import { createApp } from 'vue';
import FeatureFlagEditor from '@/components/shared/FeatureFlagEditor.vue';

document.querySelectorAll('[data-island="feature-flags"]').forEach((el) => {
    createApp(FeatureFlagEditor, {
        featureKeys: JSON.parse(el.dataset.featureKeys),
        rows: JSON.parse(el.dataset.rows),
        errors: JSON.parse(el.dataset.errors ?? '{}'),
    }).mount(el);
});
