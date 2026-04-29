# Spinner Examples

This file gives concrete examples of how to build real boxes with realistic admin settings and item pools.

Use it with:

- [SPINNER_SYSTEM_GUIDE.md](e:\Outsider Work\giveaways\SPINNER_SYSTEM_GUIDE.md)
- [SPINNER_RECOMMENDED_DEFAULTS.md](e:\Outsider Work\giveaways\SPINNER_RECOMMENDED_DEFAULTS.md)

## 1. Starter Box Example

### Purpose

- welcome users
- low-risk first experience
- make first spins feel active and understandable

### Box Setup

- Name: `Starter Spinner`
- Slug: `starter-spinner`
- Price credits: `4`
- Active: `on`
- Real-money credits only: `off`

### Economy Policy

- Target RTP min: `30`
- Target RTP max: `55`
- High-tier threshold: `25`
- Onboarding max spins: `4`
- Onboarding age hours: `72`
- Pity after spins: `3`
- Pity multiplier: `1.75`
- Daily progress after spins: `5`
- Daily progress multiplier: `1.15`
- Daily progress cap: `1`
- Jackpot max wins/day: `0`
- Jackpot cooldown spins: `0`
- Allowed credit sources: `PROMO`, `SALE`, `REAL MONEY`
- Onboarding item types: `Sticker`, `Coupon`
- Enable jackpot-tier items: `off`

### Suggested Items

1. Starter Sticker A
- Item type: `Sticker`
- Value tier: `Low`
- Drop weight: `250`
- Estimated value: `0.30`
- Sell value: `0.10`
- Onboarding only: `on`
- Daily limit: `2`
- Max repeat/day: `2`

2. Welcome Coupon A
- Item type: `Coupon`
- Value tier: `Low`
- Drop weight: `220`
- Estimated value: `0.40`
- Sell value: `0.15`
- Onboarding only: `on`
- Daily limit: `2`
- Max repeat/day: `2`

3. Basic Digital Reward
- Item type: `Digital`
- Value tier: `Mid`
- Drop weight: `50`
- Estimated value: `2.00`
- Sell value: `1.00`

4. Nice Bonus Reward
- Item type: `Digital`
- Value tier: `Mid`
- Drop weight: `20`
- Estimated value: `4.00`
- Sell value: `2.00`

### User Experience

- first spins mostly feel safe
- no jackpot pressure
- low risk for promo users

## 2. Rivals Box Example

### Purpose

- main traffic box
- regular player engagement
- balanced day-to-day spinner

### Box Setup

- Name: `Rivals`
- Slug: `rivals`
- Price credits: `10`
- Active: `on`
- Real-money credits only: `off`

### Economy Policy

- Target RTP min: `35`
- Target RTP max: `70`
- High-tier threshold: `100`
- Onboarding max spins: `3`
- Onboarding age hours: `48`
- Pity after spins: `3`
- Pity multiplier: `2`
- Daily progress after spins: `6`
- Daily progress multiplier: `1.20`
- Daily progress cap: `2`
- Jackpot max wins/day: `0`
- Jackpot cooldown spins: `0`
- Allowed credit sources: `PROMO`, `SALE`, `REAL MONEY`
- Onboarding item types: `Sticker`, `Coupon`
- Enable jackpot-tier items: `off`

### Suggested Items

1. Rival Sticker
- Type: `Sticker`
- Tier: `Low`
- Weight: `180`
- Sell value: `0.25`
- Onboarding only: `on`

2. Rival Coupon
- Type: `Coupon`
- Tier: `Low`
- Weight: `160`
- Sell value: `0.40`
- Onboarding only: `on`

3. Character Artwork Pack
- Type: `Digital`
- Tier: `Mid`
- Weight: `60`
- Sell value: `2.50`

4. Weapon Skin Card
- Type: `Digital`
- Tier: `Mid`
- Weight: `40`
- Sell value: `4.00`

5. Elite Collectible
- Type: `Physical`
- Tier: `High`
- Weight: `6`
- Sell value: `18.00`
- Min account age hours: `24`

### User Experience

- normal box for broad users
- onboarding still helps first-time users
- premium outcome exists without full jackpot mode

## 3. Premium Gaming Box Example

### Purpose

- higher-spend users
- premium rewards
- more serious box economics

### Box Setup

- Name: `Premium Gaming Box`
- Slug: `premium-gaming-box`
- Price credits: `50`
- Active: `on`
- Real-money credits only: `off` or `on` depending on strategy

### Economy Policy

