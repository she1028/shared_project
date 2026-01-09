<?php $packageType = "wedding";?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YMZM | Wedding Packages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oranienbaum' rel='stylesheet'>

    <style>
        /* Hero Section */
        .hero-wedding {
            position: relative;
            background-image: url('images/packages/wedding_header.jpg');
            width: 100%;
            height: 300px;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: top;
            overflow: hidden;
        }

        .hero-wedding::before {
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

        .header-wedding {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
        }

        .package-title {
            font-family: 'poppins';
            letter-spacing: 0.4em;
        }

        .package-name {
            font-family: 'Oranienbaum';
        }

        .package-description {
            font-size: 16px;
        }

        .btn-primary-custom {
            background-color: #6D4302;
            color: white;
        }
    </style>

    <script>
    const ACTIVE_PACKAGE_TYPE = "<?php echo $packageType; ?>";
</script>
</head>

<body>
    <!-- Navbar -->
    <?php include("nav.php"); ?>

    <!-- Hero Section -->
    <section id="home">
        <div class="container-fluid hero-wedding d-flex align-items-center">
            <div class="row text-center header-wedding">
                <div class="col-12">
                    <div class="fw-bold text-white" style="font-family: 'Oranienbaum';">
                        <h1 class="display-5 display-md-3 display-lg-1 pt-5">Wedding Catering Packages</h1>
                    </div>
                </div>
                <div class="col-12">
                    <div class="py-1" style="font-size: 16px;">
                        <a href="index.html#home" class="text-white text-decoration-none">Home</a>
                        <span style="color:#ffffff;"> &lt; </span>
                        <a href="wedding.php" class="text-white text-decoration-none" style="color:#ca9292;">Wedding Packages</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <div id="packagesContainer"></div>

    <!-- Full Inclusion Modal -->
    <?php include("modal.php"); ?>

    <script src="packages.js"></script>
    <script src="inclusion.js"></script>
    <script>
        const container = document.querySelector('#packagesContainer');

        function renderPackage(pkg) {
            const html = `
            <div class="container-fluid">
                <div class="row my-5 p-5 shadow-sm" style="background-color: ${pkg.backgroundColor || '#EADCC6'};">
                    <div class="d-flex flex-column flex-lg-row align-items-center gap-4">
                        <div class="flex-fill h-100 text-center d-flex align-items-center">
                            <img src="${pkg.image}" alt="wedding" class="img-fluid"
                                style="max-height:${pkg.maxImageHeight}; width: 100%; object-fit: contain;">
                        </div>
                        <div class="flex-fill h-100 d-flex align-items-center">
                            <div>
                                <h6 class="package-title">${pkg.packageTitle}</h6>
                                <h1 class="package-name display-5">${pkg.packageName}</h1>
                                <p class="pt-1 package-description">
                                    ${pkg.description}
                                    <p class="text-muted mb-1">${pkg.note}</p>
                                    <button class="btn btn-sm btn-primary-custom" onclick="openInclusionModal('${pkg.id}')">${pkg.buttonText}</button>
                                </p>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between align-items-center" style="font-size: 20px;">
                                    <div>
                                        <span style="font-family: 'poppins'; font-size: 16px;">Starts at </span><span class="fs-2" style="font-family: 'Oranienbaum';">${pkg.currency}${pkg.startsAt}</span>
                                    </div>
                                    <a href="${pkg.bookLink}" class="btn btn btn-primary-custom">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', html);
        }

        weddingPackages.forEach(pkg => renderPackage(pkg));
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>
</html>