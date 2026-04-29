<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->nullable()->after('country');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('real_money_credits', 12, 2)->default(0)->after('balance_credits');
            $table->decimal('promo_credits', 12, 2)->default(0)->after('real_money_credits');
            $table->decimal('sale_credits', 12, 2)->default(0)->after('promo_credits');
        });

        DB::table('users')->whereNull('timezone')->update([
            'timezone' => config('app.timezone', 'UTC'),
        ]);

        DB::table('wallets')->update([
            'real_money_credits' => DB::raw('balance_credits'),
        ]);

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('credit_source')->nullable()->after('type');
            $table->string('bucket')->nullable()->after('credit_source');
            $table->json('funding_bucket_before')->nullable()->after('balance_after');
            $table->json('funding_bucket_after')->nullable()->after('funding_bucket_before');
            $table->json('origin_context')->nullable()->after('idempotency_key');
        });

        Schema::table('mystery_boxes', function (Blueprint $table) {
            $table->boolean('requires_real_money_only')->default(false)->after('price_credits');
        });

        Schema::table('mystery_box_items', function (Blueprint $table) {
            $table->string('item_type')->default('digital')->after('image');
            $table->string('value_tier')->default('low')->after('rarity');
            $table->boolean('is_onboarding_only')->default(false)->after('value_tier');
            $table->boolean('is_returning_user_only')->default(false)->after('is_onboarding_only');
            $table->json('eligible_credit_sources')->nullable()->after('is_returning_user_only');
            $table->json('eligible_spin_ranges')->nullable()->after('eligible_credit_sources');
            $table->unsignedInteger('daily_limit')->nullable()->after('eligible_spin_ranges');
            $table->unsignedInteger('lifetime_limit')->nullable()->after('daily_limit');
            $table->unsignedInteger('min_account_age_hours')->nullable()->after('lifetime_limit');
            $table->decimal('min_real_spend', 12, 2)->nullable()->after('min_account_age_hours');
            $table->unsignedInteger('max_repeat_per_day')->nullable()->after('min_real_spend');
            $table->timestamp('archived_at')->nullable()->after('updated_at');

            $table->index(['mystery_box_id', 'item_type']);
            $table->index(['mystery_box_id', 'value_tier']);
        });

        Schema::table('user_inventory_items', function (Blueprint $table) {
            $table->json('item_snapshot')->nullable()->after('state');
            $table->string('claim_status')->nullable()->after('item_snapshot');
            $table->timestamp('claimable_at')->nullable()->after('sell_amount_credits');
            $table->timestamp('claimed_at')->nullable()->after('claimable_at');
        });

        Schema::create('box_reward_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mystery_box_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('target_rtp_min', 5, 2)->default(30);
            $table->decimal('target_rtp_max', 5, 2)->default(85);
            $table->json('eligible_credit_sources')->nullable();
            $table->unsignedInteger('onboarding_max_spins')->default(3);
            $table->unsignedInteger('onboarding_max_account_age_hours')->default(48);
            $table->json('onboarding_item_types')->nullable();
            $table->unsignedInteger('pity_after_spins')->default(3);
            $table->decimal('pity_multiplier', 8, 4)->default(2);
            $table->unsignedInteger('daily_progress_after_spins')->default(6);
            $table->decimal('daily_progress_multiplier', 8, 4)->default(1.2);
            $table->unsignedInteger('daily_progress_cap')->default(2);
            $table->boolean('jackpot_enabled')->default(true);
            $table->unsignedInteger('jackpot_max_wins_per_day')->default(1);
            $table->unsignedInteger('jackpot_cooldown_spins')->default(0);
            $table->decimal('high_tier_value_threshold', 12, 2)->default(250);
            $table->timestamps();
        });

        Schema::create('user_box_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mystery_box_id')->constrained()->cascadeOnDelete();
            $table->string('local_day')->nullable();
            $table->unsignedInteger('daily_spin_count')->default(0);
            $table->unsignedInteger('lifetime_spin_count')->default(0);
            $table->unsignedInteger('onboarding_spins_used')->default(0);
            $table->unsignedInteger('consecutive_low_tier_spins')->default(0);
            $table->unsignedInteger('progression_segment')->default(0);
            $table->unsignedInteger('high_tier_wins_today')->default(0);
            $table->unsignedInteger('jackpot_wins_today')->default(0);
            $table->unsignedInteger('last_jackpot_spin_index')->nullable();
            $table->timestamp('last_spin_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mystery_box_id']);
            $table->index(['mystery_box_id', 'local_day']);
        });

        Schema::create('user_box_item_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mystery_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mystery_box_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('won_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedInteger('saved_count')->default(0);
            $table->unsignedInteger('claimed_count')->default(0);
            $table->unsignedInteger('won_today_count')->default(0);
            $table->string('last_local_day')->nullable();
            $table->timestamp('last_won_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mystery_box_item_id']);
            $table->index(['user_id', 'mystery_box_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_box_item_stats');
        Schema::dropIfExists('user_box_progress');
        Schema::dropIfExists('box_reward_profiles');

        Schema::table('user_inventory_items', function (Blueprint $table) {
            $table->dropColumn(['item_snapshot', 'claim_status', 'claimable_at', 'claimed_at']);
        });

        Schema::table('mystery_box_items', function (Blueprint $table) {
            $table->dropIndex(['mystery_box_id', 'item_type']);
            $table->dropIndex(['mystery_box_id', 'value_tier']);
            $table->dropColumn([
                'item_type',
                'value_tier',
                'is_onboarding_only',
                'is_returning_user_only',
                'eligible_credit_sources',
                'eligible_spin_ranges',
                'daily_limit',
                'lifetime_limit',
                'min_account_age_hours',
                'min_real_spend',
                'max_repeat_per_day',
                'archived_at',
            ]);
        });

        Schema::table('mystery_boxes', function (Blueprint $table) {
            $table->dropColumn('requires_real_money_only');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'credit_source',
                'bucket',
                'funding_bucket_before',
                'funding_bucket_after',
                'origin_context',
            ]);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['real_money_credits', 'promo_credits', 'sale_credits']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
