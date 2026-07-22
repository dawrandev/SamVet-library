<?php

namespace App\Exports;

use App\Exports\Concerns\WithBoldHeaderRow;
use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class VideosExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use WithBoldHeaderRow;

    /**
     * @param  array{search?: string}  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return app(VideoRepositoryInterface::class)->filtered($this->filters)
            ->withCount('tracks')
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Nomi', 'Muallif', 'Annotatsiya', 'Videolar soni', "Ko'rishlar soni"];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($video): array
    {
        /** @var Video $video */
        return [
            $video->id,
            $video->name,
            $video->author,
            $video->annotation,
            $video->tracks_count,
            $video->views_count,
        ];
    }
}
