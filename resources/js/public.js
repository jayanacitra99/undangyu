/*
| Public bundle — the published invitation (docs/05 § 2).
|
| The Blade shell server-renders <head> for WhatsApp link previews and a plain
| summary of the invitation in the body. This replaces that summary with the
| Vue app once it loads.
|
| The payload arrives JSON-encoded on the mount div — the same shape
| App\Http\Resources\InvitationPayload produces, already resolved. The template
| components that draw it land in Session 22; this mounts the shell and hands
| them the data.
*/
import { createApp } from 'vue';
import InvitationApp from '@/invitation/InvitationApp.vue';

const el = document.getElementById('invitation');

if (el?.dataset.payload) {
    // The guest the ?to= token resolved to, when there was one (27.2). Absent
    // for a shared link, a wrong token or the preview route.
    const guest = el.dataset.guest ? JSON.parse(el.dataset.guest) : null;

    createApp(InvitationApp, {
        payload: JSON.parse(el.dataset.payload),
        guestName: guest?.name ?? null,
    }).mount(el);
}
