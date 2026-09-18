<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GuestImportStatus;
use App\Models\GuestImport;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestImport>
 */
class GuestImportFactory extends Factory
{
    protected $model = GuestImport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'user_id' => User::factory(),
            'file_path' => GuestImport::DIRECTORY.'/'.fake()->uuid().'.xlsx',
            'original_filename' => 'daftar-tamu.xlsx',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => null,
            'status' => GuestImportStatus::Queued->value,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (): array => [
            'status' => GuestImportStatus::Completed->value,
            'total_rows' => 500,
            'success_rows' => 488,
            'failed_rows' => 12,
            'errors' => [
                ['row' => 14, 'name' => '', 'message' => 'Nama wajib diisi.'],
            ],
        ]);
    }
}
