<?php

namespace App\Models;

use Database\Factories\RunnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'position', 'is_active'])]
class Runner extends Model
{
    /** @use HasFactory<RunnerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** Only runners the public should see. */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Display order. The id tiebreaker keeps the order deterministic even if two
     * rows somehow share a position — SQLite gives no stable order otherwise.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * Stored digits-only, no "+", matching config('jubahrunner.whatsapp_number').
     * The mutator is a backstop for seeders/tinker; the admin form normalises in
     * prepareForValidation() so the `unique` rule compares like with like.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => static::normalisePhone($value),
        );
    }

    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(fn (): string => 'https://wa.me/'.$this->phone);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** "+60 14-533 2637" and "014-533 2637" both become "60145332637". */
    public static function normalisePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return str_starts_with($digits, '0') ? '60'.substr($digits, 1) : $digits;
    }
}
