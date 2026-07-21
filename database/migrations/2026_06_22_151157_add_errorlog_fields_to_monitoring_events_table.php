<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitoring_events', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->after('evidence_link');
            $table->string('event_status')->nullable()->after('ticket_number');
            $table->string('follow_up_status')->nullable()->after('event_status');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_events', function (Blueprint $table) {
            $table->dropColumn(['ticket_number', 'event_status', 'follow_up_status']);
        });
    }
};
