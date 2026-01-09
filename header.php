<style>
    /* Reusable Hero Section */
    .hero {
        position: relative;
        width: 100%;
        height: 300px;
        background-repeat: no-repeat;
        background-size: cover;
        background-position: top;
        overflow: hidden;
    }

    .hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: inherit;
        background-size: cover;
        background-position: center;
        filter: blur(3px) brightness(70%);
        transform: scale(1.1);
        z-index: 0;
    }

    .hero-header {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: white;
        z-index: 1;
    }

    .package-title {
        font-family: 'Poppins';
        letter-spacing: 0.4em;
        font-size: 16px;
    }

    .package-name {
        font-family: 'Oranienbaum';
    }

    .package-description {
        font-size: 16px;
    }

    .btn-primary-custom {
        color: white;
    }
</style>

<!-- Hero Section -->
<section id="home">
    <div class="container-fluid hero d-flex align-items-center"
         style="background-image: url('<?= $heroImage ?>');">

        <div class="row text-center hero-header">
            <div class="col-12">
                <h1 class="display-5 display-md-3 display-lg-1 package-name pt-5">
                    <?= $heroTitle ?>
                </h1>
            </div>

            <div class="col-12">
                <div class="py-1 package-description">
                    <?= $heroBreadcrumb ?>
                </div>
            </div>
        </div>
    </div>
</section>
