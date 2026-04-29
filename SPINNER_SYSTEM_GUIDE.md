# Spinner System Guide

This guide explains the full spinner system in this project from both the admin side and the user side.

It is written to help you operate the system without needing to study the code first.

## 1. Big Picture

This spinner is not just a random wheel.

It is a managed reward economy built from these layers:

1. Box configuration
2. Item pool configuration
3. Wallet funding source rules
4. User progression and daily logic
5. Weighted random selection
6. Inventory and sell-back handling
7. Audit snapshots for history and control

Main idea:

- Every box has a price.
- Every box contains items.
- Every item has a weight and rules.
- The system first checks which items are allowed for that user right now.
- Then it adjusts effective chance using onboarding, pity, daily progression, and anti-repeat rules.
- Then it picks a winner using weighted randomness.
- Then it stores the result together with why it happened.

## 2. Core Concepts

### Box
A box is the spinner product a user opens.

Examples:

- Rivals Box
- Starter Spinner
- Premium Jackpot Box

Each box has:

- name
- slug
- description
- thumbnail
- price in credits
- status
- economy policy

### Item
An item is a possible reward inside a box.

Examples:

- Sticker
- Coupon
- Digital reward
- Physical reward
- Jackpot reward

Each item has:

- display info
- reward type
- value tier
- drop weight
- funding restrictions
- progression restrictions
- daily/lifetime caps

### Reward Profile
This is the economy policy for a box.

It controls:

- RTP range
- onboarding behavior
- pity behavior
- daily progression
- jackpot behavior
- allowed funding sources

### Wallet Buckets
The system separates credits by source:

- `promo_credits`
- `sale_credits`
- `real_money_credits`

This is important because some rewards should only be possible if the spin is funded with certain credit sources.

### Progress
The system tracks per user + per box progress:

- daily spin count
- lifetime spin count
- onboarding spins used
- consecutive low-tier outcomes
- jackpot wins today
- high-tier wins today

## 3. User Flow

## 3.1 New User Flow

Typical new user flow:

1. User registers.
2. User starts with low or zero credits unless admin/promo credits are given.
3. User opens a box page.
4. If balance is below the box price, the open button redirects to wallet/deposit first.
5. After adding credits, user returns and opens the box.
6. Spinner checks the user state and usually gives onboarding-friendly outcomes first if the box policy allows that.

What onboarding means here:

- early spins can prefer item types like `sticker` and `coupon`
- onboarding can be limited by number of spins
- onboarding can also be limited by account age

So if the box is configured for starter behavior, new users get a safer entry experience.

## 3.2 Existing User Flow

For older users, onboarding usually does not apply anymore.

Their spins are mainly affected by:

- wallet source used
- current local-day spin count
- pity count
- repeat history
- item eligibility rules
- high-tier and jackpot guardrails

This means older users are treated more like normal progression users, not first-time users.

## 3.3 Old Users Migrated From Previous Version

For existing users that already had balances before this new system:

- their existing total balance is moved into `real_money_credits` during migration
- their timezone is filled from the app default if it was missing

Why:

- old data had only one balance column
- the system had to preserve spendable balance safely
- putting it into `real_money_credits` avoids making those balances unusable for stricter boxes

Important practical meaning:

- old users keep their usable balance
- they do not lose access
- but their historical old balance cannot be perfectly separated into promo/sale/real because old data did not store that split

## 4. Spinner Logic Step by Step

When a user clicks Open:

1. The system checks whether the box is active.
2. It checks whether the user has enough balance.
3. It debits the box price from wallet buckets in this order:
   - promo
   - sale
   - real money
4. It loads the user-box progress row.
5. It loads active box items.
6. It filters out items the user is not allowed to get right now.
7. It applies effective chance adjustments.
8. It runs the provably fair random roll.
9. It picks the winner by weight.
10. It saves the spin, rule trail, funding source, and item snapshot.
11. It creates a pending inventory item.
12. User then decides:
   - sell
   - add to inventory

## 5. Daily Logic

Daily logic is based on the user timezone, not only server UTC.

That means:

- each user has a `timezone`
- the system calculates the user local day
- when that local day changes, daily counters reset

What resets daily:

- daily spin count
- daily progression segment
- high-tier wins today
- jackpot wins today
- item daily counters used in repeat limits

What does not necessarily reset daily:

- lifetime spins
- onboarding age
- lifetime item win count

Why this matters:

- a user in Pakistan and a user in New York should not both be forced into the same reset moment if your product wants local-day behavior

## 6. Chance Adjustment Logic

The system does not always use raw weight directly.

