/*
| Server-side guest table (24.4, M5.10).
|
| Everything that narrows the list — search, group, VIP, sort, page — is a
| query parameter, and the server answers with one page. The table is specified
| to hold 1000+ rows; the only way it stays usable on a phone is by never
| holding more than 25 of them at a time.
|
| The first page arrives rendered into the island's props, so the table has
| content before its first request. Every change after that refetches.
*/
import { computed, reactive, ref, watch } from 'vue';

export const SEARCH_DELAY = 350;

export function useGuestTable({
    indexUrl,
    initialGuests = [],
    initialPagination,
    initialGroups = [],
    initialQuota = {},
    csrfToken,
}) {
    const guests = ref([...initialGuests]);
    const pagination = reactive({ ...initialPagination });
    // Both move whenever the list does — a new guest changes the quota and one
    // group's count — so both come back with every page.
    const groups = ref([...initialGroups]);
    const quota = reactive({ ...initialQuota });
    const loading = ref(false);
    const failed = ref(false);

    const filters = reactive({
        search: '',
        group: '',
        vip: '',
        sort: 'name',
        direction: 'asc',
    });

    const selected = ref(new Set());

    async function request(url, { method = 'GET', body = null } = {}) {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: body === null ? null : JSON.stringify(body),
        });

        const payload = response.status === 204 ? null : await response.json().catch(() => null);

        return { ok: response.ok, status: response.status, payload };
    }

    const queryString = (page) => {
        const params = new URLSearchParams();

        if (filters.search !== '') params.set('search', filters.search);
        if (filters.group !== '') params.set('group', filters.group);
        if (filters.vip !== '') params.set('vip', filters.vip);

        params.set('sort', filters.sort);
        params.set('direction', filters.direction);
        params.set('per_page', String(pagination.per_page));
        params.set('page', String(page));

        return params.toString();
    };

    /*
    | Only the newest fetch may write to the table. A client typing "budi"
    | fires four requests, and without this the answer to "bud" can land after
    | the answer to "budi" and leave the wrong rows on screen.
    */
    let sequence = 0;

    async function load(page = pagination.current_page) {
        const ticket = ++sequence;

        loading.value = true;
        failed.value = false;

        const { ok, payload } = await request(`${indexUrl}?${queryString(page)}`);

        if (ticket !== sequence) {
            return;
        }

        loading.value = false;

        if (!ok || !payload) {
            failed.value = true;

            return;
        }

        guests.value = payload.data;
        Object.assign(pagination, {
            current_page: payload.meta.current_page,
            last_page: payload.meta.last_page,
            per_page: payload.meta.per_page,
            total: payload.meta.total,
        });

        groups.value = payload.meta.groups ?? groups.value;
        Object.assign(quota, payload.meta.quota ?? {});

        // A selection that survives a filter change is a bulk delete aimed at
        // rows the client can no longer see.
        selected.value = new Set();
    }

    let searchTimer = null;

    watch(
        () => filters.search,
        () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => load(1), SEARCH_DELAY);
        },
    );

    watch(
        () => [filters.group, filters.vip, filters.sort, filters.direction],
        () => load(1),
    );

    function sortBy(column) {
        if (filters.sort === column) {
            filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';

            return;
        }

        filters.sort = column;
        filters.direction = 'asc';
    }

    const isSelected = (guest) => selected.value.has(guest.id);

    function toggle(guest) {
        const next = new Set(selected.value);

        next.has(guest.id) ? next.delete(guest.id) : next.add(guest.id);
        selected.value = next;
    }

    function toggleAll() {
        selected.value = allSelected.value ? new Set() : new Set(guests.value.map((guest) => guest.id));
    }

    const allSelected = computed(
        () => guests.value.length > 0 && guests.value.every((guest) => selected.value.has(guest.id)),
    );

    const selectedIds = computed(() => [...selected.value]);

    const pages = computed(() => {
        // A window around the current page: 40 page links is not navigation.
        const { current_page: current, last_page: last } = pagination;
        const from = Math.max(1, current - 2);
        const to = Math.min(last, from + 4);

        return Array.from({ length: to - from + 1 }, (_, index) => from + index);
    });

    return {
        guests,
        groups,
        quota,
        pagination,
        pages,
        filters,
        loading,
        failed,
        load,
        sortBy,
        request,
        selected,
        selectedIds,
        isSelected,
        toggle,
        toggleAll,
        allSelected,
    };
}
