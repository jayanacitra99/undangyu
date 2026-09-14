<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\ReorderCatalog;
use App\Enums\PersonRole;
use App\Enums\SectionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCatalogRequest;
use App\Http\Requests\Admin\StoreEventTypeRequest;
use App\Http\Requests\Admin\UpdateEventTypeRequest;
use App\Models\EventType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin CRUD for the event taxonomy (M3.1).
 */
final class EventTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', EventType::class);

        return view('admin.event-types.index', [
            'columns' => [__('Nama'), __('Slug'), __('Peran'), __('Seksi bawaan')],
            // The island renders plain text only — the cells are built here, not
            // in JavaScript, so nothing user-supplied is ever interpreted as HTML.
            'rows' => EventType::query()->ordered()->get()->map(fn (EventType $type): array => [
                'id' => $type->id,
                'cells' => [
                    $type->name,
                    $type->slug,
                    implode(', ', array_map(
                        static fn (string $role): string => PersonRole::from($role)->label(),
                        $type->person_roles,
                    )),
                    trans_choice(':count seksi|:count seksi', count($type->default_sections)),
                ],
                'is_active' => $type->is_active,
            ])->all(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', EventType::class);

        return view('admin.event-types.create', [
            'eventType' => new EventType([
                'person_roles' => [],
                'default_sections' => [],
                'is_active' => true,
                'sort_order' => (int) EventType::query()->max('sort_order') + 1,
            ]),
            'personRoles' => PersonRole::cases(),
            'sectionKeys' => SectionKey::cases(),
        ]);
    }

    public function store(StoreEventTypeRequest $request): RedirectResponse
    {
        EventType::query()->create($request->validated());

        return to_route('admin.event-types.index')
            ->with('status', __('Jenis acara ditambahkan.'));
    }

    public function edit(EventType $eventType): View
    {
        Gate::authorize('update', $eventType);

        return view('admin.event-types.edit', [
            'eventType' => $eventType,
            'personRoles' => PersonRole::cases(),
            'sectionKeys' => SectionKey::cases(),
        ]);
    }

    public function update(UpdateEventTypeRequest $request, EventType $eventType): RedirectResponse
    {
        $eventType->update($request->validated());

        return to_route('admin.event-types.index')
            ->with('status', __('Jenis acara diperbarui.'));
    }

    public function destroy(EventType $eventType): RedirectResponse
    {
        Gate::authorize('delete', $eventType);

        // Session 14 adds invitations.event_type_id with FK restrict; until that
        // table exists there is nothing here that can reference this row.
        $eventType->delete();

        return to_route('admin.event-types.index')
            ->with('status', __('Jenis acara dihapus.'));
    }

    public function reorder(ReorderCatalogRequest $request, ReorderCatalog $reorder): JsonResponse
    {
        Gate::authorize('reorder', EventType::class);

        $reorder(EventType::class, $request->orderedIds());

        return response()->json(['status' => 'ok']);
    }
}
