<?php

namespace App\Support;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The bookings list filters, shared by the index and the CSV export so the export can
 * never drift from what is on screen. Unknown or malformed values are dropped, not errors.
 */
class BookingFilters
{
    /** @param  array<string, string|int|null>  $values */
    private function __construct(private array $values) {}

    public static function fromRequest(Request $request): self
    {
        $int = fn (string $key) => ctype_digit((string) $request->query($key)) ? (int) $request->query($key) : null;
        $enum = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        $runner = $request->query('runner_id');

        return new self([
            'q' => trim((string) $request->query('q', '')) ?: null,
            'status' => BookingStatus::tryFrom((string) $request->query('status'))?->value,
            'convocation_session_id' => $int('convocation_session_id'),
            'faculty_id' => $int('faculty_id'),
            'delivery_method' => $enum('delivery_method', ['pickup', 'cod']),
            // "none" means unassigned.
            'runner_id' => $runner === 'none' ? 'none' : $int('runner_id'),
            'paid' => $enum('paid', ['yes', 'no']),
        ]);
    }

    /**
     * @param  Builder<\App\Models\Booking>  $query
     * @return Builder<\App\Models\Booking>
     */
    public function apply(Builder $query): Builder
    {
        $v = $this->values;

        return $query
            ->when($v['q'], fn ($q, $term) => $q->search($term))
            ->when($v['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($v['convocation_session_id'], fn ($q, $id) => $q->where('convocation_session_id', $id))
            ->when($v['faculty_id'], fn ($q, $id) => $q->where('faculty_id', $id))
            ->when($v['delivery_method'], fn ($q, $method) => $q->where('delivery_method', $method))
            ->when($v['runner_id'], fn ($q, $runner) => $runner === 'none'
                ? $q->whereNull('runner_id')
                : $q->where('runner_id', $runner))
            ->when($v['paid'], fn ($q, $paid) => $paid === 'yes'
                ? $q->whereNotNull('paid_at')
                : $q->whereNull('paid_at'));
    }

    public function get(string $key): string|int|null
    {
        return $this->values[$key] ?? null;
    }

    /** Only the filters actually set; used for the query string and the export log. */
    public function active(): array
    {
        return array_filter($this->values, fn ($value) => $value !== null);
    }
}
