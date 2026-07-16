<?php

namespace App\Services\Site;

use App\Repositories\Contracts\MenuItemRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders an admin-authored content page (a menu item + its rich-text Page body)
 * together with its section's sidebar navigation.
 */
class ContentPageService
{
    public function __construct(
        private readonly MenuItemRepositoryInterface $menu,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when the menu item is missing or inactive
     */
    public function show(int $id): array
    {
        $item = $this->menu->findPublicPage($id);

        if ($item === null) {
            throw new NotFoundHttpException();
        }

        // A child page's breadcrumb belongs to its section (parent); a
        // top-level item is its own section.
        $section = $item->parent ?? $item;
        $locale = app()->getLocale();

        return [
            'item' => $item,
            'section' => $section,
            'body' => $item->page?->getTranslation('body', $locale, false)
                ?: $item->page?->getTranslation('body', 'uz', false),
        ];
    }
}
