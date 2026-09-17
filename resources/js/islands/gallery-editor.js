/*
| Island entry for the builder's "Galeri" tab (18.4).
*/
import { createApp } from 'vue';
import GalleryEditor from '@/components/builder/GalleryEditor.vue';

document.querySelectorAll('[data-island="gallery-editor"]').forEach((el) => {
    createApp(GalleryEditor, {
        media: JSON.parse(el.dataset.media),
        quota: JSON.parse(el.dataset.quota),
        tracks: JSON.parse(el.dataset.tracks),
        canUseMusic: el.dataset.canUseMusic === '1',
        uploadUrl: el.dataset.uploadUrl,
        embeddedUrl: el.dataset.embeddedUrl,
        reorderUrl: el.dataset.reorderUrl,
        itemUrlTemplate: el.dataset.itemUrlTemplate,
        coverUrlTemplate: el.dataset.coverUrlTemplate,
        maxUploadMb: Number(el.dataset.maxUploadMb ?? 10),
        csrfToken: el.dataset.csrfToken,
        canEdit: el.dataset.canEdit === '1',
    }).mount(el);
});
