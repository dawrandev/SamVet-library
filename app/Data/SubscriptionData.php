<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (subscription).
 */
class SubscriptionData
{
    public function __construct(
        public readonly int $subscriber_id,
        public readonly int $journal_id,
        public readonly int $year,
        public readonly int $start_month,
        public readonly int $end_month,
        public readonly float $amount,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            subscriber_id: $request->integer('subscriber_id'),
            journal_id: $request->integer('journal_id'),
            year: $request->integer('year'),
            start_month: $request->integer('start_month'),
            end_month: $request->integer('end_month'),
            amount: (float) $request->input('amount'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'subscriber_id' => $this->subscriber_id,
            'journal_id' => $this->journal_id,
            'year' => $this->year,
            'start_month' => $this->start_month,
            'end_month' => $this->end_month,
            'amount' => $this->amount,
        ];
    }
}
