<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YMZM | Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="pages.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oswald' rel='stylesheet'>
</head>

<body>

    <!-- navbar -->
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
                    <li class="nav-item"><a class="nav-link px-3 text-dark border border-primary rounded-5"
                            href="#sign-in">Sign in</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Home -->
    <section id="home">
        <div class="container-fluid align-items-center hero-rentals">
            <div class="row text-center header-rentals">
                <div class="col-12 z-5">
                    <div class=" display-1 fw-bold" style="margin-top: 1.5em; font-family: 'Oswald'; font-size: 70px; z-index: 3; color:#ffffff;">Rentals</div>
                </div>
                <div class="col-12">
                    <div class="fs-5 py-1">
                        <a href="index.html#home" style="text-decoration: none; color:#ffffff;">Home</a>
                        <span style="color:#ffffff;"> &lt; </span>
                        <a href="rentals.html" style="text-decoration: none; color: #ffffff;">Rentals</a>
                    </div>
                </div>
            </div>
        </div>


    </section>

    <div class="mt-4 me-5" style="display:flex; align-items:center; gap:18px; justify-content:right;">
        <div class="rounded-5 p-2" style="border:1px solid #000000; display:flex; align-items:center; width:350px;">
            <input type="text" placeholder="Search" style="border:none; outline:none; width:100%; font-size:15px;">
        </div>
        <i class="bi bi-search" style="font-size:23px; cursor:pointer;"></i>
        <i class="bi bi-cart3" style="font-size:23px; cursor:pointer;"></i>
    </div>

    <div class="container text-center my-5">
        <h2 class="fw-bold my-3">Browse From Over 100 Rental Products!</h2>
        <div class="mt-1 mb-5">
            Find everything you need to bring your event to life — from tables and chairs to tents, décor, linens, and more. Our wide range of high-quality rental items is perfect for weddings, parties, corporate events, and celebrations of all sizes. Create the setup you want with reliable and stylish rental options.
        </div>
    </div>

    <div class="container text-center my-5" id="content">

    </div>
    <!--modal-->
    <div class="modal fade" tabindex="-1" id="cardModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-body">
                    <div class="row align-items-center">
                        <div class="col-md-6 align-items-center">
                            <img src="Catering/table/rec1.jpg" class="img-fluid">
                        </div>
                        <div class="col-md-6">
                            <div class="btn" data-bs-dismiss="modal">back</div>
                            <div class="align-items-center px-5 mx-5">
                                <div class="rounded-5 text-center my-2 py-1" style="background-color: gray; justify-content: center;">Tables</div>
                            </div>
                            <div class="row">
                                <div class="h3 fw-bold" style="text-align:jjustify;">Foundry Cocktail Table - Black</div>
                                <div class="details">
                                    <h5>Details:</h5>
                                    <p>Lorem ipsum</p>
                                </div>
                                <div class="description">
                                    <h5>Description:</h5>
                                    <p style="text-align: justify;">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Sint dolorum facilis sit assumenda maxime pariatur molestias, officiis, at suscipit laboriosam eligendi accusantium delectus. Accusantium inventore dolorum provident ut non cum.</p>
                                </div>
                            </div>
                            <hr class="my-5">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h4>Price: $</h4>
                                </div>
                                <div class="col-md-6">
                                    <h6>Available In: </h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" id="qty-minus" style="margin:0 8px;">-</button>
                                    <span id="modal-qty border">1</span>
                                    <button type="button" id="qty-plus" style="margin:0 8px;">+</button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" id="add-to-cart-btn" style="flex:1;background:#23272f;color:#fffbe7;font-weight:bold;padding:8px 0;border:none;border-radius:6px;cursor:pointer;">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div> -->
            </div>
        </div>
    </div>

    <!-- footer -->
    <section class="mt-5">
        <div class="container-fluid bg-dark">
            <div class="container">
                <div class="row d-flex justify-content-center text-center pb-3">
                    <div class="col-lg-2 col-5">
                        <img src="images/YMZM-logo.png" class="w-75 pt-5">
                    </div>
                    <div class="col-10 pt-5">
                        <ul class="list-unstyled text-light text-start">
                            <li><i class="bi bi-clock" style="width: 1em;"></i> 9:00 AM - 7:00 PM | Monday to Friday
                            </li>
                            <li><i class="bi bi-geo-alt" style="width: 1em;"></i> 123 Main Street, City, Country</li>
                            <li><i class="bi bi-telephone" style="width: 1em;"></i> Catering Services 0912 XXX XXXX |
                                09XX XXX XXXX</li>
                            <li><i class="bi bi-telephone" style="width: 1em;"></i> Food Order 09XX XXX XXXX | 09XX XXX
                                XXXX</li>
                        </ul>
                    </div>
                </div>
                <hr style="margin: 0 auto;" class="text-light">
                <div class="row">
                    <div class="col text-light d-flex justify-content-between py-3">
                        <p>@2025 copy right.</p>
                        <div>
                            <a href="#"><i class="bi bi-instagram fs-3 text-light"></i></a>
                            <a href="#"><i class="bi bi-facebook fs-3 text-light"></i></a>
                            <a href="#"><i class="bi bi-x fs-3 text-light"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="items.js"></script>
    <script>
        var content = document.getElementById("content");

        for (var i = 0; i < rentals.rntCategories.length; i++) {
            var category = rentals.rntCategories[i];
            var sectionId = category.category.replace(/\s+/g, "");

            content.innerHTML += `
            <hr class="m-5">
            <h2 class="fw-bold m-3 mb-3">` + category.category + `</h2>
            <div class="mt-1">` + category.description + `</div>
            <div class="row row-cols-1 row-cols-md-4 g-4 mt-2" id="` + sectionId + `"></div>`;

            var row = document.getElementById(sectionId);

            for (var j = 0; j < category.items.length; j++) {
                var item = category.items[j];
                row.innerHTML += `
                <div class="col">
                    <div class="card border-dark shadow" onclick="openModal()">
                        <img src="` + item.img + `" class="card-img-top" style="height:200px; width:100%; object-fit: cover; object-position:center; background-color: #f8f9fa;">
                        <div class="card-body text-start">
                            <h5 class="card-title">` + item.name + `</h5>
                            <p class="card-text">$ ` + item.price.toFixed(2) + `</p>
                        </div>
                    </div>
                </div>`;
            }
        }

        function openModal() {
            const modal = new bootstrap.Modal(document.getElementById('cardModal'));
            modal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous">
    </script>

</body>

</html>