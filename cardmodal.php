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

<!--modal-->
<div class="modal fade" tabindex="-1" id="foodModal">
    <div class="modal-dialog modal-dialog-centered modal-lg custom-modal">
        <div class="modal-content p-3" style="background-color: #ede3d4;">
            <div class="modal-body">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-5 col-12 align-items-center">
                        <img id="modalFoodImage" class="img-fluid">
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
                            <span id="modalFoodCategory"
                                class="rounded-5 text-center py-1 px-3"
                                style="background-color: #c6c6c6cc; font-size: 13px;">
                            </span>
                        </div>
                        <div class="row mt-2">
                            <!-- Title -->
                            <div id="modalFoodName" class="h3 fw-bold"></div>
                            <!-- details -->
                            <div class="details mt-2">
                                <h5>Details:</h5>
                                <p id="modalFoodServing" class="mb-0"></p>
                                <p id="modalFoodExtra" class="mb-0"></p>

                            </div>
                            <!-- description -->
                            <div class="description mt-3">
                                <h5>Description:</h5>
                                <p id="modalFoodDescription" style="text-align: justify;"></p>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row align-items-center">
                            <!-- price -->
                            <div class="col-lg-6 col-12">
                                <h4 class="fw-semibold">Price: <span id="modalFoodPrice"></span></h4>
                            </div>
                            <!-- availabile colors -->
                            <div class="col-lg-6 col-12">
                                <span class="fs-6">Available In: </span>
                                <span class="color-dot bg-danger"></span>
                                <span class="color-dot bg-primary"></span>
                                <span class="color-dot bg-dark"></span>
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
    function openFoodModal(item) {
        document.getElementById("modalFoodImage").src = item.image;
        document.getElementById("modalFoodName").innerText = item.name;
        document.getElementById("modalFoodDescription").innerText = item.description || "";
        document.getElementById("modalFoodPrice").innerText = "$ " + item.price.toFixed(2);
        document.getElementById("modalFoodServing").innerText = item.serving || "";
        document.getElementById("modalFoodCategory").innerText = item.category || "";

        const modal = new bootstrap.Modal(document.getElementById("foodModal"));
        modal.show();
    }


    let currentFood = null; // currently selected food item
    let currentQty = 1;

    function openFoodModal(item) {
        currentFood = item; // store current item
        currentQty = 1; // reset quantity

        document.getElementById("modalFoodImage").src = item.image;
        document.getElementById("modalFoodName").innerText = item.name;
        document.getElementById("modalFoodDescription").innerText = item.description || "";
        document.getElementById("modalFoodPrice").innerText = "$ " + item.price.toFixed(2);
        document.getElementById("modalFoodServing").innerText = item.serving || "";
        document.getElementById("modalFoodCategory").innerText = item.category || "";

        document.querySelector("#foodModal .qty-box span").innerText = currentQty;

        const modal = new bootstrap.Modal(document.getElementById("foodModal"));
        modal.show();
    }

    // Quantity buttons
    document.getElementById("qty-minus").addEventListener("click", () => {
        if (currentQty > 1) currentQty--;
        document.querySelector("#foodModal .qty-box span").innerText = currentQty;
    });

    document.getElementById("qty-plus").addEventListener("click", () => {
        currentQty++;
        document.querySelector("#foodModal .qty-box span").innerText = currentQty;
    });

   // Add to Cart button
document.querySelector("#foodModal .btn").addEventListener("click", () => {
    if (!currentFood) return;

    const postData = {
        food_id: currentFood.food_id,
        name: currentFood.name,
        price: Number(currentFood.price),
        qty: Number(currentQty),
        image: currentFood.image,
        category: currentFood.category,
        serving: currentFood.serving || ''
    };

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(postData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message); // ✅ user feedback
            updateCartCount();   // dynamically update cart icon/count
            const modalEl = document.getElementById("foodModal");
            bootstrap.Modal.getInstance(modalEl).hide();
        } else {
            alert("Error adding to cart");
        }
    });
});

</script>