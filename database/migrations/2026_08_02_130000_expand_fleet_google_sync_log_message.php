<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('fleet_google_sync_logs')
            || !Schema::hasColumn(
                'fleet_google_sync_logs',
                'message'
            )
        ) {
            return;
        }

        Schema::table(
            'fleet_google_sync_logs',
            function (Blueprint $table): void {
                $table
                    ->longText('message')
                    ->nullable()
                    ->change();
            }
        );
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('fleet_google_sync_logs')
            || !Schema::hasColumn(
                'fleet_google_sync_logs',
                'message'
            )
        ) {
            return;
        }

        Schema::table(
            'fleet_google_sync_logs',
            function (Blueprint $table): void {
                $table
                    ->text('message')
                    ->nullable()
                    ->change();
            }
        );
    }
};
