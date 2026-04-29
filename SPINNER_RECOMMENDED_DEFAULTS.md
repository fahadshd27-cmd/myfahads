# Spinner Recommended Defaults

This file gives practical starting values for common box types.

These are not universal truths.
They are safe starting points so you do not have to guess from zero.

Use this together with:

- [SPINNER_SYSTEM_GUIDE.md](e:\Outsider Work\giveaways\SPINNER_SYSTEM_GUIDE.md)

## 1. How To Use This File

When creating a new box:

1. Choose the closest box profile below.
2. Copy the box policy values.
3. Build item pool using the suggested reward mix.
4. Adjust slowly after observing real behavior.

Do not change too many variables at once.

Best rule:

- first set reward structure
- then set sell values
- then set weights
- then test user experience

## 2. Important Principle

The most dangerous mistake is not item rarity.
It is bad sell-value economics.

Estimated value is mostly presentation.
Sell value is the real financial output.

So when tuning boxes:

- protect `sell_value_credits`
- use `estimated_value_credits` for display polish
- use strict source restrictions and spend limits for expensive items

## 3. Box Type: Cheap Starter Box

Use this when:

- user is new
- low-friction entry box
- onboarding or engagement box
- you want frequent activity and low risk

### Suggested Box Settings

- Price credits: `2` to `5`
- Real-money credits only: `off`
- Active: `on`

### Suggested Economy Policy

- Target RTP min: `25`
- Target RTP max: `55`
- High-tier threshold: `25`
- Onboarding max spins: `4`
- Onboarding age hours: `72`
- Pity after spins: `3`
- Pity multiplier: `1.5` to `2`
- Daily progress after spins: `5`
- Daily progress multiplier: `1.1` to `1.2`
- Daily progress cap: `1`
- Jackpot max wins/day: `0`
- Jackpot cooldown spins: `0`
- Allowed credit sources: `PROMO`, `SALE`, `REAL MONEY`
- Onboarding item types: `Sticker`, `Coupon`
- Enable jackpot-tier items: `off`

### Suggested Item Mix

- 50% to 70% of pool: low-tier sticker/coupon outcomes
- 25% to 40% of pool: low-mid digital outcomes
- 0% to 5% of pool: high-tier outcomes
- jackpot: none

### Suggested Sell Value Style

- low items: `5%` to `20%` of box price
- mid items: `20%` to `50%` of box price
- avoid expensive payouts here

### Good Use Case

- first spinner users
- welcome promotions
- activity campaigns

## 4. Box Type: Normal Everyday Box

Use this when:

- main traffic box
- repeatable daily product
- broad audience box

### Suggested Box Settings

- Price credits: `5` to `20`
- Real-money credits only: `off`
- Active: `on`

### Suggested Economy Policy

- Target RTP min: `35`
- Target RTP max: `70`
- High-tier threshold: `100`
- Onboarding max spins: `3`
- Onboarding age hours: `48`
- Pity after spins: `3`
- Pity multiplier: `2`
- Daily progress after spins: `6`
- Daily progress multiplier: `1.15` to `1.3`
- Daily progress cap: `2`
- Jackpot max wins/day: `0` or `1`
- Jackpot cooldown spins: `0`
- Allowed credit sources: `PROMO`, `SALE`, `REAL MONEY`
- Onboarding item types: `Sticker`, `Coupon`
- Enable jackpot-tier items: `off` or `very limited`

### Suggested Item Mix

- 40% to 55% low-tier
- 30% to 40% mid-tier
- 5% to 15% high-tier
- jackpot only if carefully restricted

### Suggested Sell Value Style

- low items: `10%` to `30%` of box price
- mid items: `30%` to `70%` of box price
- high items: `70%` to `150%` of box price but with real rarity

### Good Use Case

- default daily box
- general audience spinner
- standard monetized gameplay box

## 5. Box Type: Premium Box

Use this when:

- higher spenders
- stronger reward expectations
- premium inventory tier

### Suggested Box Settings

- Price credits: `20` to `100`
- Real-money credits only: `optional but often recommended`
- Active: `on`

### Suggested Economy Policy

- Target RTP min: `40`
- Target RTP max: `80`
- High-tier threshold: `250`
- Onboarding max spins: `0` to `2`
- Onboarding age hours: `24`
- Pity after spins: `4`
- Pity multiplier: `1.75` to `2.25`
- Daily progress after spins: `5`
- Daily progress multiplier: `1.1` to `1.2`
- Daily progress cap: `1`
- Jackpot max wins/day: `1`
- Jackpot cooldown spins: `10` to `50`
- Allowed credit sources: usually `SALE`, `REAL MONEY` or only `REAL MONEY`
- Onboarding item types: usually none or only low digital
- Enable jackpot-tier items: `optional`

