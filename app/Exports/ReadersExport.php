<?php

namespace App\Exports;

use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Reader;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class ReadersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string, type?: string, status?: string}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(ReaderRepositoryInterface::class)->filtered($this->filters)
            ->with(['affiliationPlace', 'affiliationUnit', 'affiliationGroup', 'region', 'district'])
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID', 'F.I.Sh.', 'ID raqami', "Ro'yxatga olingan raqami", "Ro'yxatga olingan sanasi", 'Turi',
            "O'qish/ish joyi", 'Mutaxassisligi/bo\'limi', 'Guruhi/lavozimi',
            'Millati', "Tug'ilgan sana", 'Passport', 'JShShIR', 'Jinsi',
            'Viloyat', 'Tuman', 'Manzil', 'Telefon', "A'zolik yili", 'Boshqa kutubxona a\'zoligi',
            'Holati', 'Bloklangan muddat', 'Bloklash sababi', 'Chiqib ketish sababi', 'Izoh',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($reader): array
    {
        /** @var Reader $reader */
        return [
            $reader->id,
            $reader->full_name,
            $reader->id_number,
            $reader->registration_number,
            $reader->issued_date?->format('d.m.Y'),
            $reader->type?->label(),
            $reader->affiliationPlace?->name,
            $reader->affiliationUnit?->name,
            $reader->affiliationGroup?->name,
            $reader->nationality,
            $reader->birth_date?->format('d.m.Y'),
            $reader->passport,
            $reader->pinfl,
            $reader->gender?->label(),
            $reader->region?->name,
            $reader->district?->name,
            $reader->address,
            $reader->phone,
            $reader->member_year,
            $reader->other_library_member,
            $reader->status?->label(),
            $reader->blocked_until?->format('d.m.Y'),
            $reader->block_reason,
            $reader->left_reason,
            $reader->note,
        ];
    }
}
