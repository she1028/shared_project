<?php
// nav.php - navbar that shows Sign In when guest, user name + dropdown when logged in
// Place this file in your project root (same path used by include("nav.php"))
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Reliable logged-in detection: accept several session key names
$isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
$userName = $_SESSION['name'] ?? ($_SESSION['username'] ?? '');
$userEmail = $_SESSION['email'] ?? '';

$cartCount = 0;
$cart = $_SESSION['cart'] ?? [];
if (is_array($cart)) {
    foreach ($cart as $it) {
        $cartCount += (int)($it['qty'] ?? 1);
    }
}

$currentUri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php', ENT_QUOTES, 'UTF-8');
$authUrl = 'auth.php?next=' . urlencode($currentUri);
?>
<style>
    .navbar .nav-link {
        position: relative;
        border-radius: 9999px;
        transition: color 150ms ease, background-color 150ms ease, transform 150ms ease;
    }

    .navbar .nav-link::after {
        content: "";
        position: absolute;
        left: 12px;
        right: 12px;
        bottom: 6px;
        height: 2px;
        background: #3E2723;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 180ms ease;
        opacity: 0.9;
        width: 4em;
       
    }

    .navbar .nav-link:hover,
    .navbar .nav-link:focus-visible {
        color: black !important;
        transform: translateY(-1px);
    }

    .navbar .nav-link:hover::after,
    .navbar .nav-link:focus-visible::after {
        transform: scaleX(1);
    }

    /* Special hover for Sign in */
    .navbar .sign-in-link::after {
        display: none;
    }

    .navbar .sign-in-link {
        transition: background-color 160ms ease, color 160ms ease, transform 160ms ease, box-shadow 160ms ease,
            border-color 160ms ease;
    }

    .navbar .sign-in-link:hover,
    .navbar .sign-in-link:focus-visible {
        background: #EADCC6;
        border-color: #3E2723 !important;
        color: #fff !important;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(62, 39, 35, 0.28);
    }

    .cart-icon {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 9999px;
    }

    .cart-badge {
        position: absolute;
        top: 3px;
        right: 3px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9999px;
        background: #dc3545;
        color: #fff;
        font-size: 12px;
        line-height: 18px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #f8f9fa;
    }
</style>

<nav class="navbar navbar-expand-lg bg-light navbar-light fixed-top shadow-sm mt-3 rounded-4 mx-4">
    <div class="container-fluid ps-4 pe-4">
        <a class="navbar-brand" href="index.php">
            <img src="images/YMZM-logo.png" alt="Logo" class="logo" width="40" height="auto">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#home">Home</a></li>
                <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#menu">Menu</a></li>
                <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#rentals">Rentals</a></li>
                <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#contact">Contact</a></li>
                <!-- <li class="nav-item"><a class="nav-link sign-in-link px-3 text-dark border border-primary rounded-5"
                            href="#sign-in">Sign in</a></li> -->
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link sign-in-link px-3 text-dark border border-primary rounded-5 dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <!-- small circle with initial -->
                            <span style="display:inline-block;width:34px;height:34px;background:#333;color:#fff;border-radius:50%;text-align:center;line-height:34px;font-weight:600;margin-right:8px;">
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            </span>
                            <?= htmlspecialchars($userName ?: 'Profile', ENT_QUOTES) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li class="dropdown-item-text"><strong><?= htmlspecialchars($userName ?: '') ?></strong></li>
                            <li class="dropdown-item-text"><?= htmlspecialchars($userEmail ?: '') ?></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <a href="cart.php" class="btn cart-icon" style="text-decoration:none;">
                        <i class="bi bi-cart3" style="font-size:23px; cursor:pointer;"></i>
                        <span class="cart-badge" data-cart-badge="1" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= (int)$cartCount ?></span>
                    </a>
                <?php else: ?>
                    <a href="cart.php" class="btn cart-icon" style="text-decoration:none;">
                        <i class="bi bi-cart3" style="font-size:23px; cursor:pointer;"></i>
                        <span class="cart-badge" data-cart-badge="1" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= (int)$cartCount ?></span>
                    </a>
                    <li class="nav-item">
                        <a class="nav-link sign-in-link px-3 text-dark border border-primary rounded-5" href="<?= $authUrl ?>">Sign In / Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
(() => {
    function setCartCount(count) {
        const n = Number(count);
        const safe = Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
        document.querySelectorAll('[data-cart-badge="1"]').forEach((el) => {
            el.textContent = safe;
            el.style.display = safe > 0 ? 'inline-flex' : 'none';
        });
    }

    async function updateCartCount() {
        try {
            const res = await fetch('api/get_cart_count.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success) setCartCount(data.cart_count);
        } catch (e) {
            // ignore
        }
    }

    window.setCartCount = setCartCount;
    window.updateCartCount = updateCartCount;
})();
</script>