<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('country', 2)->nullable()->after('display_name');
            $table->string('billing_first_name')->nullable()->after('country');
            $table->string('billing_last_name')->nullable()->after('billing_first_name');
            $table->string('billing_address')->nullable()->after('billing_last_name');
            $table->string('status')->default('active')->after('remember_token');
            $table->boolean('is_admin')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'country',
                'billing_first_name',
                'billing_last_name',
                'billing_address',
                'status',
                'is_admin',
            ]);
        });
    }
};
