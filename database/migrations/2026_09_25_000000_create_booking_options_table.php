<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_options', function (Blueprint $table) {
            $table->id();
            // faculty | robe_size | convocation_session (App\Enums\BookingOptionType).
            $table->string('type', 30);
            // Both languages on the row: the public form is bilingual and these are not in
            // lang files, so the parity test cannot police them. The admin form requires both.
            $table->string('label_en', 120);
            $table->string('label_ms', 120);
            // Display order. Deliberately NOT unique, same reasoning as runners.position.
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'label_en']);
            $table->index(['type', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_options');
    }
};
