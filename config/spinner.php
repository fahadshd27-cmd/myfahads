<?php

return [
    /*
     * Simple economy profiles.
     *
     * Values are percentages of the box price (sell-value payout target).
     * Example: box price 100, 10%..30% => choose items around 10..30 sell value.
     */
    'simple_profiles' => [
        'safe' => [
            // Band basis:
            // - net_loss_after_cost: bands apply to (netLoss + currentBoxPrice)
            // - box_price: bands apply to currentBoxPrice (old behavior)
            'band_basis' => 'net_loss_after_cost',
            'max_payout_percent' => 60,
            'first_spin' => [3, 12],
            'first_box_spin' => [8, 22],
            'normal_spin' => [8, 22],
            'repeat_spin' => [3, 10],
            'recovery_spin' => [22, 45],
            'repeat_same_box_after_spins' => 3,
            'recovery_after_net_loss_percent' => 175,
            'window_hours' => 24,
        ],
        'normal' => [
            'band_basis' => 'net_loss_after_cost',
            'max_payout_percent' => 70,
            'first_spin' => [5, 20],
            'first_box_spin' => [10, 35],
            'normal_spin' => [10, 30],
            'repeat_spin' => [5, 15],
            'recovery_spin' => [35, 60],
            'repeat_same_box_after_spins' => 3,
            'recovery_after_net_loss_percent' => 150,
            'window_hours' => 24,
        ],
        'aggressive' => [
            'band_basis' => 'net_loss_after_cost',
            'max_payout_percent' => 80,
            'first_spin' => [8, 25],
            'first_box_spin' => [15, 45],
            'normal_spin' => [12, 35],
            'repeat_spin' => [6, 18],
            'recovery_spin' => [40, 70],
            'repeat_same_box_after_spins' => 3,
            'recovery_after_net_loss_percent' => 125,
            'window_hours' => 24,
        ],
    ],
];