- Target RTP min: `40`
- Target RTP max: `80`
- High-tier threshold: `250`
- Onboarding max spins: `1`
- Onboarding age hours: `24`
- Pity after spins: `4`
- Pity multiplier: `1.8`
- Daily progress after spins: `5`
- Daily progress multiplier: `1.1`
- Daily progress cap: `1`
- Jackpot max wins/day: `1`
- Jackpot cooldown spins: `15`
- Allowed credit sources: `SALE`, `REAL MONEY`
- Onboarding item types: none or minimal
- Enable jackpot-tier items: `optional`

### Suggested Items

1. Basic Gaming Voucher
- Type: `Coupon`
- Tier: `Low`
- Weight: `120`
- Sell value: `5.00`

2. Premium Digital Bundle
- Type: `Digital`
- Tier: `Mid`
- Weight: `45`
- Sell value: `20.00`

3. High-End Peripheral
- Type: `Physical`
- Tier: `High`
- Weight: `8`
- Sell value: `65.00`
- Min real spend: `25`
- Min account age hours: `24`
- Eligible sources: `SALE`, `REAL MONEY`

4. RTX-Class Prize
- Type: `Physical`
- Tier: `High`
- Weight: `2`
- Sell value: `150.00`
- Min real spend: `50`
- Min account age hours: `72`
- Eligible sources: `REAL MONEY`

### User Experience

- stronger value feeling
- still controlled
- not all users qualify equally for top items

## 4. Dream Jackpot Box Example

### Purpose

- aspirational top-end box
- rare high-hype reward
- fully controlled premium product

### Box Setup

- Name: `Dream Jackpot Box`
- Slug: `dream-jackpot-box`
- Price credits: `100`
- Active: `on`
- Real-money credits only: `on`

### Economy Policy

- Target RTP min: `25`
- Target RTP max: `60`
- High-tier threshold: `250`
- Onboarding max spins: `0`
- Onboarding age hours: `0`
- Pity after spins: `6`
- Pity multiplier: `1.3`
- Daily progress after spins: `10`
- Daily progress multiplier: `1.05`
- Daily progress cap: `1`
- Jackpot max wins/day: `1`
- Jackpot cooldown spins: `50`
- Allowed credit sources: `REAL MONEY`
- Onboarding item types: none
- Enable jackpot-tier items: `on`

### Suggested Items

1. Low Consolation Reward
- Type: `Digital`
- Tier: `Low`
- Weight: `250`
- Sell value: `8.00`

2. Mid Reward A
- Type: `Digital`
- Tier: `Mid`
- Weight: `60`
- Sell value: `30.00`

3. Mid Reward B
- Type: `Physical`
- Tier: `Mid`
- Weight: `30`
- Sell value: `45.00`

4. High Reward A
- Type: `Physical`
- Tier: `High`
- Weight: `6`
- Sell value: `140.00`
- Min real spend: `75`
- Min account age hours: `24`

5. Jackpot Reward
- Type: `Jackpot`
- Tier: `Jackpot`
- Weight: `1`
- Sell value: `15000.00`
- Min real spend: `100`
- Min account age hours: `24`
- Lifetime limit: `1`
- Daily limit: `1`
- Max repeat/day: `1`
- Eligible sources: `REAL MONEY`

### User Experience

- expensive
- rare
- exciting
- tightly protected

## 5. Example Rules For Different User Types

## New User

If user is:

- account age under onboarding hours
- onboarding spins used less than onboarding max spins

Then:

- onboarding-only items are eligible
- onboarding types get chance boost
- returning-user-only items are blocked

## Existing User

If user is outside onboarding:

- onboarding-only items are blocked
- returning-user-only items can appear
- normal pool becomes dominant

## High-Spend User

If user has enough real spend:

- premium items with `min_real_spend` become eligible

## Promo-Funded User

If spin is funded by promo:

- items restricted to `REAL MONEY` do not appear

## 6. Suggested First Production Setup

If you are launching this system for the first time, use:

1. One starter box
2. One standard daily box
3. No jackpot box in first phase

Why:

- easier balancing
- easier support
- easier audit review

Then later:

4. add one premium box
5. only after that consider jackpot box

## 7. Final Advice

If you are unsure:

- start with cheaper boxes
- keep jackpot off
- keep premium items tied to `REAL MONEY` or `MIN REAL SPEND`
- keep onboarding simple

The safest early system is usually:

- one beginner-friendly box
- one normal monetized box
- no dream jackpot until your economy data becomes stable
