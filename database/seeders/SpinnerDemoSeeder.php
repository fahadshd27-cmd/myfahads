<?php

namespace Database\Seeders;

use App\Models\BoxRewardProfile;
use App\Models\MysteryBox;
use App\Models\MysteryBoxItem;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SpinnerDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate([
            'email' => 'admin@giveaways.test',
        ], [
            'name' => 'Admin User',
            'display_name' => 'Admin User',
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_admin' => true,
            'email_verified_at' => now(),
            'country' => 'US',
            'timezone' => 'America/New_York',
        ]);

        /** @var WalletService $wallets */
        $wallets = app(WalletService::class);

        $users = [
            ['email' => 'new1@giveaways.test', 'name' => 'New User 1', 'age_days' => 0, 'deposit' => 10],
            ['email' => 'new2@giveaways.test', 'name' => 'New User 2', 'age_days' => 0, 'deposit' => 0],
            ['email' => 'old1@giveaways.test', 'name' => 'Old User 1', 'age_days' => 14, 'deposit' => 50],
            ['email' => 'old2@giveaways.test', 'name' => 'Old User 2', 'age_days' => 30, 'deposit' => 200],
            ['email' => 'old3@giveaways.test', 'name' => 'Old User 3', 'age_days' => 90, 'deposit' => 500],
        ];

        foreach ($users as $attrs) {
            $user = User::query()->updateOrCreate([
                'email' => $attrs['email'],
            ], [
                'name' => $attrs['name'],
                'display_name' => $attrs['name'],
                'password' => Hash::make('password'),
                'status' => 'active',
                'is_admin' => false,
                'email_verified_at' => now(),
                'country' => 'US',
                'timezone' => 'America/New_York',
            ]);

            $user->created_at = now()->subDays((int) $attrs['age_days']);
            $user->saveQuietly();

            $wallets->ensureWallet($user);

            $deposit = (float) $attrs['deposit'];
            if ($deposit > 0) {
                $idempotencyKey = 'seed-deposit-'.$user->id;

                $alreadySeeded = WalletTransaction::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'deposit_credit')
                    ->where('idempotency_key', $idempotencyKey)
                    ->exists();

                if ($alreadySeeded) {
                    continue;
                }

                $wallets->credit(
                    user: $user,
                    amount: $deposit,
                    type: 'deposit_credit',
                    meta: ['seed' => true],
                    idempotencyKey: $idempotencyKey,
                    admin: $admin,
                    bucket: WalletService::BUCKET_REAL_MONEY,
                    creditSource: 'real_money',
                    originContext: ['kind' => 'seed'],
                );
            }
        }

        $boxes = [
            ['name' => 'Starter Spinner', 'price' => 4, 'profile' => 'normal'],
            ['name' => 'Quick $2 Box', 'price' => 2, 'profile' => 'safe'],
            ['name' => 'Casual $10 Box', 'price' => 10, 'profile' => 'normal'],
            ['name' => 'Rivals $20 Box', 'price' => 20, 'profile' => 'normal'],
            ['name' => 'Pro $50 Box', 'price' => 50, 'profile' => 'normal'],
            ['name' => 'Elite $100 Box', 'price' => 100, 'profile' => 'normal'],
            ['name' => 'Mega $200 Box', 'price' => 200, 'profile' => 'safe'],
            ['name' => 'Titan $500 Box', 'price' => 500, 'profile' => 'safe'],
            ['name' => 'Whale $1000 Box', 'price' => 1000, 'profile' => 'safe'],
            ['name' => 'Aggro $30 Box', 'price' => 30, 'profile' => 'aggressive'],
        ];

        foreach ($boxes as $i => $boxAttrs) {
            $slug = Str::slug($boxAttrs['name']);

            $box = MysteryBox::query()->updateOrCreate([
                'slug' => $slug,
            ], [
                'name' => $boxAttrs['name'],
                'description' => 'Demo box for economy simulation.',
                'price_credits' => (float) $boxAttrs['price'],
                'requires_real_money_only' => false,
                'is_active' => true,
                'sort_order' => $i,
            ]);

            BoxRewardProfile::query()->updateOrCreate([
                'mystery_box_id' => $box->id,
            ], [
                'economy_mode' => 'simple',
                'economy_profile' => $boxAttrs['profile'],
                'eligible_credit_sources' => ['promo', 'sale', 'real_money'],
                'jackpot_enabled' => true,
            ]);

            $price = (float) $box->price_credits;

            $itemDefs = [
                [
                    'name' => 'Starter Coupon',
                    'item_type' => 'coupon',
                    'drop_weight' => 700,
                    'sell_value_credits' => round($price * 0.05, 2),
                    'sort_order' => 0,
                ],
                [
                    'name' => 'Bonus Coupon',
                    'item_type' => 'coupon',
                    'drop_weight' => 600,
                    'sell_value_credits' => round($price * 0.07, 2),
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Digital Reward',
                    'item_type' => 'digital',
                    'drop_weight' => 120,
                    'sell_value_credits' => round($price * 0.25, 2),
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Physical Reward',
                    'item_type' => 'physical',
                    'drop_weight' => 12,
                    'sell_value_credits' => round($price * 0.55, 2),
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Jackpot Dream Prize',
                    'item_type' => 'jackpot',
                    'drop_weight' => 1,
                    'sell_value_credits' => round(max(80, $price * 35), 2),
                    'eligible_credit_sources' => ['real_money'],
                    'min_real_spend' => round(max(20, $price * 2), 2),
                    'min_account_age_hours' => 24,
                    'lifetime_limit' => 1,
                    'sort_order' => 4,
                ],
            ];

            foreach ($itemDefs as $def) {
                $def['estimated_value_credits'] = (float) $def['sell_value_credits'];
                MysteryBoxItem::query()->updateOrCreate([
                    'mystery_box_id' => $box->id,
                    'name' => $def['name'],
                ], array_merge($def, [
                    'mystery_box_id' => $box->id,
                    'is_active' => true,
                ]));
            }
        }
    }
}