It first calculates an effective weight.

### 6.1 Base Weight
This is the original item `drop_weight` configured by admin.

Higher weight = appears more often.

### 6.2 Onboarding Boost
If the user is still in onboarding window:

- item types chosen in onboarding policy can be boosted
- usually stickers/coupons are used here

Purpose:

- smoother first user experience
- controlled early outcomes

### 6.3 Pity Boost
If the user has multiple low-tier outcomes in a row:

- mid-tier items can get an extra multiplier

Purpose:

- avoid the feeling of endless low-value streaks

### 6.4 Daily Progress Boost
After some number of spins in the same local day:

- selected item types can get a slight boost

Purpose:

- create daily progression feeling
- keep users engaged without exploding the economy

### 6.5 Anti-Repeat Dampening
If the same user already hit the same low-value item too much:

- its effective weight can be reduced

Purpose:

- stop annoying repetition
- make the spinner feel less stale

### 6.6 Jackpot Guardrails
Jackpot items stay possible, but are heavily controlled.

Purpose:

- allow dream rewards
- avoid box economics becoming irrational

## 7. User Wallet Flow

## 7.1 Wallet Buckets

### Promo Credits
Used for:

- admin top-up
- onboarding/promo style rewards
- test/manual rewards

### Sale Credits
Used for:

- credits earned by selling inventory rewards back into balance

### Real Money Credits
Used for:

- actual deposit-funded balance
- migrated legacy balance from old system

## 7.2 Spending Order

Current spend order:

1. Promo
2. Sale
3. Real money

This means if a user has:

- 5 promo
- 10 sale
- 20 real

and opens a 12-credit box, the system spends:

- 5 promo
- 7 sale
- 0 real

## 7.3 Why Split Wallet Matters

This lets you answer:

- did the user spin using deposit money or promo money?
- did they sell previous rewards and reuse that?
- should this spin be allowed for premium-only rewards?

## 8. Inventory Flow

After spin, reward goes into pending inventory state.

User can choose:

### Sell
The item is sold immediately.

Result:

- inventory state becomes sold
- sell value goes into `sale_credits`

### Add to Inventory
The item is kept for future use.

Result:

- inventory state becomes saved
- no credits added immediately

Users can review all won items from the inventory page.

Inventory page purpose:

- see pending decisions
- see saved items
- see sold history
- see claimed items
- sell saved items later
- mark saved items as claimed

Important:

- inventory stores an item snapshot
- later admin edits do not rewrite history

So if admin changes item value or name later, old wins still show what the user actually won at that time.

## 9. Admin Flow

Admin manages the system in 3 big areas:

1. Box settings
2. Box economy policy
3. Box item rules

## 10. Box Settings Fields

These are the general box identity fields.

### Name
Human-readable box name.

Use:

- display title in admin and user pages

### Slug
URL-friendly identifier.

Example:

- `starter-spinner`
- `rivals-box`

Use:

- route path
- internal reference

### Description
User-facing explanation for the box.

Use:

- what this box is
- theme
- positioning

### Thumbnail Image
Main box image.

Use:

- user listing cards
- branding

### Price Credits
How much one open costs.

Use:

- wallet debit amount
- entry barrier
- box value baseline

### Sort Order
Controls display order in lists.

Use:

- homepage/admin ordering

### Active
If checked:

- users can open the box

If unchecked:

- box is hidden or unusable for spin

### Real-money Credits Only
If checked:

- user must fund the spin entirely with real-money credits

Use when:

- you want premium boxes
- you do not want promo-funded or sell-funded spins to access them

## 11. Economy Policy Fields

These fields define box-level economy behavior.

### Target RTP Min
Lower boundary of expected return planning.

Meaning:

- used as guardrail reference
- helps you keep box return from becoming too weak

Practical interpretation:

- if box costs 10 and expected sell return is around 3 to 8.5, RTP is roughly 30% to 85%

### Target RTP Max
Upper boundary of expected return planning.

Meaning:

- keeps box from becoming too generous overall

### High-tier Threshold
Value above which the system starts treating rewards as high-tier controlled outcomes.

Use:

- extra caution for costly rewards

### Onboarding Max Spins
How many early spins can use onboarding behavior.

Example:

- `3` means first 3 qualifying spins can use onboarding rules

### Onboarding Age Hours
How old the account can be and still count as onboarding.

Example:

- `48` means only users within first 48 hours of account life qualify

### Pity After Spins
How many consecutive low outcomes trigger pity logic.

Example:

- `3` means after 3 low-tier hits in a row, pity starts helping

### Pity Multiplier
How strongly the pity logic boosts selected items.

