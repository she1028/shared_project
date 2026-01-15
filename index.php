<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YMZM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oranienbaum' rel='stylesheet'>
</head>

<body>

    <!-- Navbar -->
    <?php include("nav.php"); ?>

    <?php include("includes/chatbot.php"); ?>

    <script>
        const landingFab = document.getElementById('landingNotifFab');
        const landingPanel = document.getElementById('landingNotifPanel');
        const landingClose = document.getElementById('landingNotifClose');
        const landingEmailInput = document.getElementById('landingNotifEmail');
        const landingList = document.getElementById('landingNotifList');
        const landingSave = document.getElementById('landingNotifSave');
        const landingRefresh = document.getElementById('landingNotifRefresh');
        const landingBadge = document.getElementById('landingNotifBadge');

        const getLandingEmail = () => {
            return localStorage.getItem('landingNotifEmail') || '';
        }
        const storeLandingEmail = (email) => {
            localStorage.setItem('landingNotifEmail', email);
        }

        const renderLandingNotifications = (list) => {
            landingList.innerHTML = '';
            if (!list || list.length === 0) {
                landingList.innerHTML = '<div class="notif-empty">No notifications yet. Save your order email to get updates.</div>';
                landingBadge.style.display = 'none';
                return;
            }
            let unread = 0;
            list.forEach(n => {
                if (!parseInt(n.is_read, 10)) unread++;
                const wrap = document.createElement('div');
                wrap.className = 'notif-item';
                const dateStr = new Date(n.created_at).toLocaleString();
                wrap.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <span class="status-pill ${n.status}">${n.status.toUpperCase()}</span>
                <span class="notif-meta">${dateStr}</span>
            </div>
            <div class="mt-1">${n.message || ''}</div>
            <div class="notif-meta">Order #${n.order_id}</div>
        `;
                landingList.appendChild(wrap);
            });
            landingBadge.textContent = unread || '';
            landingBadge.style.display = unread > 0 ? 'flex' : 'none';
        }

        const fetchLandingNotifications = async (email) => {
            if (!email) return;
            try {
                const res = await fetch(`api/get_notifications.php?email=${encodeURIComponent(email)}`);
                const data = await res.json();
                if (!data.success) return;
                renderLandingNotifications(data.notifications);
            } catch (err) {
                console.error(err);
            }
        }

        landingFab.addEventListener('click', () => {
            landingPanel.classList.toggle('open');
            if (landingPanel.classList.contains('open')) fetchLandingNotifications(landingEmailInput.value || getLandingEmail());
        });
        landingClose.addEventListener('click', () => landingPanel.classList.remove('open'));
        landingSave.addEventListener('click', () => {
            const email = landingEmailInput.value.trim();
            if (!email) return;
            storeLandingEmail(email);
            fetchLandingNotifications(email);
        });
        landingRefresh.addEventListener('click', () => {
            fetchLandingNotifications(landingEmailInput.value.trim() || getLandingEmail());
        });

        // Prefill
        const savedLandingEmail = getLandingEmail();
        if (savedLandingEmail) {
            landingEmailInput.value = savedLandingEmail;
            fetchLandingNotifications(savedLandingEmail);
        }
    </script>

    <!-- Home -->
    <section id="home">
        <div class="container-fluid align-items-center hero">
            <div class="row text-center">
                <div class="col-12 z-5">
                    <div class=" display-1 fw-bold"
                        style="margin-top: 2.3em; z-index: 3; color: white; font-family: 'Poppins'; font-size: 100px;">
                        YMZM</div>
                </div>
                <div class="col-12 mt-1">
                    <!-- <a href="" class="btn btn-outline-secondary rounded-5 px-3 py-1" style="text-decoration: none;">Book
                        Now</a> -->
                    <?php
                    $logged = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
                    ?>
                    <!-- ... -->
                    <?php if (!$logged): ?>
                        <a href="auth.php?next=<?= urlencode('index.php') ?>" class="btn btn-outline-warning rounded-5 px-3 py-1" style="text-decoration: none;">Book Now</a>
                    <?php else: ?>
                        <!-- Book Now on homepage is intentionally hidden for logged in users -->
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="container bg-light p-4 px-5 shadow rounded-4 text-home">
            <h3>About Us</h3>
            <div class="home-text"> At YMZM Event Catering Services, we turn your events into unforgettable experiences 
                through exceptional food and dependable event rentals. Our expertly crafted food menu is designed to delight every guest, 
                while our high-quality rental items—such as tables, chairs, and tableware—ensure your event looks polished and well-organized.

                From intimate gatherings to large-scale celebrations, we take pride in delivering seamless service, 
                attention to detail, and reliable support from start to finish. With YMZM, you can enjoy your special moments 
                while we handle the essentials—making every event flavorful, stylish, and stress-free.
                </p>
            </div>
        </div>
    </section>


    <!-- Food Menu -->
    <section id="menu" class=" my-5 py-4">
        <div class="container-fluid my-5 mb-5" style="position: relative;">
            <div class="row">
                <div class="col-12">
                    <div class="display-4 fw-bold home-text1 home-text text-center text-warning"
                        style="font-family: Poppins;">
                        Food Menu </div>
                </div>
            </div>
            <div class="row mx-auto text-center justify-content-center mb-5">
                <div class="col-lg-7 col-11 text-muted">
                    <small>Our catering menu offers a wide selection of delicious dishes, from savory mains to
                        delectable desserts, perfect for any occasion. Each dish is crafted with fresh ingredients to
                        delight your guests and make every event memorable.</small>
                </div>
            </div>

            <div class="scroll-container ">
                <div class="scroll-content" id="foodCategory"></div>
            </div>
        </div>

        <div class="container-fluid my-5">
            <div class="row text-center d-flex justify-content-center">
                <div class="col-md-4 col-10">
                    <a href="foodmenu.php" class="index-menu-button rounded-5 text-warning px-5 py-2 shadow">View Menu</a>
                </div>
            </div>
        </div>
    </section>


    <!-- Rentals -->
    <section id="rentals">
        <div class="container-fluid my-5 pb-5" style="background-color: #EADCC6;">
            <div class="row">
                <div class="col-9 mx-auto text-center p-5">
                    <div class="display-4 fw-bold home-text1 home-text text-center text-dark"
                        style="font-family: Poppins;">
                        Rentals </div>
                    <p class="text-muted">High-quality event rentals to complement your catering from tables and chairs
                        to décor and serving essentials, we provide
                        everything you need for a seamless celebration.</p>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row g-3 justify-content-center pb-5" id="rentalsRow">
                </div>
                <div class="row text-center d-flex justify-content-center">
                    <div class="col-md-4 col-10">
                        <a href="rentals.php" class="index-menu-button rounded-5 px-5 py-2 text-warning shadow">View More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="my-5">
        <hr style="width: 90%; margin: 0 auto;">
        <div class="container text-start my-5">
            <h2 class="fw-bold mb-2 mt-3 px-3">How It Works</h2>
            <p class="mb-5 px-3">
                Planning your event is easy with our simple step-by-step process—from selecting food and rentals to event-day service.
            </p>
            <div class="row justify-content-center gy-5">
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-list-ul fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Choose Food & Rentals</h5>
                        <p class="text-center">Browse our food menu and select the rental items you need for your event.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-pencil-square fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Customize Your Order</h5>
                        <p class="text-center">Adjust quantities, add special requests, and provide your event details.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-receipt fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Request a Quote</h5>
                        <p class="text-center">Receive a clear and detailed quotation based on your selected food and rentals.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-check2-circle fs-1 mb-1"></i>   
                        <h5 class="fw-bold text-center pb-3">Order Confirmation</h5>
                        <p class="text-center">Our team reviews and confirms your order to ensure everything is accurate.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-truck fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Event Day Service</h5>
                        <p class="text-center">We prepare, deliver, set up, and collect rentals so you can enjoy your event stress-free.</p>
                    </div>
                </div>
            </div>
        </div>
        <hr style="width: 90%; margin: 0 auto;">
    </section>

    <!-- why choose us -->
    <section id="why-choose-us" class="choose-us my-5 py-4" style="position: relative; z-index: 1; background-image: url('images/hero-choose-us.jpg');">
        <div class="container content text-center text-light" style="position: relative; z-index: 1;">
            <h2 class="fw-bold mb-4 mt-3 text-start px-3">Why Choose Us</h2>
            <ul class="list-unstyled fs-5 text-start px-5">
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Experienced and professional catering team
                </li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Customizable food menu
                </li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>High-quality ingredients and presentation
                </li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Reliable and timely service</li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Comprehensive rental options</li>
            </ul>
        </div>
    </section>


    <!-- Contact Section -->
    <section id="contact">
        <div class="container-fluid py-5 mt-5">
            <div class="display-5 fw-semibold text-center text-dark pb-2">Make Your Event Hassle-Free</div>
            <div class="row d-flex justify-content-center">
                <div class="col-md-10 text-center text-dark">
                    <p class=" mx-5 text-muted">Planning an event doesn’t have to be stressful. Contact us for
                        personalized catering solutions, expert guidance,
                        and reliable service from start to finish.
                        We’re here to take care of the details so you can enjoy the moment.</p>
                    <div class="text-center mt-4">
                        <button type="button" class=" index-menu-button btn-light rounded-pill px-5 py-2 shadow text-warning fw-bold" data-bs-toggle="modal" data-bs-target="#contactModal">
                            Contact Us
                        </button>
            </div>
    </section>
    <!-- Contact Modal -->
<?php include("contact.php"); ?>

    <!-- Contact Toast (match add-to-cart style) -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="contactToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="contactToastMessage">Message sent!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php
        $isClientLoggedIn = (!empty($_SESSION['role']) && $_SESSION['role'] !== 'admin') && (!empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']));
    ?>
    <?php if ($isClientLoggedIn): ?>
        <!-- Notifications Widget (client only) -->
        <div id="notifFab" class="notif-fab" title="Notifications">
            <i class="bi bi-bell-fill fs-4"></i>
            <span class="notif-badge" id="notifBadge"></span>
        </div>
        <div id="notifPanel" class="notif-panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="m-0">Order Notifications</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="notifClose">Close</button>
            </div>
            <div class="mb-2 d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-secondary" id="notifRefresh">Refresh</button>
            </div>
            <div id="notifList" class="d-flex flex-column gap-2" style="overflow-y:auto; max-height:50vh;"></div>
        </div>
    <?php endif; ?>

        <!-- Footer -->
    <?php include("footer.php"); ?>

    <script src="data.js"></script>

    <script>
        // Rentals Section
        var rentalsRow = document.getElementById("rentalsRow");

        const rentalGroupKeyByTitle = {
            'tenting': 'tent',
            'chairs': 'chairs',
            'tableware & utensils': 'tableware-utensils',
            'tables': 'tables',
            'fabric & linens': 'fabric-linens',
            'containers': 'containers',
            'decorations': 'decorations',
            'tables & chair setup': 'tables-and-chairs'
        };

        rentals.rntCategories.forEach(rntCategory => {
            const title = (rntCategory.title || '').trim();
            const groupKey = rentalGroupKeyByTitle[title.toLowerCase()] || '';
            const hashTarget = groupKey || title.replace(/\s+/g, '');
            rentalsRow.innerHTML += `
        <div class="col-md-4 col-lg-3 col-10 mb-3">
            <a href="rentals.php#` + hashTarget + `" class="text-decoration-none" style="display:block;">
                <div class="card border rental-card rounded-4" style="height: 260px; overflow: hidden;">
                    <img src="` + rntCategory.img + `" class="card-img-center" alt="` + title + `" style="height: 100%; object-fit: cover;">
                    <div class="card-img-overlay d-flex justify-content-center align-items-center p-2">
                        <p class="fs-5 rounded-5 px-2 m-0 text-white">` + title + `</p>
                    </div>
                </div>
            </a>
        </div>
    `;
        });

        // food menu 
        const categories = [{
                thumb: "images/Food Menu/food1.jpg"
            },
            {
                thumb: "images/Food Menu/food2.jpg"
            },
            {
                thumb: "images/Food Menu/food3.jpg"
            },
            {
                thumb: "images/Food Menu/food4.jpg"
            },
            {
                thumb: "images/Food Menu/food5.jpg"
            },
            {
                thumb: "images/Food Menu/food6.jpg"
            }
        ];
        const foodCategory = document.getElementById("foodCategory");

        function populateImages() {
            for (let repeat = 0; repeat < 3; repeat++) {
                for (let i = 0; i < categories.length; i++) {
                    const cat = categories[i];

                    const imgDiv = document.createElement("div");
                    imgDiv.innerHTML = `
                <a href="#"><img src="` + cat.thumb + `" alt="` + cat.category + `" class="foodImg"></a>
            `;
                    foodCategory.appendChild(imgDiv);
                }
            }
        }

        populateImages();

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

            const openPanelIfRequested = () => {
                const params = new URLSearchParams(window.location.search);
                const shouldOpen = params.get('show') === 'notifications' || (sessionStorage.getItem('openNotifications') === '1');
                if (!shouldOpen) return;

                if (!notifPanel.classList.contains('open')) {
                    togglePanel();
                } else {
                    fetchNotifications();
                }

                try { sessionStorage.removeItem('openNotifications'); } catch (err) {}
                if (params.get('show') === 'notifications') {
                    params.delete('show');
                    const next = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '') + window.location.hash;
                    window.history.replaceState({}, document.title, next);
                }
            };

            notifFab.addEventListener('click', togglePanel);
            notifClose.addEventListener('click', togglePanel);
            notifRefresh.addEventListener('click', () => {
                fetchNotifications();
            });

            // Initial badge load (without forcing the panel open)
            fetchNotifications();
            openPanelIfRequested();
        }
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const status = params.get('contact');
            if (!status || !window.bootstrap) return;

            const toastEl = document.getElementById('contactToast');
            const toastMsg = document.getElementById('contactToastMessage');
            if (!toastEl || !toastMsg) return;

            const success = status === 'sent';
            const message = success
                ? 'Message was sent successfully!'
                : (status === 'invalid' ? 'Please check your contact form details.' : 'Sorry, we could not send your message right now. Please try again.');

            toastMsg.textContent = message;
            toastEl.classList.toggle('text-bg-success', success);
            toastEl.classList.toggle('text-bg-danger', !success);

            const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
            toast.show();

            // If something went wrong, re-open the contact modal so the user can try again.
            if (!success) {
                const modalEl = document.getElementById('contactModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }

            // Clean URL so refresh doesn't re-show the toast.
            params.delete('contact');
            const next = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '') + window.location.hash;
            window.history.replaceState({}, document.title, next);
        })();
    </script>
</body>

</html>