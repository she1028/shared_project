<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YMZM | Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oranienbaum' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <?php include("nav.php"); ?>

    <!-- Hero Section -->
    <?php
    $heroTitle = "Rentals";
    $heroImage = "images/FoodMenuPage/hero.jpg";

    $heroBreadcrumb = '
    <a href="index.php#home" class="text-white text-decoration-none">Home</a>
    <span style="color:#ffffff;"> &lt; </span>
    <a href="rentals.php"
       class="text-white text-decoration-none"
       style="color:#ca9292;">
        Rentals
    </a>';

    include("header.php");
    ?><!--  -->

    <div class="mt-4 me-5" style="display:flex; align-items:center; gap:18px; justify-content:right;">
        <div class="rounded-5 p-2" style="border:1px solid #000000; display:flex; align-items:center; width:350px;">
            <input type="text" id="searchInput" placeholder="Search" style="border:none; outline:none; width:100%; font-size:15px;">
            <span id="clearBtn" style="position: right; right:12px; cursor:pointer; font-size:18px; display:none;">&times;</span>
        </div>
        <i class="bi bi-search" id="searchBtn" style="font-size:23px; cursor:pointer;"></i>
        <a href="cart.php" class="btn" style="text-decoration:none;">
            <i class="bi bi-cart3" style="font-size:23px; cursor:pointer;"></i>
        </a>
    </div>

    <div class="container text-center my-5">
        <h2 class="fw-bold my-3">Browse From Over 100 Rental Products!</h2>
        <div class="mt-1 mb-5">
            Find everything you need to bring your event to life — from tables and chairs to tents, décor, linens, and more. Our wide range of high-quality rental items is perfect for weddings, parties, corporate events, and celebrations of all sizes. Create the setup you want with reliable and stylish rental options.
        </div>
    </div>

    <div class="container text-center my-5" id="content">

    </div>

    <!-- Footer -->
    <?php include("footer.php"); ?>

    <script src="items.js"></script>
    <script>
        var content = document.getElementById("content");
        var searchInput = document.getElementById("searchInput");
        var searchBtn = document.getElementById("searchBtn");
        var introSection = document.querySelector(".container.text-center.my-5"); // intro section
        var clearBtn = document.getElementById("clearBtn");

        // Flatten all items for search purposes
        var allItems = [];
        rentals.rntCategories.forEach(function(category) {
            category.items.forEach(function(item) {
                allItems.push(item);
            });
        });

        // Function to render full category layout (with hr, description, etc.)
        function renderFullCategories() {
            content.innerHTML = "";
            introSection.style.display = "block"; // show intro
            rentals.rntCategories.forEach(function(category) {
                var sectionId = category.category.replace(/\s+/g, "");
                content.innerHTML += `
                <hr class="m-5">
                <h2 class="fw-bold m-3 mb-3">${category.category}</h2>
                <div class="mt-1">${category.description}</div>
                <div class="row row-cols-1 row-cols-md-4 g-4 mt-2" id="${sectionId}"></div>
            `;
                var row = document.getElementById(sectionId);
                category.items.forEach(function(item) {
                    row.innerHTML += `
                    <div class="col">
                        <div class="card border-dark shadow" onclick="openModal()">
                            <img src="${item.img}" class="card-img-top" style="height:200px; width:100%; object-fit: cover; object-position:center; background-color: #f8f9fa;">
                            <div class="card-body text-start">
                                <h5 class="card-title">${item.name}</h5>
                                <p class="card-text">$ ${item.price.toFixed(2)}</p>
                            </div>
                        </div>
                    </div>
                `;
                });
            });
        }

        // Function to render search results in a flat grid
        function renderSearchResults(items) {
            content.innerHTML = "";
            introSection.style.display = "none"; // hide intro during search

            if (items.length === 0) {
                content.innerHTML = `<p class="text-center fw-bold fs-5 my-5">No results found.</p>`;
                return;
            }

            var row = document.createElement("div");
            row.className = "row row-cols-1 row-cols-md-4 g-4";
            content.appendChild(row);

            items.forEach(function(item) {
                var col = document.createElement("div");
                col.className = "col";
                col.innerHTML = `
                <div class="card border-dark shadow" onclick="openModal()" style="background-color: #E2D4D4;">
                    <img src="${item.img}" class="card-img-top" style="height:200px; width:100%; object-fit: cover; object-position:center; background-color: #f8f9fa;">
                    <div class="card-body text-start">
                        <h5 class="card-title">${item.name}</h5>
                        <p class="card-text">$ ${item.price.toFixed(2)}</p>
                    </div>
                </div>
            `;
                row.appendChild(col);
            });
        }

        // Function to handle search
        function searchItems() {
            var query = searchInput.value.trim().toLowerCase();

            clearBtn.style.display = query ? "block" : "none";

            if (query === "") {
                renderFullCategories(); // render original category layout
                return;
            }
            var filtered = allItems.filter(function(item) {
                return item.name.toLowerCase().includes(query);
            });
            renderSearchResults(filtered);
        }

        // Initial render
        renderFullCategories();

        // Event listeners
        searchInput.addEventListener("input", searchItems);
        searchBtn.addEventListener("click", searchItems);
        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") searchItems();
        });

        clearBtn.addEventListener("click", function() {
            searchInput.value = "";
            clearBtn.style.display = "none";
            renderFullCategories(); // restore intro + content
        });
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous">
    </script>

    <?php include 'cardmodal.php' ?>

</body>

</html>