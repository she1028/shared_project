<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

$isClientLoggedIn = (!empty($_SESSION['role']) && $_SESSION['role'] !== 'admin') && (!empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']));
?>
<?php if ($isClientLoggedIn): ?>
    <!-- Notifications Widget (client only) -->
    <div id="notifFab" class="notif-fab" title="Notifications" aria-label="Notifications">
        <span class="notif-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 14h12a1 1 0 00.707-1.707L18 11.586V8a6 6 0 00-6-6zm0 20a3 3 0 01-2.995-2.824L9 19h6a3 3 0 01-2.824 2.995L12 22z"/>
            </svg>
        </span>
        <span class="notif-badge" id="notifBadge"></span>
    </div>
    <div id="notifPanel" class="notif-panel">
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
            <h6 class="m-0 flex-grow-1">Order Notifications</h6>
            <div class="d-flex gap-2">
                <button class="notif-icon-btn" id="notifRefresh" aria-label="Refresh notifications"><i class="bi bi-arrow-clockwise"></i></button>
                <button type="button" class="notif-icon-btn" id="notifClose" aria-label="Close notifications"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div id="notifList" class="d-flex flex-column gap-2" style="overflow-y:auto; max-height:50vh;"></div>
    </div>

    <script>
    // Notifications widget logic (client session-based)
    const notifFab = document.getElementById('notifFab');
    const notifPanel = document.getElementById('notifPanel');
    const notifClose = document.getElementById('notifClose');
    const notifList = document.getElementById('notifList');
    const notifRefresh = document.getElementById('notifRefresh');
    const notifBadge = document.getElementById('notifBadge');

    // If the widget isn't rendered (guest/admin), do nothing.
    if (!notifFab || !notifPanel || !notifList || !notifClose || !notifRefresh || !notifBadge) {
        // no-op
    } else {
        let notificationsCache = [];
        const orderDetailsCache = {};

    const statusClass = (s) => {
        if (s === 'paid') return 'paid';
        if (s === 'shipped') return 'shipped';
        return 'pending';
    };

    const escapeHtml = (val) => {
        if (!val) return '';
        return val
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

        const updateBadge = () => {
            const unread = (notificationsCache || []).reduce((acc, n) => acc + (!parseInt(n.is_read, 10) ? 1 : 0), 0);
            if (unread > 0) {
                notifBadge.textContent = unread;
                notifBadge.style.display = 'flex';
            } else {
                notifBadge.style.display = 'none';
            }
        };

        const markNotificationsRead = async (ids) => {
            if (!ids || !ids.length) return;
            try {
                const body = `ids=${encodeURIComponent(ids.join(','))}`;
                await fetch('api/mark_notifications_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body
                });
            } catch (e) {
                // ignore
            }
        };

        const renderNotifications = (list) => {
            notifList.innerHTML = '';
            if (!list || list.length === 0) {
                notifList.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
                notifBadge.style.display = 'none';
                return;
            }
            notificationsCache = list;

            const fetchOrderDetails = async (orderId) => {
                const key = String(orderId || '');
                if (!key) return null;
                if (orderDetailsCache[key]) return orderDetailsCache[key];
                try {
                    const res = await fetch(`api/get_order_details.php?order_id=${encodeURIComponent(key)}`);
                    const data = await res.json();
                    if (data && data.success) {
                        orderDetailsCache[key] = data;
                        return data;
                    }
                } catch (e) {
                    // ignore
                }
                return null;
            };

            const renderOrderDetailsHtml = (data) => {
                if (!data || !data.order) return '<div class="notif-meta">Unable to load order details.</div>';
                const o = data.order;
                const items = Array.isArray(data.items) ? data.items : [];
                const money = (n) => {
                    const num = Number(n || 0);
                    return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };
                const itemsText = items.length
                    ? items.map(it => `• ${escapeHtml(it.product_name)} x${escapeHtml(it.quantity)}`).join('<br>')
                    : '(No items)';

                const addressBits = [o.street, o.barangay, o.city, o.province, o.postal_code]
                    .map(v => (v || '').trim())
                    .filter(Boolean);

                return `
                    <div class="notif-meta"><strong>Name:</strong> ${escapeHtml(o.full_name || '')}</div>
                    <div class="notif-meta"><strong>Contact:</strong> ${escapeHtml(o.contact || '')}</div>
                    <div class="notif-meta"><strong>Email:</strong> ${escapeHtml(o.email || '')}</div>
                    <div class="notif-meta"><strong>Payment:</strong> ${escapeHtml(String(o.payment_method || '').toUpperCase())}</div>
                    <div class="notif-meta"><strong>Delivery:</strong> ${escapeHtml(String(o.delivery_method || '').toUpperCase())}</div>
                    ${addressBits.length ? `<div class="notif-meta"><strong>Address:</strong> ${escapeHtml(addressBits.join(', '))}</div>` : ''}
                    <div class="notif-meta"><strong>Items:</strong><br>${itemsText}</div>
                    <div class="notif-meta"><strong>Subtotal:</strong> PHP ${escapeHtml(money(o.subtotal))}</div>
                    <div class="notif-meta"><strong>Shipping:</strong> PHP ${escapeHtml(money(o.shipping))}</div>
                    <div class="notif-meta"><strong>Total:</strong> PHP ${escapeHtml(money(o.total))}</div>
                `;
            };

            list.forEach(n => {
                const wrap = document.createElement('div');
                wrap.className = 'notif-item';
                wrap.style.cursor = 'pointer';
                wrap.style.opacity = parseInt(n.is_read, 10) ? '0.75' : '1';

                const dateStr = new Date(n.created_at).toLocaleString();
                const updatedStr = n.updated_at ? new Date(n.updated_at).toLocaleString() : '';

                wrap.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="status-pill ${statusClass(n.status)}">${String(n.status || '').toUpperCase()}</span>
                        <span class="notif-meta">${dateStr}</span>
                    </div>
                    <div class="mt-1">${escapeHtml(n.message || '')}</div>
                    <div class="notif-meta">Order #${escapeHtml(String(n.order_id ?? ''))}</div>
                    <div class="notif-detail mt-2" style="display:none; border-top: 1px solid rgba(0,0,0,0.08); padding-top: 8px;">
                        ${updatedStr ? `<div class="notif-meta">Updated: ${escapeHtml(updatedStr)}</div>` : ''}
                        <div class="notif-meta">Tap again to close</div>
                    </div>
                `;

                wrap.addEventListener('click', async () => {
                    const detail = wrap.querySelector('.notif-detail');
                    const isOpening = detail && detail.style.display === 'none';
                    if (detail) detail.style.display = isOpening ? 'block' : 'none';

                    if (isOpening && !parseInt(n.is_read, 10)) {
                        n.is_read = 1;
                        wrap.style.opacity = '0.75';
                        updateBadge();
                        await markNotificationsRead([n.id]);
                    }

                    if (isOpening && detail) {
                        detail.innerHTML = `<div class="notif-meta">Loading order details…</div>`;
                        const data = await fetchOrderDetails(n.order_id);
                        detail.innerHTML = `
                            ${updatedStr ? `<div class="notif-meta">Updated: ${escapeHtml(updatedStr)}</div>` : ''}
                            ${renderOrderDetailsHtml(data)}
                            <div class="notif-meta mt-2">Tap again to close</div>
                        `;
                    }
                });

                notifList.appendChild(wrap);
            });

            updateBadge();
        };

        const fetchNotifications = async () => {
            try {
                const res = await fetch('api/get_notifications.php');
                const data = await res.json();
                if (!data.success) {
                    notifList.innerHTML = '<div class="notif-empty">Unable to load notifications.</div>';
                    notifBadge.style.display = 'none';
                    return;
                }
                renderNotifications(data.notifications);
            } catch (err) {
                notifList.innerHTML = '<div class="notif-empty">Unable to load notifications.</div>';
                notifBadge.style.display = 'none';
            }
        };

        const togglePanel = () => {
            notifPanel.classList.toggle('open');
            if (notifPanel.classList.contains('open')) {
                fetchNotifications();
            }
        };

        notifFab.addEventListener('click', togglePanel);
        notifClose.addEventListener('click', togglePanel);
        notifRefresh.addEventListener('click', () => {
            fetchNotifications();
        });

        // Initial badge load (without forcing the panel open)
        fetchNotifications();
    }
    </script>
<?php endif; ?>
