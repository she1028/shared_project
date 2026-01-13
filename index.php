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

<style>
/* Position landing notification button on bottom-right opposite chatbot */
.landing-notif-fab {
    position: fixed;
    bottom: 20px;
    right: 1em; /* adjust distance from chatbot */
    background-color: #e83e8c;
    color: white;
    border-radius: 50%;
    width: 55px;
    height: 55px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    z-index: 1050;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.landing-notif-fab:hover {
    /* background-color: #ffffff; */
    transform: scale(1.1);
}

.landing-notif-panel {
    position: fixed;
    bottom: 85px; /* above the button */
    right: 90px;
    width: 320px;
    max-height: 400px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    padding: 15px;
    display: none;
    flex-direction: column;
    z-index: 1050;
    overflow: hidden;
}

.landing-notif-panel.open {
    display: flex;
}

.landing-notif-panel .notif-item {
    border-bottom: 1px solid #ddd;
    padding: 5px 0;
}

.landing-notif-panel .status-pill.paid { background-color: #28a745; color: white; padding: 2px 5px; border-radius: 5px; }
.landing-notif-panel .status-pill.shipped { background-color: #ffc107; color: white; padding: 2px 5px; border-radius: 5px; }
.landing-notif-panel .status-pill.pending { background-color: #6c757d; color: white; padding: 2px 5px; border-radius: 5px; }
</style>

<script>
const landingFab = document.getElementById('landingNotifFab');
const landingPanel = document.getElementById('landingNotifPanel');
const landingClose = document.getElementById('landingNotifClose');
const landingEmailInput = document.getElementById('landingNotifEmail');
const landingList = document.getElementById('landingNotifList');
const landingSave = document.getElementById('landingNotifSave');
const landingRefresh = document.getElementById('landingNotifRefresh');
const landingBadge = document.getElementById('landingNotifBadge');

const getLandingEmail = () => { return localStorage.getItem('landingNotifEmail') || ''; }
const storeLandingEmail = (email) => { localStorage.setItem('landingNotifEmail', email); }

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
    } catch (err) { console.error(err); }
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
                        style="margin-top: 2.3em; z-index: 3; color: white; font-family: 'Oranienbaum'; font-size: 90px;">
                        CATERING</div>
                </div>
                <div class="col-12 mt-1">
                    <a href="" class="btn btn-outline-secondary rounded-5 px-3 py-1" style="text-decoration: none;">Book Now</a>
                </div>
            </div>
        </div>

        <div class="container bg-light p-4 px-5 shadow rounded-4 text-home">
            <h3>About Us</h3>
            <div class="home-text"> At YMZM Event Catering Services, we are dedicated to making every event unforgettable. We offer a wide range of services, 
                including delicious food catering, stylish event rentals, and creative event design. Whether you need individual services like tables, chairs,
                 and tableware, or a fully customized package from basic to premium, we tailor our offerings to meet your unique needs.

                Our team is committed to delivering smooth, stress-free, and high-quality experiences for gatherings of any size. From intimate 
                celebrations to grand events, we focus on creating memorable moments that leave a lasting impression.</p>
            </div>
        </div>
    </section>

    <!-- Packages -->
    <!-- <section id="packages" class="my-5 pb-5 border" style="background-color:#EADCC6;">
        <div class="text-center text-dark p-5">
            <h2 class="fw-bold display-6 mb-2 mt-3 text-dark" style="font-family: 'Poppins', sans-serif;">
                Packages
            </h2>
            <small class="text-muted">
                Our catering packages are made to fit any celebration big or small, bringing great food and seamless
                service to your special moments.
            </small>
        </div>

        <div class="container">
            <div class="row g-3 justify-content-center" id="packageRow">
            </div>
        </div>
    </section> -->


    <!-- Food Menu -->
    <section id="category" class=" my-5 py-4">
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
                    <h2 class="display-5 fw-bold mb-2 mt-3 text-dark">Rentals</h2>
                    <p class="text-muted">High-quality event rentals to complement your catering from tables and chairs to décor and serving essentials, we provide
                        everything you need for a seamless celebration.</p>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row g-3 justify-content-center pb-5" id="rentalsRow">
                </div>
                <div class="row text-center d-flex justify-content-center">
                    <div class="col-md-4 col-10">
                        <a href="rentals.php" class="index-menu-button rounded-5 px-5 py-2 shadow">View More</a>
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
                Planning your event is simple with our step-by-step catering process—from selection to service.
            </p>
            <div class="row justify-content-center gy-5">
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-box-seam fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Choose a Package</h5>
                        <p class="text-center">Explore our catering packages, menu options, and rental items that suit your event needs.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-pencil-square fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Customize Your Order</h5>
                        <p class="text-center">Personalize your menu, add equipment rentals, and share your event details with us.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-quote fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Request a Quote & Confirm</h5>
                        <p class="text-center">Receive a detailed quotation and secure your booking with a reservation payment.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-check2-circle fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Catering Head Confirmation</h5>
                        <p class="text-center">Our catering head carefully reviews and confirms all event details for accuracy.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex justify-content-center">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center border rounded-4 shadow p-3 hiw-card">
                        <i class="bi bi-truck fs-1 mb-1"></i>
                        <h5 class="fw-bold text-center pb-3">Event Day Service</h5>
                        <p class="text-center">We handle the preparation, delivery, setup, service, and cleanup, so you can relax and enjoy your event.</p>
                    </div>
                </div>
            </div>
        </div>
        <hr style="width: 90%; margin: 0 auto;">
    </section>

    <!-- why choose us no cards only text with bulleted checks but bg of container is picture blur like food-menu -->
    <section id="why-choose-us" class="choose-us my-5 py-4" style="position: relative; z-index: 1;">
        <div class="container content text-center text-light" style="position: relative; z-index: 1;">
            <h2 class="fw-bold mb-4 mt-3 text-start px-3">Why Choose Us</h2>
            <ul class="list-unstyled fs-5 text-start px-5">
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Experienced and professional catering team
                </li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Customizable packages to fit your needs
                </li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>High-quality ingredients and presentation
                </li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Reliable and timely service</li>
                <li class="mb-3"><i class="bi bi-check-circle-fill me-2"></i>Comprehensive rental options for all event
                    needs</li>
            </ul>
        </div>
    </section>


    <!-- Contact Section -->
    <section id="contact">
        <div class="container-fluid py-5 mt-5" style="background-color: #EADCC6;">
            <div class="display-5 fw-semibold text-center text-dark pb-2">Make Your Event Hassle-Free</div>
            <div class="row d-flex justify-content-center">
                <div class="col-md-10 text-center text-dark">
                    <p class=" mx-5 text-muted">Planning an event doesn’t have to be stressful. Contact us for personalized catering solutions, expert guidance,
                        and reliable service from start to finish.
                        We’re here to take care of the details so you can enjoy the moment.</p>
                    <div class="text-center mt-4">
                        <a href="#" class="btn btn-light rounded-pill px-4">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Notifications Widget -->
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
            <input type="email" class="form-control" id="notifEmail" placeholder="Enter your order email" style="flex:1 1 auto;">
            <button class="btn btn-primary" id="notifSave">Save</button>
            <button class="btn btn-outline-secondary" id="notifRefresh">Refresh</button>
        </div>
        <div id="notifList" class="d-flex flex-column gap-2" style="overflow-y:auto; max-height:50vh;"></div>
    </div>

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
                <div class="card border rental-card" style="height: 260px; overflow: hidden;">
                    <img src="` + rntCategory.img + `" class="card-img-center" alt="` + title + `" style="height: 100%; object-fit: cover;">
                    <div class="card-img-overlay d-flex justify-content-center align-items-center p-2">
                        <p class="fs-5 rounded-5 px-2 m-0 text-white">` + title + `</p>
                    </div>
                </div>
            </a>
        </div>
    `;
        });


        // Package Section
    //     var packageRow = document.getElementById("packageRow");

    //     packages.pkgCategories.forEach(pkgCategory => {
    //         let link = "";
    //         switch (pkgCategory.title.toLowerCase()) {
    //             case "wedding":
    //                 link = "wedding.php";
    //                 break;
    //             case "debut":
    //                 link = "debut.php";
    //                 break;
    //             case "kids party":
    //                 link = "children_party.php";
    //                 break;
    //             case "corporate events":
    //                 link = "corporate.php";
    //                 break;
    //         }
    //         packageRow.innerHTML += `
    //     <div class="col-md-4 col-lg-3 col-10">
    //                 <div class="card-pkg card border" style="height: 350px; overflow: hidden;">
    //                     <img src="` + pkgCategory.img + `" class="card-img-top h-100"
    //                         style="object-fit: cover;" alt="Wedding">
    //                     <div class="card-img-overlay d-flex justify-content-start align-items-start p-2">
    //                         <p class="fw-bold text-muted bg-light rounded-5 px-3 mb-0">` + pkgCategory.title + `</p>
    //                     </div>
    //                     <div
    //                         class="card-img-overlay d-flex flex-column justify-content-end text-white text-start explore-text">
    //                         <p class="small mb-2">` + pkgCategory.desc + `
                                
    //                         </p>
    //                         <a href="` + link + `" class="btn btn-light btn-sm"
    //                             style="z-index: 6; color: rgb(231, 87, 231);">View Package</a>
    //                     </div>
    //                 </div>
    //             </div>
    // `;
    //     });

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

        // Notifications widget logic
        const notifFab = document.getElementById('notifFab');
        const notifPanel = document.getElementById('notifPanel');
        const notifClose = document.getElementById('notifClose');
        const notifEmailInput = document.getElementById('notifEmail');
        const notifList = document.getElementById('notifList');
        const notifSave = document.getElementById('notifSave');
        const notifRefresh = document.getElementById('notifRefresh');
        const notifBadge = document.getElementById('notifBadge');

        const getStoredEmail = () => {
            try { return localStorage.getItem('notifEmail') || ''; } catch (e) { return ''; }
        };
        const storeEmail = (email) => {
            try { localStorage.setItem('notifEmail', email); } catch (e) {}
        };

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

        const renderNotifications = (list) => {
            notifList.innerHTML = '';
            if (!list || list.length === 0) {
                notifList.innerHTML = '<div class="notif-empty">No notifications yet. Save your order email to get updates.</div>';
                notifBadge.style.display = 'none';
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
                        <span class="status-pill ${statusClass(n.status)}">${n.status.toUpperCase()}</span>
                        <span class="notif-meta">${dateStr}</span>
                    </div>
                    <div class="mt-1">${escapeHtml(n.message || '')}</div>
                    <div class="notif-meta">Order #${n.order_id}</div>
                `;
                notifList.appendChild(wrap);
            });
            if (unread > 0) {
                notifBadge.textContent = unread;
                notifBadge.style.display = 'flex';
            } else {
                notifBadge.style.display = 'none';
            }
        };

        const fetchNotifications = async (email, markRead = true) => {
            if (!email) {
                notifList.innerHTML = '<div class="notif-empty">Enter your order email to see updates.</div>';
                notifBadge.style.display = 'none';
                return;
            }
            try {
                const res = await fetch(`api/get_notifications.php?email=${encodeURIComponent(email)}`);
                const data = await res.json();
                if (!data.success) {
                    notifList.innerHTML = '<div class="notif-empty">Unable to load notifications.</div>';
                    return;
                }
                renderNotifications(data.notifications);
                if (markRead && data.notifications && data.notifications.length) {
                    const ids = data.notifications.map(n => n.id).join(',');
                    fetch('api/mark_notifications_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `email=${encodeURIComponent(email)}&ids=${encodeURIComponent(ids)}`
                    });
                }
            } catch (err) {
                notifList.innerHTML = '<div class="notif-empty">Unable to load notifications.</div>';
            }
        };

        const togglePanel = () => {
            notifPanel.classList.toggle('open');
            if (notifPanel.classList.contains('open')) {
                fetchNotifications(notifEmailInput.value || getStoredEmail());
            }
        };

        notifFab.addEventListener('click', togglePanel);
        notifClose.addEventListener('click', togglePanel);
        notifSave.addEventListener('click', () => {
            const email = notifEmailInput.value.trim();
            if (!email) return;
            storeEmail(email);
            fetchNotifications(email);
        });
        notifRefresh.addEventListener('click', () => {
            fetchNotifications(notifEmailInput.value.trim() || getStoredEmail(), false);
        });

        // Prefill email from storage
        const savedEmail = getStoredEmail();
        if (savedEmail) {
            notifEmailInput.value = savedEmail;
            fetchNotifications(savedEmail, false);
        }
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>