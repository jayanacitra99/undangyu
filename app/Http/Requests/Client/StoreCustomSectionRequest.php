<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Invitation;
use App\Models\InvitationSection;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A client's own section (M4.10).
 *
 * The body is plain text, not HTML. CLAUDE.md forbids `v-html` on
 * user-supplied content, and shipping a rich-text editor without a sanitiser
 * on the way in would be writing markup we then cannot safely render. Line
 * breaks survive; formatting waits for a sanitiser.
 */
class StoreCustomSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [InvitationSection::class, $this->invitation()]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'body' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * The column is `content`, a JSON blob whose shape depends on the section;
     * a custom one holds its body.
     *
     * @return array<string, mixed>
     */
    public function attributesForSection(): array
    {
        return [
            'title' => $this->validated('title'),
            'content' => ['body' => $this->validated('body')],
            'is_visible' => true,
        ];
    }

    protected function invitation(): Invitation
    {
        $invitation = $this->route('invitation');

        abort_unless($invitation instanceof Invitation, 404);

        return $invitation;
    }
}
