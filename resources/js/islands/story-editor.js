/*
| Island entry for the builder's "Cerita" tab (19.2).
*/
import { createApp } from 'vue';
import StoryEditor from '@/components/builder/StoryEditor.vue';

document.querySelectorAll('[data-island="story-editor"]').forEach((el) => {
    createApp(StoryEditor, {
        stories: JSON.parse(el.dataset.stories),
        storeUrl: el.dataset.storeUrl,
        reorderUrl: el.dataset.reorderUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        imageUrlTemplate: el.dataset.imageUrlTemplate,
        csrfToken: el.dataset.csrfToken,
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
