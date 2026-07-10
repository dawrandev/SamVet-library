<?php

namespace Database\Seeders;

use App\Enums\BookFormat;
use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Location;
use App\Models\PublicationPlace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        // Book 1: Iqtisodiyot nazariyasi
        $this->createBook([
            'title' => 'Iqtisodiyot nazariyasi',
            'udc' => '330.1',
            'author_mark' => 'O-56',
            'type' => 'Darslik',
            'language' => 'O‘zbek',
            'publisher' => ['uz' => 'Iqtisod-moliya', 'ru' => 'Иктисод-молия', 'kk' => 'Iqtisod-moliya'],
            'publication_place' => 'Toshkent',
            'publication_year' => 2020,
            'pages' => 480,
            'isbn' => '978-9943-13-456-7',
            'print_run' => 1000,
            'annotation' => 'Iqtisodiyot nazariyasining asosiy tushunchalari, bozor mexanizmi va makroiqtisodiyot masalalari yoritilgan darslik.',
            'authors' => ['A. O‘lmasov', 'A. Vahobov'],
            'categories' => ['Iqtisodiyot nazariyasi'],
            'copies' => [
                ['inventory_number' => 'INV-0001', 'format' => BookFormat::Print, 'condition' => CopyCondition::New, 'status' => CopyStatus::Available, 'location' => 'Kitob berish bo‘limi', 'price' => 45000],
                ['inventory_number' => 'INV-0002', 'format' => BookFormat::Print, 'condition' => CopyCondition::Old, 'status' => CopyStatus::Available, 'location' => 'Kitob berish bo‘limi', 'price' => 45000],
                ['inventory_number' => 'INV-0003', 'format' => BookFormat::Print, 'condition' => CopyCondition::Torn, 'status' => CopyStatus::WrittenOff, 'location' => 'Arxiv', 'price' => 45000],
            ],
        ]);

        // Book 2: Umumiy veterinariya asoslari
        $this->createBook([
            'title' => 'Umumiy veterinariya asoslari',
            'udc' => '619',
            'author_mark' => 'X-72',
            'type' => 'Darslik',
            'language' => 'O‘zbek',
            'publisher' => ['uz' => 'Fan', 'ru' => 'Фан', 'kk' => 'Fan'],
            'publication_place' => 'Samarqand',
            'publication_year' => 2019,
            'pages' => 320,
            'isbn' => '978-9943-11-222-3',
            'print_run' => 500,
            'annotation' => 'Veterinariya sohasining umumiy asoslari, hayvonlar salomatligi va profilaktika masalalari bo‘yicha o‘quv darslik.',
            'authors' => ['B. Xodiyev'],
            'categories' => ['Umumiy veterinariya'],
            'copies' => [
                ['inventory_number' => 'INV-0101', 'format' => BookFormat::Print, 'condition' => CopyCondition::New, 'status' => CopyStatus::Available, 'location' => 'Ilmiy adabiyotlar zali', 'price' => 38000],
                ['inventory_number' => 'INV-0102', 'format' => BookFormat::Braille, 'condition' => CopyCondition::New, 'status' => CopyStatus::Available, 'location' => 'O‘qish zali', 'price' => 52000],
            ],
        ]);
    }

    private function createBook(array $data): void
    {
        $book = Book::updateOrCreate(
            ['slug' => Str::slug($data['title'])],
            [
                'title' => $data['title'],
                'udc' => $data['udc'],
                'author_mark' => $data['author_mark'],
                'book_type_id' => BookType::where('name->uz', $data['type'])->value('id'),
                'language_id' => Language::where('name->uz', $data['language'])->value('id'),
                'publisher' => $data['publisher'],
                'publication_place_id' => PublicationPlace::where('name->uz', $data['publication_place'])->value('id'),
                'publication_year' => $data['publication_year'],
                'pages' => $data['pages'],
                'isbn' => $data['isbn'],
                'print_run' => $data['print_run'],
                'annotation' => $data['annotation'],
            ]
        );

        // Authors (many-to-many)
        $authorIds = Author::whereIn('name', $data['authors'])->pluck('id');
        $book->authors()->sync($authorIds);

        // Categories (many-to-many) — translated, matched by uz
        $categoryIds = Category::whereIn('name->uz', $data['categories'])->pluck('id');
        $book->categories()->sync($categoryIds);

        // Physical copies
        foreach ($data['copies'] as $copy) {
            $book->copies()->updateOrCreate(
                ['inventory_number' => $copy['inventory_number']],
                [
                    'format' => $copy['format'],
                    'condition' => $copy['condition'],
                    'status' => $copy['status'],
                    'location_id' => Location::where('name->uz', $copy['location'])->value('id'),
                    'price' => $copy['price'],
                ]
            );
        }
    }
}
