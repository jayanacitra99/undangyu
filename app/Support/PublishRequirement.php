<?php

declare(strict_types=1);

namespace App\Support;

/**
 * One line of the publish validation gate (docs/04 § 4).
 *
 * The gate is a table in the spec, and the builder has to show it as a list of
 * things still to do. So each check answers three questions: what is it called,
 * is it met, and — when it is not — what does the client actually have to do.
 * The hint is the whole point; "Mempelai: belum" is not a task.
 */
final readonly class PublishRequirement
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $met,
        public ?string $hint = null,
        public ?string $tab = null,
    ) {}

    public static function met(string $key, string $label, ?string $tab = null): self
    {
        return new self($key, $label, true, null, $tab);
    }

    public static function unmet(string $key, string $label, string $hint, ?string $tab = null): self
    {
        return new self($key, $label, false, $hint, $tab);
    }
}
