<?php

namespace App\Data;

use App\Enums\SubscriptionSource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (subscription).
 */
class SubscriptionData
{
    public function __construct(
        public readonly ?int $reader_id,
        public readonly SubscriptionSource $source,
        public readonly int $journal_id,
        public readonly int $delivery_location_id,
        public readonly ?int $post_branch_id,
        public readonly int $year,
        public readonly int $start_month,
        public readonly int $end_month,
        public readonly ?float $amount,
        public readonly ?UploadedFile $receipt_file,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $source = SubscriptionSource::from($request->string('source')->toString());

        return new self(
            // Budget-funded subscriptions have no reader — never trust a client-submitted one for that case.
            reader_id: $source === SubscriptionSource::Budget ? null : $request->integer('reader_id'),
            source: $source,
            journal_id: $request->integer('journal_id'),
            delivery_location_id: $request->integer('delivery_location_id'),
            post_branch_id: $request->integer('post_branch_id') ?: null,
            year: $request->integer('year'),
            start_month: $request->integer('start_month'),
            end_month: $request->integer('end_month'),
            amount: $request->filled('amount') ? (float) $request->input('amount') : null,
            receipt_file: $request->file('receipt_file'),
        );
    }

    /**
     * Only the scalar fields written to the subscriptions table (without the file).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'reader_id' => $this->reader_id,
            'source' => $this->source->value,
            'journal_id' => $this->journal_id,
            'delivery_location_id' => $this->delivery_location_id,
            'post_branch_id' => $this->post_branch_id,
            'year' => $this->year,
            'start_month' => $this->start_month,
            'end_month' => $this->end_month,
            'amount' => $this->amount,
        ];
    }
}
