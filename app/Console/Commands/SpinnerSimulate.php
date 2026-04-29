<?php

namespace App\Console\Commands;

use App\Models\MysteryBox;
use App\Models\User;
use App\Services\InventoryActionService;
use App\Services\SpinService;
use App\Services\WalletService;
use Database\Seeders\SpinnerDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SpinnerSimulate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spinner:simulate
                            {--seed : Seed demo boxes and users before simulating}
                            {--runs=1 : Number of simulation runs}
                            {--output= : Optional output directory (defaults to storage/app/reports)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate deposit → spin → win → sell flows across multiple boxes and produce a report.';

    /**
     * Execute the console command.
     */
    public function handle(SpinService $spins, InventoryActionService $inventory, WalletService $wallets): int
    {
        if ($this->option('seed')) {
            (new SpinnerDemoSeeder)->run();
            $this->info('Seeded demo boxes/users.');
        }

        $users = User::query()
            ->whereIn('email', [
                'new1@giveaways.test',
                'new2@giveaways.test',
                'old1@giveaways.test',
                'old2@giveaways.test',
                'old3@giveaways.test',
            ])
            ->get()
            ->keyBy('email');

        $boxes = MysteryBox::query()
            ->whereIn('slug', [
                'quick-2-box',
                'starter-spinner',
                'casual-10-box',
                'rivals-20-box',
                'pro-50-box',
                'elite-100-box',
                'mega-200-box',
                'titan-500-box',
            ])
            ->with('rewardProfile')
            ->get()
            ->keyBy('slug');

        $plan = [
            'new1@giveaways.test' => [
                ['box' => 'quick-2-box', 'spins' => 2],
                ['box' => 'starter-spinner', 'spins' => 2],
                ['box' => 'casual-10-box', 'spins' => 1],
            ],
            'new2@giveaways.test' => [
                ['box' => 'quick-2-box', 'spins' => 1],
            ],
            'old1@giveaways.test' => [
                ['box' => 'rivals-20-box', 'spins' => 3],
                ['box' => 'pro-50-box', 'spins' => 1],
            ],
            'old2@giveaways.test' => [
                ['box' => 'pro-50-box', 'spins' => 2],
                ['box' => 'elite-100-box', 'spins' => 1],
                ['box' => 'mega-200-box', 'spins' => 1],
            ],
            'old3@giveaways.test' => [
                ['box' => 'mega-200-box', 'spins' => 2],
                ['box' => 'titan-500-box', 'spins' => 1],
            ],
        ];

        $runs = max(1, (int) $this->option('runs'));
        $summaryRows = [];
        $report = [
            'generated_at' => now()->toIso8601String(),
            'runs' => $runs,
            'users' => [],
        ];

        foreach (range(1, $runs) as $runIndex) {
            foreach ($plan as $email => $steps) {
                $user = $users->get($email);
                if (! $user) {
                    $this->warn("Missing seeded user: {$email}");

                    continue;
                }

                $userKey = $email.'#'.$runIndex;
                $report['users'][$userKey] = [
                    'email' => $email,
                    'run' => $runIndex,
                    'spins' => [],
                ];

                foreach ($steps as $step) {
                    $box = $boxes->get((string) $step['box']);
                    if (! $box) {
                        $this->warn('Missing seeded box: '.$step['box']);

                        continue;
                    }

                    for ($i = 0; $i < (int) $step['spins']; $i++) {
                        try {
                            $spin = $spins->spin($user->fresh(), $box, '');
                            $inventoryItem = $spin->inventoryItem;

                            // Default simulation behavior: sell immediately (creates sale credits that can be reused).
                            if ($inventoryItem) {
                                $inventory->sell($user->fresh(), $inventoryItem, 'sim-'.$spin->id);
                            }

                            $report['users'][$userKey]['spins'][] = [
                                'box' => $box->slug,
                                'cost' => (float) $spin->cost_credits,
                                'winner' => [
                                    'name' => $spin->resultItem?->name,
                                    'type' => $spin->resultItem?->item_type,
                                    'sell' => (float) ($spin->resultItem?->sell_value_credits ?? 0),
                                ],
                                'funding' => data_get($spin->meta, 'funding', []),
                            ];
                        } catch (\Throwable $e) {
                            $report['users'][$userKey]['spins'][] = [
                                'box' => $box->slug,
                                'error' => $e->getMessage(),
                            ];
                            break;
                        }
                    }
                }

                $balance = $wallets->spendableBalance($user->fresh());
                $spent = collect($report['users'][$userKey]['spins'])
                    ->filter(fn ($s) => isset($s['cost']))
                    ->sum('cost');
                $returned = collect($report['users'][$userKey]['spins'])
                    ->filter(fn ($s) => isset($s['winner']['sell']))
                    ->sum(fn ($s) => (float) data_get($s, 'winner.sell', 0));

                $summaryRows[] = [
                    $email,
                    (string) $runIndex,
                    (string) count($report['users'][$userKey]['spins']),
                    '$'.number_format((float) $spent, 2),
                    '$'.number_format((float) $returned, 2),
                    '$'.number_format((float) ($spent - $returned), 2),
                    '$'.number_format((float) $balance['sale'], 2),
                    '$'.number_format((float) $balance['real_money'], 2),
                ];
            }
        }

        $this->table(
            ['User', 'Run', 'Spins', 'Spent', 'Returned', 'Net', 'Sale Bal', 'Real Bal'],
            $summaryRows,
        );

        $outputDir = (string) ($this->option('output') ?: storage_path('app/reports'));
        File::ensureDirectoryExists($outputDir);

        $stamp = now()->format('Ymd_His').'_'.Str::lower(Str::random(4));
        $jsonPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."spinner_simulation_{$stamp}.json";

        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info('Wrote report: '.$jsonPath);

        return self::SUCCESS;
    }
}
