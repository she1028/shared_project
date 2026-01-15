<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YMZM | Food Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Oranienbaum' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>

    <!-- Navbar -->
    <?php include("nav.php"); ?>

    <!-- Hero Section -->
    <?php
    $heroTitle = "Food Menu";
    $heroImage = "images/RentalPage/hero.png";

    $heroBreadcrumb = '
    <a href="index.php#home" class="text-white text-decoration-none">Home</a>
    <span style="color:#ffffff;"> &lt; </span>
    <a href="foodmenu.php"
       class="text-white text-decoration-none"
       style="color:#ca9292;">
        Food Menu
    </a>';

    include("header.php");
    ?><!--  -->

    <div class="mt-4 me-5" style="display:flex; align-items:center; gap:18px; justify-content:right;">
        <div class="rounded-5 p-2" style="border:1px solid #000000; display:flex; align-items:center; width:350px;">
            <input type="text" id="searchInput" placeholder="Search" style="border:none; outline:none; width:100%; font-size:15px;">
            <span id="clearBtn" style="position: right; right:12px; cursor:pointer; font-size:18px; display:none;">&times;</span>
        </div>
        <i class="bi bi-search" id="searchBtn" style="font-size:23px; cursor:pointer;"></i>
    </div>

    <div class="container text-center my-5">
        <h2 class="fw-bold my-3">Browse Our Wide Selection of Delicious Menu Options!</h2>
        <div class="mt-1">
            Discover a variety of flavorful dishes made with fresh ingredients and lots of love. From comforting classics to exciting specialty meals, our menu is crafted to satisfy every craving and make every occasion more memorable. Enjoy great food that’s perfect for sharing with family and friends.
        </div>
    </div>

    <div class="container text-center my-5" id="content">

    </div>

    <!-- Footer -->
    <?php include("footer.php"); ?>

    <script>
        let content = document.getElementById("content");
        let searchInput = document.getElementById("searchInput");
        let searchBtn = document.getElementById("searchBtn");
        let clearBtn = document.getElementById("clearBtn");
        let introSection = document.querySelector(".container.text-center.my-5");

        let menuSections = [];
        let allItems = [];

        // Fetch menu from backend
        fetch("api/get_food_menu.php")
            .then(res => res.json())
            .then(data => {
                menuSections = data;

                // flatten items for search
                allItems = [];
                data.forEach(section => {
                    section.items.forEach(item => {
                        item.category = section.title;
                        allItems.push(item);
                    });
                });

                renderFullCategories();
            });

        function renderFullCategories() {
            content.innerHTML = "";
            introSection.style.display = "block";

            menuSections.forEach(section => {
                let sectionId = section.title.replace(/\s+/g, "");
                content.innerHTML += `
            <hr class="m-5">
            <h2 class="fw-bold m-3">${section.title}</h2>
            <div class="mt-1">${section.description ?? ""}</div>
            <div class="row row-cols-1 row-cols-md-4 g-4 mt-2" id="${sectionId}"></div>
        `;

                let row = document.getElementById(sectionId);

                section.items.forEach(item => {
                    row.innerHTML += `
        <div class="col">
              <div class="card border-dark shadow" style="background-color:#E2D4D4; cursor:pointer;"
                  onclick='openFoodModal(${JSON.stringify(item)})'>
                <img src="${item.image}" class="card-img-top" style="height:200px; object-fit:cover;">
                <div class="card-body text-start">
                    <h5 class="card-title">${item.name}</h5>
                    <p class="card-text">₱ ${item.price.toFixed(2)}</p>
                </div>
            </div>
        </div>
    `;
                });

            });
        }

        function renderSearchResults(items) {
            content.innerHTML = "";
            introSection.style.display = "none";

            if (items.length === 0) {
                content.innerHTML = `<p class="text-center fw-bold fs-5 my-5">No results found.</p>`;
                return;
            }

            let row = document.createElement("div");
            row.className = "row row-cols-1 row-cols-md-4 g-4";
            content.appendChild(row);

            items.forEach(item => {
                row.innerHTML += `
        <div class="col">
              <div class="card border-dark shadow" style="cursor:pointer;"
                  onclick='openFoodModal(${JSON.stringify(item)})'>
                <img src="${item.image}" class="card-img-top" style="height:200px; object-fit:cover;">
                <div class="card-body text-start">
                    <h5 class="card-title">${item.name}</h5>
                    <p class="card-text">$ ${item.price.toFixed(2)}</p>
                </div>
            </div>
        </div>
    `;
            });

        }

        function searchItems() {
            let query = searchInput.value.trim().toLowerCase();
            clearBtn.style.display = query ? "block" : "none";

            if (!query) {
                renderFullCategories();
                return;
            }

            let filtered = allItems.filter(item =>
                item.name.toLowerCase().includes(query)
            );

            renderSearchResults(filtered);
        }

        searchInput.addEventListener("input", searchItems);
        searchBtn.addEventListener("click", searchItems);
        clearBtn.addEventListener("click", () => {
            searchInput.value = "";
            clearBtn.style.display = "none";
            renderFullCategories();
        });
    </script>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous">
    </script>

    <?php include 'cardmodal.php' ?>

</body>

</html>