Higher value:

- stronger recovery effect
- more economy risk if too high

### Daily Progress After Spins
How many spins in the same local day before daily progression boost starts.

### Daily Progress Multiplier
How much boost daily progression gives.

### Daily Progress Cap
Maximum progression segment allowed.

Purpose:

- prevents the boost from growing too much

### Jackpot Max Wins/Day
How many jackpot wins one user can get from this box in the same local day.

### Jackpot Cooldown Spins
Optional cooldown spacing between jackpot-eligible moments.

Current idea:

- extra guardrail for jackpot frequency control

### Allowed Credit Sources
Which wallet sources can be used for this box.

Options:

- `PROMO`
- `SALE`
- `REAL MONEY`

If a source is not selected:

- spins funded by that source should not qualify for this box policy path

### Onboarding Item Types
Which item types get boosted during onboarding.

Options:

- Sticker
- Coupon
- Digital
- Physical
- Jackpot

Recommended normal use:

- Sticker
- Coupon

### Enable Jackpot-tier Items
Master switch for jackpot items in this box.

If off:

- jackpot-tier rewards are blocked

## 12. Item Fields

These are the most important admin controls because they directly define reward behavior.

### Item Name
Display name of the reward.

### Item Image
Image used in list, reel, and inventory snapshot.

### Item Type
Reward family.

Options:

#### Sticker
Cheap starter reward.

Use:

- onboarding
- filler rewards
- low-value repeat-safe outcomes

#### Coupon
Discount / promo style reward.

Use:

- onboarding
- engagement
- soft-value outcome

#### Digital
Normal digital reward.

Use:

- mid-tier common prize group

#### Physical
Real physical product.

Use:

- premium reward
- often better restricted by real-money or spend conditions

#### Jackpot
Dream reward group.

Use:

- highly restricted, very rare outcomes

### Rarity Label
User-facing rarity text only.

Examples:

- common
- rare
- epic
- legendary

Purpose:

- visual presentation
- marketing feel

### Value Tier
System behavior tier.

Options:

#### Low
Cheap and common.

Used for:

- onboarding
- pity counting
- anti-repeat logic

#### Mid
Improved outcome tier.

Used for:

- pity recovery rewards

#### High
Expensive controlled rewards.

Used for:

- higher guardrails
- daily high-tier limits

#### Jackpot
Top controlled tier.

Used for:

- jackpot-specific safety rules

### Drop Weight
Raw probability weight before rule multipliers.

Higher number:

- more likely

Lower number:

- rarer

### Sort Order
Display ordering in admin/user item lists.

### Item Price (Sell Value)
Single price field used by the system.

Used for:

- economy targeting (simple payout bands)
- “Sell” credit amount
- user-facing price display

Use:

- actual balance return
- core economy number

Important:

- this is usually the most sensitive value in RTP planning

### Daily Limit
Maximum times this user can win this item in one local day.

Use:

- stop spammy repeats

### Lifetime Limit
Maximum times this user can ever win this item.

Use:

- special rewards
- one-time prizes

### Max Repeat/Day
Soft repeat control number.

Use:

- once user already got this item enough today, system dampens repeat chance

### Min Account Age Hours
User account must be at least this old to qualify.

Use:

- stop brand new accounts from hitting premium rewards too early

### Min Real Spend
User must have deposited at least this amount historically.

Use:

- protect premium rewards
- connect expensive rewards to actual paying behavior

### Eligible Credit Sources
Which funding sources are allowed for this item.

Options:

- PROMO
- SALE
- REAL MONEY

Example:

- jackpot item can be set to `REAL MONEY` only

### Onboarding Only
If checked:

- item is available only during onboarding window

Best for:

- starter stickers
- starter coupons

### Returning Users Only
If checked:

- onboarding users cannot get it

Best for:

- normal reward pool after starter phase

### Active and Eligible
If checked:

- item participates in the spin engine

If unchecked:

- item stays in admin records but is not used

### Eligible Spin Range
Lets you define spin number windows.

Fields:

- From Spin
- To Spin

Example:

- from 10 to 30 means only spin numbers 10-30 can hit that item

Use:

- campaign pacing
- progression pacing
- delayed access

## 13. How New Users Usually Behave

With a normal starter-friendly setup:

- they may start with low or zero balance
- they are redirected to deposit first if needed
- early spins can favor sticker/coupon reward types
- jackpot/high-tier rewards are usually blocked or effectively too rare
- if they keep getting low outcomes, pity may help move them toward mid-tier

## 14. How Old Users Usually Behave

Old users usually:

