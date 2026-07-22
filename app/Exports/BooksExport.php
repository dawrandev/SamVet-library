<?php

namespace App\Exports;

use App\Enums\CopyStatus;
use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Book;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class BooksExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string, category_id?: int, language_id?: int}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(BookRepositoryInterface::class)->filtered($this->filters)
            ->with(['type', 'language', 'languages', 'publicationPlace', 'authors', 'categories'])
            ->withCount([
                'copies',
                'copies as available_copies_count' => fn ($q) => $q->where('status', CopyStatus::Available->value),
            ])
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID', 'Sarlavha', 'Muqobil sarlavhalar', 'Turi', 'Tili', 'Boshqa tillari', 'Mualliflar', 'Kategoriyalar',
            'Noshir', 'Nashr joyi', 'Nashr yili', 'Betlar soni', 'ISBN', 'UDC', 'Muallif belgisi', 'Adadi',
            'Auditoriyasi', "O'lchami (sm)", 'Bosma taboqlar', 'Elektron nusxa', 'Jami nusxalar', 'Mavjud nusxalar', "Ko'rishlar soni",
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($book): array
    {
        /** @var Book $book */
        return [
            $book->id,
            $book->title,
            $book->parallel_titles ? implode('; ', $book->parallel_titles) : '',
            $book->type?->name,
            $book->language?->name,
            $book->languages->pluck('name')->implode(', '),
            $book->authors->pluck('name')->implode(', '),
            $book->categories->pluck('name')->implode(', '),
            $book->publisher,
            $book->publicationPlace?->name,
            $book->publication_year,
            $book->pages,
            $book->isbn,
            $book->udc,
            $book->author_mark,
            $book->print_run,
            $book->target_audience,
            $book->size_cm,
            $book->print_sheets,
            $book->electronic_file ? 'Ha' : "Yo'q",
            $book->copies_count,
            $book->available_copies_count,
            $book->views_count,
        ];
    }
}
