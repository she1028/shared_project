<?php
// Database-driven footer settings (falls back to static content if DB/table/row missing)
$footer = null;
try {
    require_once __DIR__ . '/connect.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'footer_settings'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $res = $conn->query("SELECT * FROM footer_settings ORDER BY updated_at DESC, id DESC LIMIT 1");
            if ($res) {
                $footer = $res->fetch_assoc();
            }
        }
    }
} catch (Throwable $e) {
    $footer = null;
}

$footerLogo = $footer['logo_path'] ?? 'images/YMZM-logo.png';
$footerBusinessName = $footer['business_name'] ?? 'YMZM Catering Services';
$footerCopyright = $footer['copyright_text'] ?? '&copy; 2026 Copyright: YMZM Catering Services';
$footerHours = $footer['business_hours'] ?? '9:00 AM - 7:00 PM | Monday to Friday';
$footerAddress = $footer['address'] ?? '123 Main Street, City, Country';
$footerCateringPhone = $footer['catering_phone'] ?? 'Catering Services 0912 XXX XXXX | 09XX XXX XXXX';
$footerFoodOrderPhone = $footer['food_order_phone'] ?? 'Food Order 09XX XXX XXXX | 09XX XXX XXXX';
$footerInstagram = $footer['instagram_url'] ?? 'https://instagram.com';
$footerX = $footer['x_url'] ?? 'https://twitter.com';
$footerFacebook = $footer['facebook_url'] ?? 'https://facebook.com';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- footer section -->
<footer class="contact-section py-5 p-5 pb-1 bg-dark">
    <div class="container text-center text-white px-1">
        <div class="row gy-4">
            <div class="Logo-end p-2 col-md-3">
                <a>
                    <img src="<?= htmlspecialchars($footerLogo) ?>" alt="<?= htmlspecialchars($footerBusinessName) ?>" width="130" height="130" class="mb-1">
                </a>
            </div>
            <div class="col-md-5">
                <ul class="list-unstyled text-light text-start mb-0">
                    <li class="mb-2 d-flex align-items-center">
                        <i class="bi bi-clock me-2"></i>
                        <span><?= htmlspecialchars($footerHours) ?></span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="bi bi-geo-alt me-2"></i>
                        <span><?= htmlspecialchars($footerAddress) ?></span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="bi bi-telephone me-2"></i>
                        <span><?= htmlspecialchars($footerCateringPhone) ?></span>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="bi bi-telephone me-2"></i>
                        <span><?= htmlspecialchars($footerFoodOrderPhone) ?></span>
                    </li>
                </ul>
            </div>
        </div>
        <section class="p-3 pt-0">
            <hr class="mt-2">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="p-3 w-100 w-md-auto">
                    <p class="mb-0 text-start">
                        <?= $footerCopyright ?>
                    </p>
                </div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-md btn-outline-secondary rounded-circle m-1 d-flex align-items-center justify-content-center"
                       role="button" href="<?= htmlspecialchars($footerInstagram) ?>" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a class="btn btn-md btn-outline-secondary rounded-circle m-1 d-flex align-items-center justify-content-center"
                       role="button" href="<?= htmlspecialchars($footerX) ?>" aria-label="X">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                    <a class="btn btn-md btn-outline-secondary rounded-circle m-1 d-flex align-items-center justify-content-center"
                       role="button" href="<?= htmlspecialchars($footerFacebook) ?>" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>
</footer>

<?php include_once(__DIR__ . '/includes/chatbot.php'); ?>
<?php include_once(__DIR__ . '/includes/notifications-widget.php'); ?>