### Suggested Item Mix

- 35% to 45% low-tier
- 35% to 45% mid-tier
- 10% to 20% high-tier
- jackpot extremely rare if enabled

### Suggested Item Restrictions

For high items:

- set `min_real_spend`
- set `min_account_age_hours`
- consider `lifetime_limit`

### Suggested Sell Value Style

- low items: `15%` to `35%` of box price
- mid items: `35%` to `80%` of box price
- high items: `80%` to `250%` of box price but with strict rarity

### Good Use Case

- serious monetized box
- loyal-user box
- seasonal premium reward box

## 6. Box Type: Jackpot Box

Use this when:

- the product promise is dream rewards
- you want ultra-rare top-end outcomes
- you are prepared to control it tightly

### Suggested Box Settings

- Price credits: `25` to `250+`
- Real-money credits only: `on` recommended
- Active: `on`

### Suggested Economy Policy

- Target RTP min: `25`
- Target RTP max: `65`
- High-tier threshold: `250` to `1000`
- Onboarding max spins: `0`
- Onboarding age hours: `0` or irrelevant
- Pity after spins: `5` to `8`
- Pity multiplier: `1.2` to `1.6`
- Daily progress after spins: `0` or `8+`
- Daily progress multiplier: `1.05` to `1.15`
- Daily progress cap: `1`
- Jackpot max wins/day: `1`
- Jackpot cooldown spins: `25` to `100`
- Allowed credit sources: `REAL MONEY` only recommended
- Onboarding item types: none
- Enable jackpot-tier items: `on`

### Suggested Item Mix

- 50% to 65% low-tier
- 25% to 35% mid-tier
- 8% to 15% high-tier
- 0.1% to 1% jackpot layer depending on economics

### Required Item Restrictions For Jackpot Items

Use most or all of these:

- `eligible_credit_sources`: `REAL MONEY`
- `min_real_spend`
- `min_account_age_hours`
- `lifetime_limit`
- extremely low `drop_weight`

### Suggested Sell Value Style

- low items: `5%` to `25%` of box price
- mid items: `20%` to `60%` of box price
- high items: `60%` to `150%` of box price
- jackpot items: only if business can truly sustain it

### Warning

Do not use jackpot boxes casually.
If you enable jackpot-tier items without strict restrictions, the box can become dangerous quickly.

## 7. Recommended Item Defaults By Type

These are item-level starting ideas.

## 7.1 Sticker

Use for:

- onboarding
- low-value filler
- harmless repeat outcomes

Suggested defaults:

- Value tier: `low`
- Drop weight: high
- Estimated value: low
- Sell value: very low
- Onboarding only: often `on`
- Returning users only: `off`
- Daily limit: `2` to `5`
- Lifetime limit: blank
- Max repeat/day: `1` to `3`
- Min account age: blank
- Min real spend: blank
- Eligible sources: all

## 7.2 Coupon

Use for:

- onboarding
- promotional feel
- low-risk reward layer

Suggested defaults:

- Value tier: `low`
- Drop weight: high
- Sell value: low
- Onboarding only: often `on`
- Eligible sources: all

## 7.3 Digital

Use for:

- mid-tier standard reward pool
- repeatable main catalog

Suggested defaults:

- Value tier: `mid`
- Drop weight: medium
- Sell value: medium
- Onboarding only: usually `off`
- Returning users only: optional
- Eligible sources: all or selected

## 7.4 Physical

Use for:

- premium reward layer
- higher perceived value

Suggested defaults:

- Value tier: `high`
- Drop weight: low
- Sell value: carefully controlled
- Onboarding only: `off`
- Min account age: `24` to `168`
- Min real spend: useful
- Eligible sources: often `SALE` + `REAL MONEY` or `REAL MONEY` only

## 7.5 Jackpot

Use for:

- dream prize only

Suggested defaults:

- Value tier: `jackpot`
- Drop weight: extremely low
- Onboarding only: `off`
- Returning users only: optional
- Daily limit: `1`
- Lifetime limit: `1`
- Max repeat/day: `1`
- Min account age: `24+`
- Min real spend: required
- Eligible sources: `REAL MONEY` only

## 8. Recommended Values For Sensitive Fields

## 8.1 Drop Weight

There is no perfect universal number.
Weights only matter relative to the other items in that same box.

Practical relative approach:

- common low-tier: `100` to `500`
- mid-tier: `20` to `100`
- high-tier: `2` to `20`
- jackpot: `1` or even less effective through extra restrictions

