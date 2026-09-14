<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\ReorderCatalog;
use App\Actions\Catalog\SaveTemplate;
use App\Actions\Catalog\SyncTemplateScreenshots;
use App\Enums\TemplateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCatalogRequest;
use App\Http\Requests\Admin\StoreTemplateRequest;
use App\Http\Requests\Admin\TemplateRequest;
use App\Http\Requests\Admin\UpdateTemplateRequest;
use App\Models\EventType;
use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Admin CRUD for the template catalog (M3.3).
 */
final class TemplateController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Template::class);

        $templates = Template::query()
            ->with('category')
            ->ordered()
            ->get();

        return view('admin.templates.index', [
            'columns' => [__('Nama'), __('Kategori'), __('View key'), __('Versi')],
            'rows' => $templates->map(fn (Template $template): array => [
                'id' => $template->id,
                // Templates bind on their slug, so the edit and delete links
                // cannot be built from the id the reorder endpoint wants.
                'route_key' => $template->slug,
                'cells' => [
                    $template->name,
                    $template->category->name,
                    $template->view_key,
                    $template->version,
                ],
                // Three statuses, so the row carries its own badge rather than
                // the island's default active/inactive pair.
                'badge_label' => $template->status->label(),
                'badge_class' => $template->status->badgeClass(),
            ])->all(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Template::class);

        return view('admin.templates.create', [
            ...$this->formOptions(),
            'template' => new Template([
                'version' => '1.0.0',
                'status' => TemplateStatus::Draft,
                'is_premium' => false,
                'extra_price' => '0.00',
                'sort_order' => (int) Template::query()->max('sort_order') + 1,
            ]),
            'selectedEventTypeIds' => [],
        ]);
    }

    public function store(
        StoreTemplateRequest $request,
        SaveTemplate $save,
        SyncTemplateScreenshots $syncScreenshots,
    ): RedirectResponse {
        $template = $save(
            null,
            $request->payload(),
            $request->eventTypeIds(),
            $request->file('thumbnail'),
        );

        $this->applyScreenshots($request, $template, $syncScreenshots);

        return to_route('admin.templates.edit', $template)
            ->with('status', __('Template ditambahkan.'));
    }

    public function edit(Template $template): View
    {
        Gate::authorize('update', $template);

        $template->load('screenshots', 'eventTypes');

        return view('admin.templates.edit', [
            ...$this->formOptions(),
            'template' => $template,
            'selectedEventTypeIds' => $template->eventTypes->modelKeys(),
        ]);
    }

    public function update(
        UpdateTemplateRequest $request,
        Template $template,
        SaveTemplate $save,
        SyncTemplateScreenshots $syncScreenshots,
    ): RedirectResponse {
        $save(
            $template,
            $request->payload(),
            $request->eventTypeIds(),
            $request->file('thumbnail'),
        );

        $this->applyScreenshots($request, $template, $syncScreenshots);

        return to_route('admin.templates.index')
            ->with('status', __('Template diperbarui.'));
    }

    public function destroy(Template $template): RedirectResponse
    {
        Gate::authorize('delete', $template);

        // Soft delete: invitations pin a template version, so the row has to
        // survive even when the catalog entry is retired.
        $template->delete();

        return to_route('admin.templates.index')
            ->with('status', __('Template dihapus.'));
    }

    public function reorder(ReorderCatalogRequest $request, ReorderCatalog $reorder): JsonResponse
    {
        Gate::authorize('reorder', Template::class);

        $reorder(Template::class, $request->orderedIds());

        return response()->json(['status' => 'ok']);
    }

    private function applyScreenshots(
        TemplateRequest $request,
        Template $template,
        SyncTemplateScreenshots $syncScreenshots,
    ): void {
        /** @var list<UploadedFile> $uploads */
        $uploads = $request->file('screenshots', []);

        $syncScreenshots(
            $template,
            $uploads,
            array_values($request->validated('screenshot_captions') ?? []),
            array_map(intval(...), $request->validated('remove_screenshots') ?? []),
            array_map(intval(...), $request->validated('screenshot_order') ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => TemplateCategory::query()->active()->ordered()->get(),
            'eventTypes' => EventType::query()->active()->ordered()->get(),
            'statuses' => TemplateStatus::cases(),
        ];
    }
}