- are outside onboarding age window
- no longer qualify for onboarding-only items
- use regular reward pool
- may access more rewards if they have enough real spend
- still get daily reset and pity logic

So old users are not permanently punished.
They just stop receiving starter treatment.

## 15. Recommended Operating Strategy

For a healthy box:

- use low-tier items as your volume layer
- use mid-tier items as relief outcomes
- use high-tier sparingly
- use jackpot as very rare dream layer
- keep sell values controlled
- use onboarding only for early account confidence
- use real-money restrictions for expensive products

## 16. Practical Example

Example box (simple economy):

- price = 4 credits
- simple profile = `normal` (drives 24h payout bands)
- allowed credit sources = promo + sale + real money
- jackpot enabled = yes (only affects `jackpot` item types)

Example item pool:

- coupon (low) ~$0.20 to $0.40 sell value
- digital (mid) ~$2.00 sell value
- physical (high) ~$120 sell value
- jackpot (very rare) real-money only

Note: in the simplified admin UI we auto-derive `rarity` + `value tier` from the item value compared to the box price, so you only set type, weight, and values.

Result:

- new users mostly see starter outcomes first
- repeated bad luck gets softened
- expensive rewards remain possible but controlled
- deposit-funded users can reach more premium layers

## 17. Important Warnings

1. Do not rely only on tiny weight for jackpots.
Use funding restrictions, spend requirements, daily limits, and profile guardrails too.

2. The single “sell/price” field controls the economics and is the actual wallet liability.

3. Onboarding-only rewards should usually be cheap.

4. If you allow all sources for all items, promo abuse becomes easier.

5. High-tier physical and jackpot rewards should usually require:

- real-money eligibility
- spend requirement
- age requirement
- low base weight

## 18. Where To Look In Code

Main places:

- `app/Services/SpinService.php`
- `app/Services/SpinEconomyService.php`
- `app/Services/SpinEligibilityService.php`
- `app/Services/UserProgressService.php`
- `app/Services/WalletService.php`
- `app/Services/WalletFundingService.php`
- `app/Services/InventoryActionService.php`
- `resources/views/admin/boxes/edit.blade.php`
- `resources/views/admin/items/index.blade.php`
- `resources/views/boxes/show.blade.php`
- `resources/views/wallet/show.blade.php`

## 19. Short Admin Setup Checklist

When creating a new box:

1. Set name, slug, description, image
2. Set price
3. Decide if real-money only
4. Set RTP min/max
5. Set onboarding values
6. Set pity values
7. Set daily progression values
8. Decide jackpot policy
9. Add item pool
10. Give every item correct type, tier, sell value, and source rules

## 20. Short User Journey Checklist

For a user:

1. Go to box
2. If low balance, get redirected to deposit
3. Deposit or use available credits
4. Open box
5. System checks rules and funding source
6. System picks reward
7. User sells or saves
8. Progress updates for next spin
9. User can later revisit `/inventory` to manage saved rewards

## 21. Final Summary

This spinner now works like a controlled reward engine.

Admin side decides:

- what is possible
- who can get it
- when it can happen
- how often it can happen
- what wallet source can fund it

User side experiences:

- deposit guidance when balance is low
- onboarding behavior for early accounts
- daily progression
- pity smoothing
- inventory or sell choice
- transparent balance source tracking

If you want, the next useful step is for me to create a second document:

- `SPINNER_RECOMMENDED_DEFAULTS.md`

That can give you ready-made safe values for:

- cheap box
- normal box
- premium box
- jackpot box

so you know what numbers to start with instead of guessing.

## Demo Data + Simulation

This repo includes a demo seeder and a simulation command for quick validation.

- Seed 10 boxes + 5 users: `php artisan db:seed`
- Run a simulation report (seed + spins + auto-sell): `php artisan spinner:simulate --seed`

The simulation prints a summary table and writes a JSON report under `storage/app/reports`.

## Simple Economy Math (24h)

In **simple** economy mode we decide the next spin’s “return band” using the user’s last 24h activity:

- `spent` = sum of spin costs in the last 24h (`box_spins.cost_credits`)
- `returned` = sum of won item prices in the last 24h (from the spin snapshot `sell_value_credits`)
- `netLoss = max(0, spent - returned)`

For the next spin (current box price = `C`), we use:

- `basis = netLoss + C`  (called `net_loss_after_cost` in `config/spinner.php`)
- then apply the scenario band (example `normal_spin = 10%..30%`):
  - `minValue = basis * 0.10`
  - `maxValue = basis * 0.30`

Then we choose eligible items whose **item price** falls in that range, and pick among them using drop weights.