## 8.2 Min Real Spend

Suggested starting ranges:

- normal premium item: `10` to `50`
- expensive physical item: `25` to `200`
- jackpot item: `50` to `500+`

Choose based on your real business tolerance.

## 8.3 Min Account Age Hours

Suggested starting ranges:

- normal premium item: `24`
- high-value item: `72`
- jackpot item: `24` to `168`

## 8.4 Daily Limit

Suggested starting ranges:

- low item: `2` to `5`
- mid item: `1` to `3`
- high item: `1`
- jackpot: `1`

## 8.5 Lifetime Limit

Suggested starting use:

- only for special rewards
- rare physical items
- jackpot items

Normal repeatable items usually do not need lifetime limits.

## 9. Good Default Templates

## Template A: Friendly Starter Box

### Box

- Price: `4`
- RTP min/max: `30 / 55`
- Onboarding max spins: `4`
- Onboarding age: `72`
- Pity after: `3`
- Pity multiplier: `1.75`
- Daily progress after: `5`
- Daily progress multiplier: `1.15`
- Daily progress cap: `1`
- Allowed sources: all
- Jackpot enabled: `off`

### Items

- 2 onboarding stickers
- 2 onboarding coupons
- 3 low-mid digital items
- 1 controlled high item

## Template B: Standard Main Box

### Box

- Price: `10`
- RTP min/max: `35 / 70`
- Onboarding max spins: `3`
- Onboarding age: `48`
- Pity after: `3`
- Pity multiplier: `2`
- Daily progress after: `6`
- Daily progress multiplier: `1.2`
- Daily progress cap: `2`
- Allowed sources: all
- Jackpot enabled: `off`

### Items

- 3 low-tier
- 3 mid-tier
- 2 high-tier

## Template C: Premium Spend Box

### Box

- Price: `50`
- RTP min/max: `40 / 80`
- Onboarding max spins: `1`
- Onboarding age: `24`
- Pity after: `4`
- Pity multiplier: `1.8`
- Daily progress after: `5`
- Daily progress multiplier: `1.1`
- Daily progress cap: `1`
- Allowed sources: `SALE`, `REAL MONEY`
- Jackpot enabled: `optional`

### Items

- 3 low-tier
- 4 mid-tier
- 2 high-tier
- 0 to 1 jackpot

## Template D: Hard-Control Jackpot Box

### Box

- Price: `100`
- RTP min/max: `25 / 60`
- Onboarding max spins: `0`
- Pity after: `6`
- Pity multiplier: `1.3`
- Daily progress after: `10`
- Daily progress multiplier: `1.05`
- Daily progress cap: `1`
- Allowed sources: `REAL MONEY`
- Jackpot enabled: `on`
- Jackpot max wins/day: `1`
- Jackpot cooldown spins: `50`

### Items

- 4 low-tier
- 3 mid-tier
- 2 high-tier
- 1 jackpot with strict restrictions

## 10. Tuning Strategy

If users feel rewards are too weak:

- slightly improve mid-tier weights
- slightly improve pity multiplier
- slightly improve daily progression

Do not first increase jackpot chance.

If users repeat the same cheap rewards too much:

- lower low-tier weight concentration
- reduce max repeat/day
- add more low-tier variety

If box feels too generous:

- lower sell values first
- reduce high-tier frequency
- tighten funding source restrictions

If premium items appear too early:

- increase `min_account_age_hours`
- increase `min_real_spend`
- disable promo source for those items

## 11. Common Mistakes

### Mistake 1
Using high estimated values but also high sell values.

Why bad:

- makes box much more expensive than it looks

### Mistake 2
Allowing jackpot items for promo-funded spins.

Why bad:

- promo abuse risk

### Mistake 3
Setting pity multiplier too high.

Why bad:

- pity can overpower your base economy

### Mistake 4
No daily or repeat limits on cheap items.

Why bad:

- users feel spammed by duplicates

### Mistake 5
Trying to control everything using only `drop_weight`.

Why bad:

- the system is built to use multiple guardrails together

## 12. Final Advice

Good setup order:

1. Decide the box purpose
2. Decide the target audience
3. Set price
4. Set sell-value targets
5. Build low/mid/high/jackpot mix
6. Apply credit source restrictions
7. Apply spend/age restrictions for premium outcomes
8. Tune weights
9. Test real experience
10. Adjust slowly

Best starting mindset:

- cheap boxes should feel friendly
- normal boxes should feel fair
- premium boxes should feel controlled
- jackpot boxes should feel exciting but heavily protected
