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

    .color-dot {
        width: 16px;
        height: 16px;
        border-radius: 2px;
        display: inline-block;
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
                                <h5>Details:</h5>
                                <p id="modalRentalServing" class="mb-0"></p>
                                <p id="modalRentalExtra" class="mb-0"></p>

                            </div>
                            <!-- description -->
                            <div class="description mt-3">
                                <h5>Description:</h5>
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
                            <div class="col-lg-6 col-12">
                                <span class="fs-6">Available In: </span>
                                <span id="modalRentalColors" class="d-inline-flex gap-1 align-items-center"></span>
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

<script>
    (() => {
        let currentItem = null;
        let currentQty = 1;

        const qtyDisplay = document.querySelector('#rentalModal .qty-box span');
        const colorsContainer = document.getElementById('modalRentalColors');

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

            // Render colors
            colorsContainer.innerHTML = '';
            const colors = item.colors || [];
            if (colors.length === 0) {
                const none = document.createElement('span');
                none.className = 'text-muted';
                none.textContent = 'N/A';
                colorsContainer.appendChild(none);
            } else {
                colors.forEach(c => {
                    const dot = document.createElement('span');
                    dot.className = 'badge bg-secondary text-dark';
                    const stock = typeof c.color_stock !== 'undefined' ? ` (${c.color_stock})` : '';
                    dot.textContent = (c.color_name || c.name || 'Color') + stock;
                    colorsContainer.appendChild(dot);
                });
            }

            const modal = new bootstrap.Modal(document.getElementById('rentalModal'));
            modal.show();
        };

        document.getElementById('qty-minus').addEventListener('click', () => {
            if (currentQty > 1) currentQty--;
            if (qtyDisplay) qtyDisplay.innerText = currentQty;
        });

        document.getElementById('qty-plus').addEventListener('click', () => {
            currentQty++;
            if (qtyDisplay) qtyDisplay.innerText = currentQty;
        });

        document.getElementById('addToCartBtn').addEventListener('click', () => {
            if (!currentItem) return;

            const postData = {
                item_id: currentItem.id || currentItem.item_id,
                name: currentItem.name,
                price: Number(currentItem.price),
                qty: Number(currentQty),
                image: currentItem.image,
                category: currentItem.category,
                serving: currentItem.serving || '',
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
                        alert(data.message);
                        if (typeof updateCartCount === 'function') {
                            updateCartCount();
                        }
                        const modalEl = document.getElementById('rentalModal');
                        const instance = bootstrap.Modal.getInstance(modalEl);
                        if (instance) instance.hide();
                    } else {
                        alert('Error adding to cart');
                    }
                })
                .catch(() => alert('Error adding to cart'));
        });
    })();
</script>