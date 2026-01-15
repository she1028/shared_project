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
    <link rel="stylesheet" href="styles.css">
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

    <script>
        const content = document.getElementById('content');
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const introSection = document.querySelector('.container.text-center.my-5');
        const clearBtn = document.getElementById('clearBtn');

        let rentalsData = [];
        let allItems = [];

        function scrollToHashTarget() {
            const hash = (window.location.hash || '').replace('#', '').trim();
            if (!hash) return;
            const target = document.getElementById(decodeURIComponent(hash));
            if (!target) return;

            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function buildCard(item) {
            const col = document.createElement('div');
            col.className = 'col';

            const card = document.createElement('div');
            card.className = 'card border-dark shadow rental-card';
            card.style.backgroundColor = '#E2D4D4';
            card.style.height = '100%';
            card.dataset.item = encodeURIComponent(JSON.stringify(item));

            const colors = Array.isArray(item.colors) ? item.colors : [];
            const colorsHtml = colors.length
                ? colors.map(c => {
                    const name = c.color_name || c.name || 'Color';
                    const stock = typeof c.color_stock === 'number' ? c.color_stock : 0;
                    return `<span class="badge bg-secondary text-dark me-1">${name} (${stock})</span>`;
                }).join('')
                : '<span class="text-muted small">No colors available</span>';

            card.innerHTML = `
                <img src="${item.image}" class="card-img-top" style="height:200px; width:100%; object-fit: cover; object-position:center; background-color: #f8f9fa; filter: none !important;">
                <div class="card-body text-start">
                    <h5 class="card-title">${item.name}</h5>
                    <p class="card-text mb-2">₱ ${Number(item.price).toFixed(2)}</p>
                    <div class="d-flex flex-wrap align-items-center gap-1" style="min-height:30px;">
                        <span class="small fw-semibold me-1">Available:</span>
                        ${colorsHtml}
                    </div>
                </div>
            `;

            card.addEventListener('click', () => {
                const raw = card.dataset.item;
                if (!raw) return;
                try {
                    const parsed = JSON.parse(decodeURIComponent(raw));
                    window.openRentalModal(parsed);
                } catch (e) {}
            });

            col.appendChild(card);
            return col;
        }

        function renderFullCategories() {
            content.innerHTML = '';
            if (introSection) introSection.style.display = 'block';

            rentalsData.forEach(category => {
                const sectionId = (category.group_key || category.group_name).replace(/\s+/g, '');
                const block = document.createElement('div');
                block.innerHTML = `
                    <div id="${sectionId}"></div>
                    <hr class="m-5">
                    <h2 class="fw-bold m-3 mb-3">${category.group_name}</h2>
                    <div class="mt-1">${category.description || ''}</div>
                    <div class="row row-cols-1 row-cols-md-4 g-4 mt-2" id="${sectionId}-row"></div>
                `;
                content.appendChild(block);

                const row = block.querySelector(`#${sectionId}-row`);
                (category.items || []).forEach(item => {
                    row.appendChild(buildCard(item));
                });
            });

            // Handle deep links (e.g., rentals.php#decorations) after DOM is built.
            scrollToHashTarget();
        }

        function renderSearchResults(items) {
            content.innerHTML = '';
            if (introSection) introSection.style.display = 'none';

            if (items.length === 0) {
                content.innerHTML = '<p class="text-center fw-bold fs-5 my-5">No results found.</p>';
                return;
            }

            const row = document.createElement('div');
            row.className = 'row row-cols-1 row-cols-md-4 g-4';
            content.appendChild(row);

            items.forEach(item => row.appendChild(buildCard(item)));
        }

        function searchItems() {
            const query = searchInput.value.trim().toLowerCase();
            clearBtn.style.display = query ? 'block' : 'none';

            if (!query) {
                renderFullCategories();
                return;
            }

            const filtered = allItems.filter(item => item.name.toLowerCase().includes(query));
            renderSearchResults(filtered);
        }

        function hydrateFlatList() {
            allItems = [];
            rentalsData.forEach(cat => {
                (cat.items || []).forEach(it => allItems.push(it));
            });
        }

        // Fetch rentals from API and render
        fetch('api/get_rentals.php')
            .then(res => res.json())
            .then(data => {
                rentalsData = Array.isArray(data) ? data : [];
                hydrateFlatList();
                renderFullCategories();
            })
            .catch(() => {
                content.innerHTML = '<p class="text-danger">Failed to load rentals.</p>';
            });

        // If user changes the hash while on the page, scroll to it.
        window.addEventListener('hashchange', scrollToHashTarget);

        searchInput.addEventListener('input', searchItems);
        searchBtn.addEventListener('click', searchItems);
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') searchItems();
        });

        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            renderFullCategories();
        });
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous">
    </script>

    <?php include 'rentalmodal.php' ?>

</body>

</html>