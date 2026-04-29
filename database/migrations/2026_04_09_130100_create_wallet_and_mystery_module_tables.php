<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance_credits', 12, 2)->default(0);
            $table->decimal('locked_credits', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->unique(['type', 'idempotency_key']);
        });

        Schema::create('deposit_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('gateway');
            $table->string('mode');
            $table->decimal('amount_credits', 12, 2);
            $table->string('status')->default('created');
            $table->string('external_id')->nullable()->index();
            $table->string('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique(['gateway', 'external_id']);
        });

        Schema::create('deposit_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('event_id')->nullable();
            $table->string('external_id')->nullable();
            $table->foreignId('deposit_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('signature_header')->nullable();
            $table->boolean('is_signature_valid')->default(false);
            $table->boolean('is_processed')->default(false);
            $table->json('payload');
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
            $table->index(['gateway', 'external_id']);
        });

        Schema::create('mystery_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->decimal('price_credits', 12, 2);
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mystery_box_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mystery_box_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('rarity')->default('common');
            $table->unsignedBigInteger('drop_weight')->default(1);
            $table->decimal('estimated_value_credits', 12, 2)->default(0);
            $table->decimal('sell_value_credits', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['mystery_box_id', 'is_active']);
        });

        Schema::create('box_config_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mystery_box_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamps();

            $table->unique(['mystery_box_id', 'version']);
        });

        Schema::create('box_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mystery_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_config_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('result_item_id')->nullable()->constrained('mystery_box_items')->nullOnDelete();
            $table->decimal('cost_credits', 12, 2);
            $table->string('status')->default('resolved');
            $table->string('server_seed_hash');
            $table->string('server_seed_plain');
            $table->string('client_seed');
            $table->unsignedBigInteger('nonce')->default(1);
            $table->decimal('roll_value', 18, 8)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_spin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mystery_box_item_id')->constrained()->cascadeOnDelete();
            $table->string('state')->default('pending');
            $table->decimal('sell_amount_credits', 12, 2)->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_inventory_items');
        Schema::dropIfExists('box_spins');
        Schema::dropIfExists('box_config_versions');
        Schema::dropIfExists('mystery_box_items');
        Schema::dropIfExists('mystery_boxes');
        Schema::dropIfExists('deposit_webhook_events');
        Schema::dropIfExists('deposit_orders');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('app_settings');
    }
};
