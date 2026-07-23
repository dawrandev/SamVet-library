<?php

namespace App\Exports;

use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Dissertation;
use App\Repositories\Contracts\DissertationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class DissertationsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string, resource_field_id?: int}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(DissertationRepositoryInterface::class)->filtered($this->filters)
            ->with([
                'resourceField', 'scienceField', 'doctoralSpecialty', 'masterSpecialty',
                'language', 'publicationPlace', 'contributors.contributorRole',
            ])
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID', 'Sarlavha', 'Muallif', 'Boshqa ishtirokchilar', 'Darajasi', 'Yo\'nalishi',
            'Fan nomi', 'Ilmiy rahbar', 'Muassasa', 'Tili', 'Nashr joyi',
            'Himoya yili', 'Betlar soni', 'UDC', 'Inventar raqami', 'Holati', 'Annotatsiya', 'Elektron nusxa',
            "Ko'rishlar soni",
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($dissertation): array
    {
        /** @var Dissertation $dissertation */
        $specialty = $dissertation->doctoralSpecialty?->name ?? $dissertation->masterSpecialty?->name;

        return [
            $dissertation->id,
            $dissertation->title,
            $dissertation->author,
            $dissertation->contributors->map(fn ($c) => "{$c->contributorRole?->name}: {$c->name}")->implode('; '),
            $dissertation->degree?->label(),
            $specialty,
            $dissertation->scienceField?->name,
            $dissertation->advisor,
            $dissertation->institution,
            $dissertation->language?->name,
            $dissertation->publicationPlace?->name,
            $dissertation->defense_year,
            $dissertation->pages,
            $dissertation->udc,
            $dissertation->inventory_number,
            $dissertation->condition?->label(),
            $dissertation->annotation,
            $dissertation->electronic_file ? 'Ha' : "Yo'q",
            $dissertation->views_count,
        ];
    }
}
