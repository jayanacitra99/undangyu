/*
| Repeatable-card editing with debounced autosave (17.1, 17.3).
|
| Both builder islands are the same shape: a list of rows belonging to one
| invitation, each edited in place, saved 800ms after the client stops typing,
| dragged to reorder, deleted with a confirm. Only the fields differ, so the
| behaviour lives here and the components stay markup.
|
| Per-row state, not per-form: two cards can be saving at once, and a
| validation error on one must not blank the indicator on the other.
*/
import { ref } from 'vue';

export const AUTOSAVE_DELAY = 800;

export function useCollectionEditor({
    initial = [],
    storeUrl,
    reorderUrl,
    itemUrlTemplate,
    csrfToken,
}) {
    const items = ref([...initial]);
    const statuses = ref({});
    const errors = ref({});
    const orderStatus = ref('idle');

    const timers = new Map();
    const pending = new Map();

    const itemUrl = (item) => itemUrlTemplate.replace('__ID__', item.id);

    const setStatus = (id, value) => {
        statuses.value = { ...statuses.value, [id]: value };
    };

    const setErrors = (id, value) => {
        errors.value = { ...errors.value, [id]: value };
    };

    async function request(url, { method = 'GET', body = null, json = true } = {}) {
        const headers = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (json) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            method,
            headers,
            credentials: 'same-origin',
            body: body === null ? null : json ? JSON.stringify(body) : body,
        });

        // 204 has no body to read; 422 has one worth reading.
        const payload = response.status === 204 ? null : await response.json().catch(() => null);

        return { ok: response.ok, status: response.status, payload };
    }

    const patchItem = (id, changes) => {
        items.value = items.value.map((item) => (item.id === id ? { ...item, ...changes } : item));
    };

    /*
    | The server derives things the client did not type: coordinates out of a
    | pasted Maps link, a normalised time, a trimmed name. Those have to land
    | back in the row.
    |
    | What must NOT land back is a field the client has kept typing in while
    | the request was in flight — merging the older server copy over it would
    | undo their keystrokes. So anything still pending is skipped, and
    | everything else is taken from the response.
    */
    const mergeSaved = (id, saved) => {
        const stillPending = Object.keys(pending.get(id) ?? {});

        const changes = Object.fromEntries(
            Object.entries(saved).filter(([key]) => !stillPending.includes(key)),
        );

        patchItem(id, changes);
    };

    async function flush(item) {
        const payload = pending.get(item.id);

        if (!payload || Object.keys(payload).length === 0) {
            return;
        }

        pending.delete(item.id);
        setStatus(item.id, 'saving');

        const { ok, status, payload: body } = await request(itemUrl(item), {
            method: 'PATCH',
            body: payload,
        });

        if (ok) {
            setErrors(item.id, {});
            setStatus(item.id, 'saved');
            mergeSaved(item.id, body?.data ?? {});

            return;
        }

        if (status === 422) {
            setErrors(item.id, body?.errors ?? {});
        }

        setStatus(item.id, 'failed');
    }

    function touch(item, field, value) {
        const next = { ...(pending.get(item.id) ?? {}), [field]: value };
        pending.set(item.id, next);

        // The local row is the source of truth while the client types. Without
        // this, any re-render — the save of a sibling field, a status badge
        // change — writes the pre-edit value back into the input they are
        // still typing in.
        patchItem(item.id, { [field]: value });

        setStatus(item.id, 'dirty');

        window.clearTimeout(timers.get(item.id));
        timers.set(item.id, window.setTimeout(() => flush(item), AUTOSAVE_DELAY));
    }

    /*
    | Saving now rather than in 800ms: what a blur or a "save" click means.
    */
    function flushNow(item) {
        window.clearTimeout(timers.get(item.id));

        return flush(item);
    }

    async function add(attributes) {
        orderStatus.value = 'saving';

        const { ok, status, payload } = await request(storeUrl, {
            method: 'POST',
            body: attributes,
        });

        if (!ok) {
            orderStatus.value = 'failed';

            // A create refused on validation is the caller's to show; there is
            // no row yet to hang the errors on.
            return { ok: false, status, errors: payload?.errors ?? {} };
        }

        items.value = [...items.value, payload.data];
        orderStatus.value = 'idle';

        return { ok: true, item: payload.data };
    }

    async function remove(item) {
        const { ok } = await request(itemUrl(item), { method: 'DELETE' });

        if (!ok) {
            setStatus(item.id, 'failed');

            return false;
        }

        items.value = items.value.filter((row) => row.id !== item.id);

        return true;
    }

    async function persistOrder() {
        orderStatus.value = 'saving';

        const { ok } = await request(reorderUrl, {
            method: 'POST',
            body: { ids: items.value.map((item) => item.id) },
        });

        orderStatus.value = ok ? 'saved' : 'failed';
    }

    const statusOf = (item) => statuses.value[item.id] ?? 'idle';
    const errorsOf = (item) => errors.value[item.id] ?? {};
    const errorFor = (item, field) => errorsOf(item)[field]?.[0] ?? null;

    return {
        items,
        orderStatus,
        add,
        remove,
        touch,
        flushNow,
        persistOrder,
        request,
        itemUrl,
        statusOf,
        errorsOf,
        errorFor,
        setStatus,
        setErrors,
    };
}
