<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The guest table's query (M5.10, 24.4).
 *
 * Search, group filter, sort and page — all server-side, because the table is
 * specified to hold 1000+ rows and a client on a phone should never be sent
 * all of them to filter in JavaScript.
 *
 * The sort column is an allowlist, not a string handed to orderBy: user input
 * reaching a column name is how an ordinary listing becomes an injection.
 */
class IndexGuestsRequest extends FormRequest
{
    public const PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    /**
     * @var array<string, string>
     */
    public const SORTS = [
        'name' => 'name',
        'created_at' => 'created_at',
        'table_number' => 'table_number',
        'opened_at' => 'opened_at',
        'sent_at' => 'sent_at',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->invitation()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            // "none" is a filter of its own: the guests nobody has grouped yet
            // are exactly the ones a client is looking for at this point.
            'group' => [
                'nullable',
                Rule::when(
                    $this->input('group') !== 'none',
                    [Rule::exists('guest_groups', 'id')->where('invitation_id', $this->invitation()->getKey())],
                ),
            ],
            'vip' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * The query this request describes, ready to paginate.
     *
     * @return Builder<Guest>
     */
    public function guestsQuery(): Builder
    {
        $validated = $this->validated();
        $group = $validated['group'] ?? null;

        return Guest::query()
            ->where('invitation_id', $this->invitation()->getKey())
            ->with('group:id,name,color')
            ->search($validated['search'] ?? null)
            ->when($group === 'none', fn ($query) => $query->whereNull('guest_group_id'))
            ->when($group !== null && $group !== 'none', fn ($query) => $query->where('guest_group_id', $group))
            ->when(array_key_exists('vip', $validated) && $validated['vip'] !== null,
                fn ($query) => $query->where('is_vip', $this->boolean('vip')))
            ->orderBy(
                self::SORTS[$validated['sort'] ?? 'name'],
                ($validated['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            )
            // A stable tiebreaker. Without it, two guests with the same name
            // can swap places between pages and one of them is never shown.
            ->orderBy('id');
    }

    public function perPage(): int
    {
        return (int) ($this->validated()['per_page'] ?? self::PER_PAGE);
    }

    public function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
