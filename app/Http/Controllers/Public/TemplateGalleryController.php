<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TemplateGalleryRequest;
use App\Models\EventType;
use App\Models\Template;
use App\Models\TemplateCategory;
use App\Support\TemplateCache;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * The public template catalog (M3.4, M3.5).
 *
 * Read-only by design: nothing here writes to the database, and every response
 * is served from the tagged template cache, flushed by the TemplateObserver.
 *
 * Only plain arrays go into the cache. Eloquent models serialize fine but come
 * back as incomplete objects under some phpredis builds, and the cache is not
 * the place to find that out — the views read arrays instead.
 */
final class TemplateGalleryController extends Controller
{
    public const PER_PAGE = 12;

    public function index(TemplateGalleryRequest $request): View
    {
        $filters = $request->filters();

        /** @var array{items: list<array<string, mixed>>, total: int} $page */
        $page = TemplateCache::remember(
            'gallery:'.md5(serialize($filters)),
            fn (): array => $this->queryPage($filters),
        );

        $templates = new LengthAwarePaginator(
            $page['items'],
            $page['total'],
            self::PER_PAGE,
            $filters['page'],
            ['path' => route('templates.index'), 'query' => $request->query()],
        );

        return view('templates.index', [
            'templates' => $templates,
            'filters' => $filters,
            'eventTypes' => TemplateCache::remember(
                'gallery:event-types',
                fn (): array => EventType::query()->active()->ordered()->get(['name', 'slug'])->toArray(),
            ),
            'categories' => TemplateCache::remember(
                'gallery:categories',
                fn (): array => TemplateCategory::query()->active()->ordered()->get(['name', 'slug'])->toArray(),
            ),
        ]);
    }

    public function show(string $slug): View
    {
        /** @var array<string, mixed>|null $template */
        $template = TemplateCache::remember(
            'detail:'.$slug,
            fn (): ?array => $this->queryDetail($slug),
        );

        // A draft or archived template is not merely hidden from the grid; it
        // does not exist publicly.
        abort_if($template === null, 404);

        return view('templates.show', [
            'template' => $template,
        ]);
    }

    /**
     * @param  array{event_type: ?string, category: ?string, tier: ?string, page: int}  $filters
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    private function queryPage(array $filters): array
    {
        $paginator = Template::query()
            ->with('category:id,name')
            ->published()
            ->forEventType($filters['event_type'])
            ->forCategory($filters['category'])
            ->forTier($filters['tier'])
            ->ordered()
            ->paginate(self::PER_PAGE, ['*'], 'page', $filters['page']);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (Template $template): array => [
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'category' => $template->category->name,
                    'thumbnail_url' => Storage::disk('public')->url($template->thumbnail),
                    'is_premium' => $template->is_premium,
                ])
                ->all(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function queryDetail(string $slug): ?array
    {
        $template = Template::query()
            ->with(['category:id,name', 'screenshots', 'eventTypes' => fn ($query) => $query->active()->ordered()])
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($template === null) {
            return null;
        }

        return [
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category->name,
            'version' => $template->version,
            'is_premium' => $template->is_premium,
            'extra_price' => (string) $template->extra_price,
            'thumbnail_url' => Storage::disk('public')->url($template->thumbnail),
            'event_types' => $template->eventTypes->pluck('name')->all(),
            'screenshots' => $template->screenshots
                ->map(fn ($screenshot): array => [
                    'url' => Storage::disk('public')->url($screenshot->path),
                    'caption' => $screenshot->caption,
                ])
                ->all(),
        ];
    }
}
