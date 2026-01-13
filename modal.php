<link href='https://fonts.googleapis.com/css?family=Oranienbaum' rel='stylesheet'>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

<style>
    .root {
        font-family: 'Poppins', sans-serif;
    }
    .inclusion-modal {
        border-radius: 12px;
    }

    .inclusion-header {
        position: relative;
    }

    .inclusion-image {
        max-height: 260px;
        object-fit: cover;
        border-radius: 12px 12px 0 0;
        filter: blur(1.5px);
    }

    .inclusion-close {
        position: absolute;
        top: 12px;
        right: 12px;
        background: white;
        border-radius: 50%;
    }

    .section-title {
        font-family: 'Oranienbaum', serif;
        letter-spacing: 0.25em;
        font-size: 26px;
        color: #7A5A28;
    }

    .inclusion-list li {
        margin-bottom: 6px;
        font-size: 15px;
    }

    .section-divider {
        margin: 32px 0;
    }
</style>

<!-- Full Inclusion Modal -->
<div class="modal fade" id="inclusionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content m-4">

      <div class="position-relative inclusion-header">
        <img id="inclusionImage" src="" alt="Package Image" class="img-fluid w-100 inclusion-image" style="height: 140px;">
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-white" style="background: rgba(0,0,0,0.25); border-radius: 12px 12px 0 0;">
          <h6 id="offerLabel" class="mb-0" style="letter-spacing:0.25em;"></h6>
          <h2 id="inclusionTitle" class=" mb-0 display-4" style="font-family: 'Oranienbaum';"></h2>
        </div>
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- body -->
      <div class="modal-body px-4 py-4" style="background: #ffffffff;">
        <div class="text-center mb-3">
          <h4 id="inclusionPrice" class="mb-1" style="color:#7A5A28; font-family: 'Oranienbaum'; font-size:30px"></h4>
          <p id="inclusionNote" class="text-muted small mb-4"></p>
        </div>

        <div class="container">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8">

              <div class="my-4">
                <div class="d-flex align-items-center mb-2">
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                  <span class="px-3 section-title">MENU</span>
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                </div>
                <ul id="menuList" class="list-unstyled text-center"></ul>
              </div>

              <div class="my-4">
                <div class="d-flex align-items-center mb-2">
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                  <span class="px-3 section-title">RENTALS</span>
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                </div>
                <ul id="rentalsList" class="list-unstyled text-center"></ul>
              </div>

              <div class="my-4">
                <div class="d-flex align-items-center mb-2">
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                  <span class="px-3 section-title">DECORATIONS</span>
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                </div>
                <ul id="decorationsList" class="list-unstyled text-center"></ul>
              </div>

              <div class="my-4">
                <div class="d-flex align-items-center mb-2">
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                  <span class="px-3 section-title">SERVICES</span>
                  <hr class="flex-grow-1" style="border-color:#C9A24D;">
                </div>
                <ul id="servicesList" class="list-unstyled text-center"></ul>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
async function openInclusionModal(packageId) {
  const category = typeof ACTIVE_PACKAGE_TYPE !== "undefined" ? ACTIVE_PACKAGE_TYPE : "";
  const normalizedId = (packageId || "").toString().toLowerCase();

  const dataSource = (typeof loadPackageInclusions === "function")
    ? await loadPackageInclusions(category)
    : (window.inclusionMap ? window.inclusionMap[category] : []);

  if (!Array.isArray(dataSource) || !dataSource.length) return;

  const inclusion = dataSource.find(p => (p.id || p.slug || "").toLowerCase() === normalizedId);
  if (!inclusion) return;

  offerLabel.textContent = inclusion.offer;
  inclusionTitle.textContent = inclusion.title;
  inclusionImage.src = inclusion.image;
  inclusionPrice.textContent = inclusion.price;
  inclusionNote.textContent = inclusion.note;

  const fill = (id, items) => {
    const ul = document.getElementById(id);
    ul.innerHTML = "";
    (items || []).forEach(item => ul.insertAdjacentHTML("beforeend", `<li>${item}</li>`));
  };

  fill("menuList", inclusion.menu);
  fill("rentalsList", inclusion.rentals);
  fill("decorationsList", inclusion.decorations);
  fill("servicesList", inclusion.services);

  new bootstrap.Modal(inclusionModal).show();
}
</script>