<?php

namespace App\Exports;

use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Avtoreferat;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AvtoreferatsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(AvtoreferatRepositoryInterface::class)->filtered($this->filters)
            ->with(['scienceField', 'publicationPlace', 'contributors.contributorRole', 'languages'])
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID', 'Sarlavha', 'Muallif', 'Boshqa ishtirokchilar', 'Ixtisosligi', 'Fan nomi', 'Darajasi',
            'Kengash raqami', 'Himoya muassasasi', 'Bajarilgan muassasa', 'Ilmiy rahbar',
            'UDC', "Ro'yxatga olish raqami", 'Holati', 'Nashr joyi', 'Himoya yili', 'Inventar raqami',
            'Tillari', 'Tayanch so\'zlar', 'Annotatsiya', 'Elektron nusxa', "Ko'rishlar soni",
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($avtoreferat): array
    {
        /** @var Avtoreferat $avtoreferat */
        return [
            $avtoreferat->id,
            $avtoreferat->title,
            $avtoreferat->author,
            $avtoreferat->contributors->map(fn ($c) => "{$c->contributorRole?->name}: {$c->name}")->implode('; '),
            $avtoreferat->specialty,
            $avtoreferat->scienceField?->name,
            $avtoreferat->degree?->label(),
            $avtoreferat->council_number,
            $avtoreferat->defense_institution,
            $avtoreferat->performed_institution,
            $avtoreferat->advisor,
            $avtoreferat->udc,
            $avtoreferat->registration_number,
            $avtoreferat->condition?->label(),
            $avtoreferat->publicationPlace?->name,
            $avtoreferat->defense_year,
            $avtoreferat->inventory_number,
            $avtoreferat->languages->pluck('name')->implode(', '),
            $avtoreferat->keywords,
            $avtoreferat->annotation,
            $avtoreferat->electronic_file ? 'Ha' : "Yo'q",
            $avtoreferat->views_count,
        ];
    }
}
