<?php

namespace App\Exports;

use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Audiobook;
use App\Repositories\Contracts\AudiobookRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AudiobooksExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(AudiobookRepositoryInterface::class)->filtered($this->filters)
            ->withCount('tracks')
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Nomi', 'Muallif', 'Annotatsiya', 'Audiolar soni', "Ko'rishlar soni"];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($audiobook): array
    {
        /** @var Audiobook $audiobook */
        return [
            $audiobook->id,
            $audiobook->name,
            $audiobook->author,
            $audiobook->annotation,
            $audiobook->tracks_count,
            $audiobook->views_count,
        ];
    }
}
