<?php

use App\Models\Article;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\DissertationRepositoryInterface;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use App\Services\Site\OnlineReaderService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The resolver is the gate for protected reading: a record is readable only if
 * it exists AND has a stored PDF. These are the security-critical branches.
 */
function makeReaderService($catalog = null, $periodicals = null, $dissertations = null, $avtoreferats = null): OnlineReaderService
{
    return new OnlineReaderService(
        $catalog ?? Mockery::mock(CatalogRepositoryInterface::class),
        $periodicals ?? Mockery::mock(PeriodicalRepositoryInterface::class),
        $dissertations ?? Mockery::mock(DissertationRepositoryInterface::class),
        $avtoreferats ?? Mockery::mock(AvtoreferatRepositoryInterface::class),
    );
}

it('returns a book that has a stored PDF', function () {
    $book = new Book(['electronic_file' => 'books/electronic/x.pdf']);
    $catalog = Mockery::mock(CatalogRepositoryInterface::class);
    $catalog->shouldReceive('findPublicBySlug')->once()->with('slug')->andReturn($book);

    expect(makeReaderService($catalog)->book('slug'))->toBe($book);
});

it('404s when the book has no stored PDF', function () {
    $book = new Book(['electronic_file' => null]);
    $catalog = Mockery::mock(CatalogRepositoryInterface::class);
    $catalog->shouldReceive('findPublicBySlug')->andReturn($book);

    makeReaderService($catalog)->book('slug');
})->throws(NotFoundHttpException::class);

it('404s when the book does not exist', function () {
    $catalog = Mockery::mock(CatalogRepositoryInterface::class);
    $catalog->shouldReceive('findPublicBySlug')->andReturn(null);

    makeReaderService($catalog)->book('slug');
})->throws(NotFoundHttpException::class);

it('404s when the article has no stored PDF', function () {
    $article = new Article(['electronic_file' => null]);
    $periodicals = Mockery::mock(PeriodicalRepositoryInterface::class);
    $periodicals->shouldReceive('findArticleBySlug')->andReturn($article);

    makeReaderService(periodicals: $periodicals)->article('slug');
})->throws(NotFoundHttpException::class);

it('returns a dissertation that has a stored PDF', function () {
    $dissertation = new Dissertation(['electronic_file' => 'dissertations/electronic/x.pdf']);
    $dissertations = Mockery::mock(DissertationRepositoryInterface::class);
    $dissertations->shouldReceive('findBySlug')->once()->with('slug')->andReturn($dissertation);

    expect(makeReaderService(dissertations: $dissertations)->dissertation('slug'))->toBe($dissertation);
});

it('404s when the dissertation has no stored PDF', function () {
    $dissertation = new Dissertation(['electronic_file' => null]);
    $dissertations = Mockery::mock(DissertationRepositoryInterface::class);
    $dissertations->shouldReceive('findBySlug')->andReturn($dissertation);

    makeReaderService(dissertations: $dissertations)->dissertation('slug');
})->throws(NotFoundHttpException::class);

it('returns an avtoreferat that has a stored PDF', function () {
    $avtoreferat = new Avtoreferat(['electronic_file' => 'avtoreferats/electronic/x.pdf']);
    $avtoreferats = Mockery::mock(AvtoreferatRepositoryInterface::class);
    $avtoreferats->shouldReceive('findBySlug')->once()->with('slug')->andReturn($avtoreferat);

    expect(makeReaderService(avtoreferats: $avtoreferats)->avtoreferat('slug'))->toBe($avtoreferat);
});

it('404s when the avtoreferat has no stored PDF', function () {
    $avtoreferat = new Avtoreferat(['electronic_file' => null]);
    $avtoreferats = Mockery::mock(AvtoreferatRepositoryInterface::class);
    $avtoreferats->shouldReceive('findBySlug')->andReturn($avtoreferat);

    makeReaderService(avtoreferats: $avtoreferats)->avtoreferat('slug');
})->throws(NotFoundHttpException::class);
