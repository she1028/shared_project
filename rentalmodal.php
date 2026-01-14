<style>
    /* .custom-modal {
        max-width: 1000px;
        width: fit-content;
    } */
    .custom-modal .modal-content {
        height: auto;
    }

    .back-action {
        cursor: pointer;
        user-select: none;
        width: fit-content;
    }

    .qty-box {
        display: flex;
        align-items: center;
        border: 1px solid #ccc;
        border-radius: 6px;
        overflow: hidden;
    }

    .qty-box button {
        background: none;
        border: none;
        padding: 6px 12px;
        cursor: pointer;
    }

    .qty-box span {
        padding: 0 12px;
        font-size: 14px;
    }

    .color-chip {
        border: 1px solid #b8b8b8;
        background: #f2f2f2;
        color: #2c2c2c;
        transition: all 0.15s ease;
    }

    .color-chip.active {
        border-color: #8c7a5a;
        background: #d8ceb9;
        color: #1f1f1f;
        box-shadow: 0 0 0 2px #c5b69a60;
    }

    /* Toast styling */
    #cartToastContainer {
        position: fixed;
        bottom: 1rem;
        right: 1rem;
        z-index: 1080;
    }
</style>

<!-- Rental detail modal -->
<div class="modal fade" tabindex="-1" id="rentalModal">
    <div class="modal-dialog modal-dialog-centered modal-lg custom-modal">
        <div class="modal-content p-3" style="background-color: #ede3d4;">
            <div class="modal-body">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-5 col-12 align-items-center">
                        <img id="modalRentalImage" class="img-fluid">
                    </div>
                    <!-- Back button -->
                    <div class="col-lg-7 col-12 p-2 mt-2">
                        <div class="m-2">
                            <span class="d-inline-flex align-items-center back-action g-2" data-bs-dismiss="modal">
                                <i class="material-icons">&#xe5c4;</i>
                                <span>back</span>
                            </span>
                        </div>
                        <!-- Category -->
                        <div class="d-flex align-items-center justify-content-center m-2">
                            <span id="modalRentalCategory"
                                class="rounded-5 text-center py-1 px-3"
                                style="background-color: #c6c6c6cc; font-size: 13px;">
                            </span>
                        </div>
                        <div class="row mt-2">
                            <!-- Title -->
                            <div id="modalRentalName" class="h3 fw-bold"></div>
                            <!-- details -->
                            <div class="details mt-2">
                                <p id="modalRentalServing" class="mb-0"></p>
                                <p id="modalRentalExtra" class="mb-0"></p>
                            </div>
                            <!-- description -->
                            <div class="description mt-3">
                                <p id="modalRentalDescription" style="text-align: justify;"></p>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row align-items-center">
                            <!-- price -->
                            <div class="col-lg-6 col-12">
                                <h4 class="fw-semibold">Price: <span id="modalRentalPrice"></span></h4>
                            </div>
                            <!-- availabile colors -->
                            <div class="col-12 mx-3 justify-content-end ">
                                <span class="fs-6">Available In: </span>
                                <span id="modalRentalColors" class="d-flex flex-wrap gap-2"></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <!-- quantity  -->
                            <div class="qty-box">
                                <button type="button" id="qty-minus">−</button>
                                <span>1</span>
                                <button type="button" id="qty-plus">+</button>
                            </div>
                            <!-- add to cart -->
                            <div class="d-flex align-items-center">
                                <span id="addToCartBtn" class="btn rounded-2 text-center py-1 px-2 ms-5" style="background-color: #c6c6c6cc; justify-content: center;">add to cart</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="cartToastContainer">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="cartToastMessage">
                Item added to cart!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
