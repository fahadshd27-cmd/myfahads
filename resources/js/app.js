import axios from 'axios';

function csrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token ?? '';
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function buildSlot(slot) {
    const div = document.createElement('div');
    div.className = 'flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-white text-zinc-400 shadow-sm dark:bg-zinc-900 dark:text-zinc-500 overflow-hidden';

    if (slot.type === 'item') {
        if (slot.image) {
            const img = document.createElement('img');
            img.src = slot.image;
            img.alt = slot.name ?? '';
            img.className = 'h-full w-full object-contain p-2';
            div.appendChild(img);
        } else {
            div.textContent = (slot.name ?? 'Item').slice(0, 1);
        }
    } else {
        div.textContent = 'G';
    }
    return div;
}

async function postJson(url, data) {
    const res = await axios.post(url, data, {
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    });
    return res.data;
}

function money(v) {
    const n = typeof v === 'number' ? v : parseFloat(v ?? '0');
    return '$' + n.toFixed(2);
}

function credits(v) {
    const n = typeof v === 'number' ? v : parseFloat(v ?? '0');
    return n.toFixed(2);
}

function applyBalance(balance) {
    if (!balance) return;

    const total = typeof balance === 'number' ? balance : balance.total;

    const totalEl = document.getElementById('balance');
    const promoEl = document.getElementById('balance-promo');
    const saleEl = document.getElementById('balance-sale');
    const realEl = document.getElementById('balance-real');

    // On the spinner page we display a single remaining "Credits" number (no $).
    if (totalEl) totalEl.textContent = credits(total ?? 0);

    // These elements exist on some older layouts; update them if present.
    if (promoEl && typeof balance === 'object') promoEl.textContent = credits(balance.promo ?? 0);
    if (saleEl && typeof balance === 'object') saleEl.textContent = credits(balance.sale ?? 0);
    if (realEl && typeof balance === 'object') realEl.textContent = credits(balance.real_money ?? 0);
}

function extractErrorMessage(error) {
    const status = error?.response?.status;
    const data = error?.response?.data;
    const errors = data?.errors;

    if (status === 419) {
        return 'Your session expired. Refresh the page and try again.';
    }

    if (status === 429) {
        return 'Too many attempts. Please wait a moment and try again.';
    }

    if (errors && typeof errors === 'object') {
        for (const key of ['balance', 'items', 'box', 'client_seed']) {
            const message = errors[key]?.[0];
            if (message) {
                return message;
            }
        }

        const firstGroup = Object.values(errors)[0];
        if (Array.isArray(firstGroup) && firstGroup[0]) {
            return firstGroup[0];
        }
    }

    if (typeof data?.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }

    return 'Open failed. Please try again.';
}

function setupSpinner() {
    const openBtn = document.getElementById('open-btn');
    const reel = document.getElementById('reel');
    const status = document.getElementById('open-status');
    const seedInput = document.getElementById('client-seed');

    if (!openBtn || !reel) return;

    const dialog = document.getElementById('win-dialog');
    const winName = document.getElementById('win-name');
    const winImage = document.getElementById('win-image');
    const winSell = document.getElementById('win-sell');
    const winMsg = document.getElementById('win-msg');
    const actSave = document.getElementById('act-save');
    const actSell = document.getElementById('act-sell');
    const walletUrl = openBtn.dataset.walletUrl;
    const boxPrice = parseFloat(openBtn.dataset.boxPrice ?? '0');
    const spinDurationMs = 15000;

    let lastInventoryId = null;

    function currentBalance() {
        const total = document.getElementById('balance')?.textContent ?? '0';

        return parseFloat(total.replace('$', '').trim()) || 0;
    }

    function redirectToDeposit() {
        if (walletUrl) {
            window.location.href = walletUrl;
        }
    }

    function updateOpenState(balance = currentBalance()) {
        const canOpen = balance >= boxPrice;
        openBtn.textContent = canOpen ? 'Open' : 'Add credits to open';
        return canOpen;
    }

    async function inventoryAction(action) {
        if (!lastInventoryId) return;
        winMsg.textContent = 'Working...';
        try {
            const data = await postJson(`/inventory/${lastInventoryId}/${action}`, {});
            winMsg.textContent = `Done: ${action.toUpperCase()}`;
            applyBalance(data.balance);
            return data;
        } catch (e) {
            winMsg.textContent = 'Action failed.';
        }
    }

    actSave?.addEventListener('click', () => inventoryAction('save'));
    actSell?.addEventListener('click', () => inventoryAction('sell'));
    updateOpenState();

    openBtn.addEventListener('click', async () => {
        const slug = openBtn.dataset.boxSlug;
        if (!slug) return;

        if (!updateOpenState()) {
            status.textContent = 'Redirecting to deposit...';
            redirectToDeposit();
            return;
        }

        openBtn.disabled = true;
        status.textContent = 'Opening...';
        winMsg.textContent = '';

        try {
            const data = await postJson(`/boxes/${slug}/spins`, {
                client_seed: seedInput?.value ?? '',
            });

            // Render reel
            reel.innerHTML = '';
            data.reel.forEach((slot) => reel.appendChild(buildSlot(slot)));

            // Animate to stop index
            reel.style.transition = 'none';
            reel.style.transform = 'translateX(0px)';
            // Force reflow so the next transition triggers reliably.
            void reel.offsetHeight;

            const slotWidth = 80;
            const gap = 12;
            const offsetPer = slotWidth + gap;
            const containerCenter = reel.parentElement.clientWidth / 2;
            const targetCenter = (data.stop_index * offsetPer) + slotWidth / 2;
            const translateX = containerCenter - targetCenter;

            reel.style.transition = `transform ${spinDurationMs}ms cubic-bezier(0.03, 0.98, 0.08, 1)`;
            reel.style.transform = `translateX(${translateX}px)`;

            // Update balance
            applyBalance(data.balance);
            updateOpenState(typeof data.balance === 'object' ? data.balance.total : data.balance);

            // Show win dialog after animation
            setTimeout(() => {
                if (winName) winName.textContent = data.winner.name;
                if (winImage) {
                    winImage.src = data.winner.image ?? '';
                    winImage.style.display = data.winner.image ? 'block' : 'none';
                }
                if (winSell) winSell.textContent = money(data.winner.sell_value);

                lastInventoryId = data.inventory_item_id ?? null;

                dialog?.showModal();
                status.textContent = 'Done';
            }, spinDurationMs + 100);
        } catch (e) {
            const errorMessage = extractErrorMessage(e);

            if (errorMessage.toLowerCase().includes('insufficient')) {
                status.textContent = 'Redirecting to deposit...';
                redirectToDeposit();
            } else {
                status.textContent = errorMessage;
            }
        } finally {
            setTimeout(() => {
                openBtn.disabled = false;
                updateOpenState();
            }, spinDurationMs + 200);
        }
    });
}

function setupDeposits() {
    const form = document.getElementById('deposit-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        try {
            const data = await postJson('/deposits', {
                amount: fd.get('amount'),
                gateway: fd.get('gateway'),
            });

            window.location.href = data.checkout_url;
        } catch (err) {
            alert('Deposit failed. Check configuration.');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setupDeposits();
    setupSpinner();
});
