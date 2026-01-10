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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#packages">Packages</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#menu">Menu</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#rentals">Rentals</a></li>
                    <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link sign-in-link px-3 text-dark border border-primary rounded-5"
                            href="#sign-in">Sign in</a></li>
                </ul>
            </div>
        </div>
    </nav>