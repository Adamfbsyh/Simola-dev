<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable(
                'master_fleet_audits'
            )
        ) {
            return;
        }

        Schema::create(
            'master_fleet_audits',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table
                    ->timestamp(
                        'occurred_at'
                    )
                    ->index();

                $table
                    ->unsignedBigInteger(
                        'user_id'
                    )
                    ->nullable()
                    ->index();

                $table
                    ->string(
                        'user_name',
                        150
                    )
                    ->nullable();

                $table
                    ->string(
                        'user_email',
                        190
                    )
                    ->nullable();

                $table
                    ->string(
                        'fleet_type',
                        32
                    )
                    ->nullable()
                    ->index();

                $table
                    ->string(
                        'module',
                        80
                    )
                    ->index();

                $table
                    ->string(
                        'action',
                        80
                    )
                    ->index();

                $table
                    ->string(
                        'route_name',
                        160
                    )
                    ->nullable()
                    ->index();

                $table
                    ->string(
                        'subject_type',
                        120
                    )
                    ->nullable();

                $table
                    ->string(
                        'subject_id',
                        80
                    )
                    ->nullable();

                $table
                    ->string(
                        'subject_label',
                        255
                    )
                    ->nullable()
                    ->index();

                $table->text(
                    'description'
                );

                $table
                    ->json(
                        'before_data'
                    )
                    ->nullable();

                $table
                    ->json(
                        'after_data'
                    )
                    ->nullable();

                $table
                    ->json(
                        'meta'
                    )
                    ->nullable();

                $table
                    ->string(
                        'ip_address',
                        45
                    )
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'fleet_type',
                    'occurred_at',
                ]);

                $table->index([
                    'module',
                    'occurred_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'master_fleet_audits'
        );
    }
};
