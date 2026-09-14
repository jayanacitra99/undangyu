<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Template;
use App\Support\TemplateCache;

/**
 * Any catalog write invalidates the cached public gallery (CLAUDE.md hard rule 5).
 */
class TemplateObserver
{
    public function saved(Template $template): void
    {
        TemplateCache::flush();
    }

    public function deleted(Template $template): void
    {
        TemplateCache::flush();
    }

    public function restored(Template $template): void
    {
        TemplateCache::flush();
    }
}
