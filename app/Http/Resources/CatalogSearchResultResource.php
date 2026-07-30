<?php

namespace App\Http\Resources;

use App\Data\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the homepage/catalog live-search dropdown.
 *
 * @mixin CatalogItem
 */
class CatalogSearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CatalogItem $item */
        $item = $this->resource;

        return [
            'type' => $item->type->value,
            'type_label' => $item->badgeLabel(),
            'title' => $item->title,
            'author' => $item->author,
            'cover_url' => $item->coverImage ? asset('storage/'.$item->coverImage) : null,
            'url' => $item->url(),
        ];
    }
}
