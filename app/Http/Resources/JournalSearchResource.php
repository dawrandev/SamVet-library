<?php

namespace App\Http\Resources;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Live-search result for the article form's journal autocomplete.
 *
 * @mixin Journal
 */
class JournalSearchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->name,
            'has_issues' => $this->issues_count > 0,
        ];
    }
}
