<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Optional: only used to email the graduate their confirmation. WhatsApp stays the
            // real channel, so this is never required and never unique.
            $table->string('email', 254)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
