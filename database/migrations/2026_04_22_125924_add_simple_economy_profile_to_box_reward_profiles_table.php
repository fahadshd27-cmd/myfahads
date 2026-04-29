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
        Schema::table('box_reward_profiles', function (Blueprint $table) {
            $table->string('economy_mode')->default('advanced')->after('mystery_box_id');
            $table->string('economy_profile')->nullable()->after('economy_mode');

            $table->unsignedSmallInteger('window_hours')->default(24)->after('economy_profile');
            $table->decimal('max_payout_percent', 5, 2)->default(70)->after('window_hours');

            $table->decimal('first_spin_min_percent', 5, 2)->default(5)->after('max_payout_percent');
            $table->decimal('first_spin_max_percent', 5, 2)->default(20)->after('first_spin_min_percent');
            $table->decimal('first_box_spin_min_percent', 5, 2)->default(10)->after('first_spin_max_percent');
            $table->decimal('first_box_spin_max_percent', 5, 2)->default(35)->after('first_box_spin_min_percent');
            $table->decimal('normal_spin_min_percent', 5, 2)->default(10)->after('first_box_spin_max_percent');
            $table->decimal('normal_spin_max_percent', 5, 2)->default(30)->after('normal_spin_min_percent');
            $table->decimal('repeat_spin_min_percent', 5, 2)->default(5)->after('normal_spin_max_percent');
            $table->decimal('repeat_spin_max_percent', 5, 2)->default(15)->after('repeat_spin_min_percent');
            $table->decimal('recovery_spin_min_percent', 5, 2)->default(35)->after('repeat_spin_max_percent');
            $table->decimal('recovery_spin_max_percent', 5, 2)->default(60)->after('recovery_spin_min_percent');

            $table->unsignedSmallInteger('repeat_same_box_after_spins')->default(3)->after('recovery_spin_max_percent');
            $table->decimal('recovery_after_net_loss_percent', 5, 2)->default(150)->after('repeat_same_box_after_spins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_reward_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'economy_mode',
                'economy_profile',
                'window_hours',
                'max_payout_percent',
                'first_spin_min_percent',
                'first_spin_max_percent',
                'first_box_spin_min_percent',
                'first_box_spin_max_percent',
                'normal_spin_min_percent',
                'normal_spin_max_percent',
                'repeat_spin_min_percent',
                'repeat_spin_max_percent',
                'recovery_spin_min_percent',
                'recovery_spin_max_percent',
                'repeat_same_box_after_spins',
                'recovery_after_net_loss_percent',
            ]);
        });
    }
};
