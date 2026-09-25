<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Public handle, JP- plus 6 chars from an unambiguous alphabet. Random, not
            // sequential: it goes into WhatsApp messages, so it must not reveal volume
            // or be guessable.
            $table->string('reference', 9)->unique();

            $table->string('full_name', 120);
            $table->string('matric_no', 20);   // uppercased, spaces/hyphens stripped
            // Digits only, 60..., same convention as runners.phone. NOT unique: one parent
            // may register two siblings from one number.
            $table->string('phone', 20);

            // Admin-managed lists. restrictOnDelete: an option any booking uses can only be
            // deactivated, never deleted, so no booking loses its label.
            $table->foreignId('faculty_id')->constrained('booking_options')->restrictOnDelete();
            $table->foreignId('robe_size_id')->constrained('booking_options')->restrictOnDelete();
            $table->foreignId('convocation_session_id')->constrained('booking_options')->restrictOnDelete();
            // Fixed set (diploma|bachelor|master|phd) in lang/*/register.php. Kept out of the
            // admin lists because the matric rule branches on it.
            $table->string('programme_level', 20);

            $table->string('delivery_method', 10);          // pickup | cod
            $table->text('delivery_address')->nullable();   // required when cod
            $table->text('notes')->nullable();              // the registrant's own note
            $table->string('locale', 2);                    // language they registered in

            $table->string('status', 20)->default('submitted');
            $table->foreignId('runner_id')->nullable()->constrained()->nullOnDelete();
            $table->text('admin_notes')->nullable();

            // Payment is tracked apart from fulfilment: COD pays at handover, so "paid"
            // cannot be a step in a straight line. A gateway webhook later fills these
            // same columns and nothing else changes.
            $table->unsignedInteger('amount_sen');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 20)->nullable();   // transfer | duitnow | cash | (gateway later)
            $table->string('payment_reference', 100)->nullable();

            // Set on first entry to each stage, kept if an admin later moves the booking back.
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // PDPA: when they agreed, and to which version of the notice.
            $table->timestamp('consented_at');
            $table->string('privacy_version', 20);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            // SQLite does not index FK columns on its own.
            $table->index('runner_id');
            $table->index('faculty_id');
            $table->index('robe_size_id');
            $table->index('convocation_session_id');
        });

        // One LIVE booking per matric number. Partial so a cancelled booking does not block
        // re-registering. The schema builder has no partial-index API for SQLite.
        DB::statement("CREATE UNIQUE INDEX bookings_matric_no_live_unique ON bookings (matric_no) WHERE status <> 'cancelled'");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
