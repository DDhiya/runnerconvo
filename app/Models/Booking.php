<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'full_name', 'matric_no', 'phone', 'email',
    'faculty_id', 'robe_size_id', 'convocation_session_id', 'programme_level',
    'delivery_method', 'delivery_address', 'notes', 'locale',
    'status', 'runner_id', 'admin_notes',
    'amount_sen', 'paid_at', 'payment_method', 'payment_reference',
    'confirmed_at', 'collected_at', 'handed_over_at', 'cancelled_at',
    'consented_at', 'privacy_version',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /** No 0/O, 1/I/L: a reference survives being read out over a phone call. */
    private const REFERENCE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Diploma/Bachelor: the confirmed XXyyaaa format (course letters, intake year, running number from 001). */
    private const MATRIC_STRICT = '/^[A-Z]{2}\d{2}(?!000)\d{3}$/';

    /** TODO(matric): Master/PhD format is unconfirmed, so accept any plausible alphanumeric until it is. */
    private const MATRIC_LOOSE = '/^[A-Z0-9]{5,15}$/';

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (! $booking->reference) {
                $booking->reference = static::generateReference();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'amount_sen' => 'integer',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'collected_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'consented_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
            $reference = 'JP-'.$suffix;
            // The unique index is the backstop if two requests pick the same one.
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /** "cb 22-001" becomes "CB22001". */
    public static function normaliseMatric(string $value): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', $value) ?? '');
    }

    /** The one place the matric rule branches on programme level. */
    public static function matricRuleFor(?string $level): string
    {
        $strict = in_array($level, ['diploma', 'bachelor'], true);

        return 'regex:'.($strict ? self::MATRIC_STRICT : self::MATRIC_LOOSE);
    }

    /** Same backstop role as Runner::phone(); the requests normalise before validating. */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Runner::normalisePhone($value),
        );
    }

    protected function matricNo(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => static::normaliseMatric($value),
        );
    }

    /** Trimmed and lowercased; blank becomes null so "no email" is one state, not two. */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => filled($value) ? strtolower(trim($value)) : null,
        );
    }

    /** The registrant own WhatsApp chat. */
    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(fn (): string => 'https://wa.me/'.$this->phone);
    }

    /** Everything except cancelled bookings. */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('status', '!=', BookingStatus::Cancelled->value);
    }

    /** Free-text search over reference, name, matric and phone. */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        // No LIKE escaping: SQLite has no default escape character, and a stray % or _
        // in an admin's own search box only makes the match broader, never unsafe
        // (the term is a bound parameter).
        $like = '%'.$term.'%';

        $query->where(function (Builder $q) use ($like, $term) {
            $q->where('reference', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('matric_no', 'like', '%'.static::normaliseMatric($term).'%');

            // "012-345" should find 6012345...: normalise phone-shaped terms the same way
            // the stored value was.
            if (preg_match('/^[\d\s+\-]{3,}$/', $term)) {
                $q->orWhere('phone', 'like', '%'.Runner::normalisePhone($term).'%');
            }
        });
    }

    /** Move to a stage, stamping its timestamp the first time only. */
    public function moveTo(BookingStatus $status): void
    {
        $this->status = $status;

        $column = $status->timestampColumn();
        if ($column && $this->{$column} === null) {
            $this->{$column} = now();
        }
    }

    public function isCod(): bool
    {
        return $this->delivery_method === 'cod';
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /** @return BelongsTo<BookingOption, $this> */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(BookingOption::class, 'faculty_id');
    }

    /** @return BelongsTo<BookingOption, $this> */
    public function robeSize(): BelongsTo
    {
        return $this->belongsTo(BookingOption::class, 'robe_size_id');
    }

    /** @return BelongsTo<BookingOption, $this> */
    public function convocationSession(): BelongsTo
    {
        return $this->belongsTo(BookingOption::class, 'convocation_session_id');
    }

    /** @return BelongsTo<Runner, $this> */
    public function runner(): BelongsTo
    {
        return $this->belongsTo(Runner::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
