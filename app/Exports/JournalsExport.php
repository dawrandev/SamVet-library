<?php

namespace App\Exports;

use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Journal;
use App\Repositories\Contracts\JournalRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class JournalsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string, journal_type_id?: int, kind?: string}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(JournalRepositoryInterface::class)->filtered($this->filters)
            ->with(['type', 'language', 'publicationPlace'])
            ->withCount('issues')
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID', 'Nomi', 'Turi (jurnal/gazeta)', 'Kategoriyasi', 'Gazeta turi', 'Muassisi',
            'Tili', 'Nashr joyi', 'ISSN', 'Indeks', 'Davriyligi', 'Sonlar soni',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($journal): array
    {
        /** @var Journal $journal */
        return [
            $journal->id,
            $journal->name,
            $journal->kind?->label(),
            $journal->type?->name,
            $journal->newspaper_type?->label(),
            $journal->founder,
            $journal->language?->name,
            $journal->publicationPlace?->name,
            $journal->issn,
            $journal->index,
            $journal->periodicityLabel(),
            $journal->issues_count,
        ];
    }
}
