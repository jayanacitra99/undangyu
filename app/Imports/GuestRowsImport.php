<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads a client's guest spreadsheet into plain rows (M5.3).
 *
 * Deliberately not a ToModel import: Laravel Excel's model path is
 * all-or-nothing per chunk and swallows the row number, and the whole point of
 * this feature is that row 214 failed and the other 499 did not. So this
 * hands the job an array and the job decides what each row means.
 *
 * Headings are read from the first row, so a client who reorders the template's
 * columns still imports — they only have to keep the names.
 */
class GuestRowsImport implements SkipsEmptyRows, ToArray, WithHeadingRow
{
    /**
     * The template's columns, in order. The key is the heading Laravel Excel
     * produces from the label below it (lower snake_case).
     *
     * @var array<string, string>
     */
    public const COLUMNS = [
        'nama' => 'nama',
        'sebutan' => 'sebutan',
        'telepon' => 'telepon',
        'email' => 'email',
        'alamat' => 'alamat',
        'grup' => 'grup',
        'jumlah_orang' => 'jumlah_orang',
        'vip' => 'vip',
        'meja' => 'meja',
        'catatan' => 'catatan',
    ];

    /**
     * The spreadsheet's own row number for the nth data row: one for the
     * heading, one because spreadsheets count from 1. The error report says
     * "row 214" and the client goes and looks at row 214.
     */
    public const FIRST_DATA_ROW = 2;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $rows = [];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function array(array $rows): void
    {
        $this->rows = $rows;
    }
}