(() => {
    let currentItem = null;
    let currentQty = 1;
    let selectedColor = null;
    let currentColors = [];

    const qtyDisplay = document.querySelector('#rentalModal .qty-box span');
    const colorsContainer = document.getElementById('modalRentalColors');

    const qtyMinusBtn = document.getElementById('qty-minus');
    const qtyPlusBtn = document.getElementById('qty-plus');
    const addToCartBtn = document.getElementById('addToCartBtn');

    function getMaxQty() {
        // Prefer per-color stock when available.
        const colorStock = selectedColor && typeof selectedColor.color_stock === 'number'
            ? selectedColor.color_stock
            : (selectedColor && typeof selectedColor.color_stock !== 'undefined' ? Number(selectedColor.color_stock) : null);

        if (Number.isFinite(colorStock)) return Math.max(0, Math.floor(colorStock));

        // Fallback to item-level stock if provided.
        const itemStock = currentItem && typeof currentItem.stock !== 'undefined' ? Number(currentItem.stock) : null;
        if (Number.isFinite(itemStock)) return Math.max(0, Math.floor(itemStock));

        return null; // unknown
    }

    function updateQtyUi() {
        const max = getMaxQty();
        if (typeof max === 'number') {
            if (currentQty > max) currentQty = Math.max(1, max);
        }
        if (qtyDisplay) qtyDisplay.innerText = String(currentQty);

        const isOutOfStock = (typeof max === 'number' && max <= 0);
        if (qtyPlusBtn) qtyPlusBtn.disabled = (typeof max === 'number' && currentQty >= max);
        if (qtyMinusBtn) qtyMinusBtn.disabled = currentQty <= 1;

        if (addToCartBtn) {
            addToCartBtn.classList.toggle('disabled', isOutOfStock);
            addToCartBtn.setAttribute('aria-disabled', isOutOfStock ? 'true' : 'false');
        }
    }

    function showToast(message, success = true) {
        const toastEl = document.getElementById('cartToast');
        const toastMessage = document.getElementById('cartToastMessage');
        toastMessage.textContent = message;

        toastEl.classList.toggle('text-bg-success', success);
        toastEl.classList.toggle('text-bg-danger', !success);

        const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
        toast.show();
    }

    window.openRentalModal = function (item) {
        currentItem = item;
        currentQty = 1;

        document.getElementById('modalRentalImage').src = item.image || '';
        document.getElementById('modalRentalName').innerText = item.name || '';
        document.getElementById('modalRentalDescription').innerText = item.description || '';
        document.getElementById('modalRentalPrice').innerText = '₱ ' + Number(item.price || 0).toFixed(2);
        document.getElementById('modalRentalServing').innerText = item.serving || '';
        document.getElementById('modalRentalCategory').innerText = item.category || '';

        if (qtyDisplay) qtyDisplay.innerText = currentQty;

        // Render colors as clickable chips
        colorsContainer.innerHTML = '';
        currentColors = Array.isArray(item.colors) ? item.colors : [];
        selectedColor = null;

        if (currentColors.length === 0) {
            const none = document.createElement('span');
            none.className = 'text-muted';
            none.textContent = 'N/A';
            colorsContainer.appendChild(none);
        } else {
            currentColors.forEach((c, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm rounded-pill color-chip';
                const stock = typeof c.color_stock !== 'undefined' ? ` (${c.color_stock})` : '';
                btn.textContent = (c.color_name || c.name || 'Color') + stock;
                btn.dataset.index = String(idx);
                btn.addEventListener('click', () => {
                    selectedColor = c;
                    updateColorSelection();
                    updateQtyUi();
                });
                colorsContainer.appendChild(btn);
            });
            selectedColor = currentColors[0];
            updateColorSelection();
        }

        updateQtyUi();

        const modal = new bootstrap.Modal(document.getElementById('rentalModal'));
        modal.show();
    };

    if (qtyMinusBtn) {
        qtyMinusBtn.addEventListener('click', () => {
            if (currentQty > 1) currentQty--;
            updateQtyUi();
        });
    }

    if (qtyPlusBtn) {
        qtyPlusBtn.addEventListener('click', () => {
            const max = getMaxQty();
            if (typeof max === 'number' && currentQty >= max) {
                const cn = selectedColor ? (selectedColor.color_name || selectedColor.name || '') : '';
                showToast(`Only ${max} available${cn ? ' in ' + cn : ''}.`, false);
                updateQtyUi();
                return;
            }
            currentQty++;
            updateQtyUi();
        });
    }

    if (addToCartBtn) addToCartBtn.addEventListener('click', () => {
        if (!currentItem) return;

        const max = getMaxQty();
        if (typeof max === 'number') {
            if (max <= 0) {
                const cn = selectedColor ? (selectedColor.color_name || selectedColor.name || '') : '';
                showToast(`Out of stock${cn ? ' for ' + cn : ''}.`, false);
                return;
            }
            if (currentQty > max) {
                const cn = selectedColor ? (selectedColor.color_name || selectedColor.name || '') : '';
                showToast(`Only ${max} available${cn ? ' in ' + cn : ''}.`, false);
                currentQty = Math.max(1, max);
                updateQtyUi();
                return;
            }
        }

        const colorName = selectedColor ? (selectedColor.color_name || selectedColor.name || '') : '';
        const colorId = selectedColor && typeof selectedColor.id !== 'undefined' ? selectedColor.id : null;
        const colorStock = selectedColor && typeof selectedColor.color_stock !== 'undefined' ? selectedColor.color_stock : null;

        const postData = {
            item_id: currentItem.id || currentItem.item_id,
            name: currentItem.name,
            price: Number(currentItem.price),
            qty: Number(currentQty),
            image: currentItem.image,
            category: currentItem.category,
            serving: currentItem.serving || '',
            color_name: colorName,
            color_id: colorId,
            color_stock: colorStock,
            type: 'rental'
        };

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(postData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, true); // replaced alert
                if (typeof updateCartCount === 'function') updateCartCount();
                const modalEl = document.getElementById('rentalModal');
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) instance.hide();
            } else {
                showToast(data.message || 'Error adding to cart', false); // replaced alert
            }
        })
        .catch(() => showToast('Error adding to cart', false)); // replaced alert
    });

    function updateColorSelection() {
        if (!colorsContainer) return;
        const children = Array.from(colorsContainer.children);
        children.forEach((child, idx) => {
            if (!(child instanceof HTMLElement)) return;
            const isActive = selectedColor && currentColors[idx] === selectedColor;
            child.classList.toggle('active', isActive);
        });
    }
})();
</script>
