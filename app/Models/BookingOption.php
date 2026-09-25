<?php

namespace App\Models;

use App\Enums\BookingOptionType;
use Database\Factories\BookingOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'label_en', 'label_ms', 'position', 'is_active'])]
class BookingOption extends Model
{
    /** @use HasFactory<BookingOptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BookingOptionType::class,
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    #[Scope]
    protected function ofType(Builder $query, BookingOptionType $type): void
    {
        $query->where('type', $type->value);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** The id tiebreaker keeps order deterministic, same as Runner::ordered(). */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** The label in the page language. The admin calls label_en directly. */
    protected function label(): Attribute
    {
        return Attribute::get(
            fn (): string => app()->getLocale() === 'ms' ? $this->label_ms : $this->label_en,
        );
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, $this->type->column());
    }

    public function isInUse(): bool
    {
        return $this->bookings()->exists();
    }
}
