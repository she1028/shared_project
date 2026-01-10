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
<div class="modal fade" tabindex="-1" id="cardModal">
    <div class="modal-dialog modal-dialog-centered modal-lg custom-modal">
        <div class="modal-content p-3" style="background-color: #ede3d4;">
            <div class="modal-body">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-5 col-12 align-items-center">
                        <img src="images/Catering/table/rec1.jpg" class="img-fluid">
                    </div>
                    <!-- Back button -->
                    <div class="col-lg-7 col-12 p-2 mt-2">
                        <div class="d-flex align-items-center back-action g-2" data-bs-dismiss="modal">
                            <i class="material-icons">&#xe5c4;</i>
                            <span>back</span>
                        </div>
                        <!-- Category -->
                        <div class="d-flex align-items-center justify-content-center m-2">
                            <span class="rounded-5 text-center py-1 px-3" style="background-color: #c6c6c6cc; justify-content: center; font-size: 13px;">Tables</span>
                        </div>
                        <div class="row mt-2">
                            <!-- Title -->
                            <div class="h3 fw-bold" style="text-align:jjustify;">Foundry Cocktail Table - Black</div>
                            <!-- details -->
                            <div class="details mt-2">
                                <h5>Details:</h5>
                                <p class="mb-0">28" W x 42" H</p>
                                <p class="mb-0">123 Qwerty IU/UX design iw</p>
                            </div>
                            <!-- description -->
                            <div class="description mt-3">
                                <h5>Description:</h5>
                                <p style="text-align: justify;">Lorem ipsum dolor sit, amet consectetur adipisicing elit. Sint dolorum facilis sit assumenda maxime pariatur molestias, officiis, at suscipit laboriosam eligendi accusantium delectus. Accusantium inventore dolorum provident ut non cum.</p>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row align-items-center">
                            <!-- price -->
                            <div class="col-lg-6 col-12">
                                <h4 class="fw-semibold">Price: $</h4>
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
                                <span class="btn rounded-2 text-center py-1 px-2 ms-5" style="background-color: #c6c6c6cc; justify-content: center;">add to cart</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.openModal = function() {
        const modalEl = document.getElementById('cardModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
</script>