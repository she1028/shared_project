<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Event Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
    body {
      background-color: #f7f4f1;
      font-family: "Helvetica", "Arial", sans-serif;
      font-size: 14px;
    }

    .form-wrapper {
      background-color: #ffffff;
      border-radius: 1.25rem;
      border: 2px solid #e0d6cf;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
    }

    .info-panel {
      border-radius: 1rem;
      padding: 2rem;
      background-color: #fffefe;
      position: relative;
    }

    .info-legend {
      font-size: 1.15rem;
      font-weight: 700;
      color: #d17d64;
      padding: 0 1rem;
      margin-bottom: 1.5rem;
    }

    .info-panel::after {
      content: "";
      position: absolute;
      inset: 8px;
      border-radius: 0.85rem;
      border: 2px solid rgba(201, 97, 26, 0.3);
      pointer-events: none;
    }

    .info-panel>* {
      position: relative;
      z-index: 1;
    }

    .floating-hint {
      margin-top: 0.15rem;
      font-size: 0.85rem;
      color: #c97a5a;
    }

    .section-divider {
      height: 1px;
      background: linear-gradient(90deg, rgba(201, 97, 26, 0.4), transparent);
      margin: 2.25rem 0;
    }

    .package-price-note {
      color: #c97a5a;
      font-size: 0.9rem;
    }

    .menu-category {
      margin-bottom: 2rem;
    }

    .menu-category h6 {
      font-size: 0.95rem;
      letter-spacing: 0.04em;
      color: #7a4430;
    }

    .menu-note {
      font-size: 0.85rem;
      color: #c97a5a;
    }

    .menu-items .form-check {
      border-radius: 0.65rem;
      padding: 0.35rem 0.65rem;
      background-color: #fdfafa;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .menu-items .form-check-label {
      font-weight: 500;
    }

    .menu-price {
      font-weight: 600;
      color: #c9611a;
      font-size: 0.85rem;
    }

    .notes-area {
      min-height: 140px;
      resize: vertical;
      background-color: #fbf7f5;
      border-color: #eadfda;
    }

    .form-control,
    .form-select,
    .notes-area {
      padding: 0.15rem 0.6rem;
      font-size: 0.9rem;
    }
<<<<<<< HEAD
=======

    .back-action {
        cursor: pointer;
        user-select: none;
        width: fit-content;
    }
>>>>>>> origin/main
  </style>
</head>

<body>
  <div class="m-4">
    <span class="d-inline-flex align-items-center back-action g-2" onclick="history.back()">
      <i class="material-icons">&#xe5c4;</i>
      <span>back</span>
    </span>
  </div>

  <img src="images/YMZM-logo.png" class="mx-auto d-block" height="200" alt="YMZM Logo">

  <div class="container-fluid py-5 form-wrapper mt-4">
    <div class="row">
      <div class="col-12">
        <div class="display-5 text-center fw-semibold">Catering Order Form</div>

        <form action="#" class="mt-5">
          <div class="row g-4">
            <div class="col-12 col-lg-4">
<<<<<<< HEAD
                
              <!-- billing info -->
              <fieldset class="info-panel shadow-sm mb-4" style="height: 97%;">
            <legend class="info-legend">
              <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Billing Information</div>
            </legend>
=======
>>>>>>> origin/main

              <!-- billing info -->
              <fieldset class="info-panel shadow-sm mb-4" style="height: 97%;">
                <legend class="info-legend">
                  <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Billing Information</div>
                </legend>

                <div class="row mb-3 align-items-center">
                  <label for="Name" class="col-sm-3 col-form-label">Name:</label>
                  <div class="col-sm-9">
                    <input type="text" id="Name" name="Name" class="form-control bg-light" required>
                  </div>
                </div>

                <div class="row mb-3 alignments-center">
                  <label for="Email" class="col-sm-3 col-form-label">Email:</label>
                  <div class="col-sm-9">
                    <input type="email" id="Email" name="Email" class="form-control bg-light" required>
                  </div>
                </div>

                <div class="row mb-3 align-items-center">
                  <label for="ContactNo" class="col-sm-3 col-form-label">Contact No:</label>
                  <div class="col-sm-9">
                    <input type="tel" id="ContactNo" name="ContactNo" class="form-control bg-light" required>
                  </div>
                </div>

                <div class="row mb-4 align-items-start">
                  <label class="col-sm-3 col-form-label">Billing Address:</label>
                  <div class="col-sm-9">
                    <div class="mb-2">
                      <select id="BillingProvince" name="BillingProvince" class="form-select bg-light" required>
                        <option value="" selected disabled>Select Province</option>
                        <option value="cebu">Cebu</option>
                        <option value="bohol">Bohol</option>
                        <option value="davao">Davao del Sur</option>
                        <option value="metro-manila">Metro Manila</option>
                      </select>
                      <div class="floating-hint">Province</div>
                    </div>

                    <div class="mb-2">
                      <select id="BillingCity" name="BillingCity" class="form-select bg-light" required>
                        <option value="" selected disabled>Select City</option>
                        <option value="cebu-city">Cebu City</option>
                        <option value="mandaue">Mandaue</option>
                        <option value="tagbilaran">Tagbilaran</option>
                        <option value="davao-city">Davao City</option>
                      </select>
                      <div class="floating-hint">City</div>
                    </div>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <select id="BillingBarangay" name="BillingBarangay" class="form-select bg-light" required>
                          <option value="" selected disabled>Select Barangay</option>
                          <option value="barangay-1">Barangay 1</option>
                          <option value="barangay-2">Barangay 2</option>
                          <option value="barangay-3">Barangay 3</option>
                        </select>
                        <div class="floating-hint">Barangay</div>
                      </div>
                      <div class="col-md-6">
                        <select id="BillingStreet" name="BillingStreet" class="form-select bg-light" required>
                          <option value="" selected disabled>Select Street</option>
                          <option value="main-st">Main St.</option>
                          <option value="maple-ave">Maple Ave.</option>
                          <option value="sunflower-rd">Sunflower Rd.</option>
                        </select>
                        <div class="floating-hint">Street Name</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row mb-2 align-items-start">
                  <label class="col-sm-3 col-form-label">Payment Method:</label>
                  <div class="col-sm-9">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="PaymentMethod" id="pay-cash" value="cash" checked>
                      <label class="form-check-label" for="pay-cash">Cash</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="PaymentMethod" id="pay-paypal" value="paypal">
                      <label class="form-check-label" for="pay-paypal">Paypal</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="PaymentMethod" id="pay-bank" value="bank">
                      <label class="form-check-label" for="pay-bank">Bank</label>
                    </div>
                  </div>
                </div>
              </fieldset>
            </div>
            <div class="col-12 col-lg-8">

              <!-- delivery details -->
              <fieldset class="info-panel shadow-sm mb-4 pb-5">
                <legend class="info-legend">
                  <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Delivery Details</div>
                </legend>

                <div class="row mb-3 align-items-center">
                  <label class="col-sm-3 col-form-label" for="EventDate">Event Date &amp; Time:</label>
                  <div class="col-sm-9">
                    <div class="row g-2">
                      <div class="col-md-4">
                        <input type="date" id="EventDate" name="EventDate" class="form-control bg-light" required>
                        <div class="floating-hint">Date</div>
                      </div>
                      <div class="col-md-4">
                        <input type="time" id="EventTime" name="EventTime" class="form-control bg-light" required>
                        <div class="floating-hint">Hour Minutes</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row mb-3 align-items-center">
                  <label class="col-sm-3 col-form-label">Delivery Type:</label>
                  <div class="col-sm-9 d-flex gap-4">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="DeliveryType" id="delivery-pickup" value="pickup"
                        checked>
                      <label class="form-check-label" for="delivery-pickup">Pick up</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="DeliveryType" id="delivery-delivery"
                        value="delivery">
                      <label class="form-check-label" for="delivery-delivery">Delivery</label>
                    </div>
                  </div>
                </div>

                <div class="row mb-3 align-items-center">
                  <label for="EventAddress" class="col-sm-3 col-form-label">Event Address:</label>
                  <div class="col-sm-9">
                    <input type="text" id="EventAddress" name="EventAddress" class="form-control bg-light"
                      placeholder="City, Barangay, Street Name" required>
                  </div>
                </div>

                <div class="row mb-3 align-items-start">
                  <label class="col-sm-3 col-form-label">Contact Person:</label>
                  <div class="col-sm-9">
                    <div class="row g-2">
                      <div class="col-md-6">
                        <input type="text" id="ContactPerson" name="ContactPerson" class="form-control bg-light"
                          placeholder="Name" required>
                      </div>
                      <div class="col-md-6">
                        <input type="tel" id="ContactPersonNo" name="ContactPersonNo" class="form-control bg-light"
                          placeholder="Contact No." required>
                      </div>
                    </div>
                  </div>
                </div>
              </fieldset>

              <!-- package details -->
              <fieldset class="info-panel shadow-sm mb-4">
                <legend class="info-legend">
                  <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Package Details</div>
                </legend>

                <div class="row mb-3 align-items-start">
                  <label class="col-sm-3 col-form-label" for="SelectPackage">Select Package:</label>
                  <div class="col-sm-9">
                    <select id="SelectPackage" name="SelectPackage" class="form-select bg-light" required>
                      <option value="" selected disabled>Choose a package</option>
                      <option value="wedding">Wedding Bliss</option>
                      <option value="corporate">Corporate Feast</option>
                      <option value="birthday">Birthday Bash</option>
                    </select>
                    <div class="floating-hint">Package Type</div>
                    <div class="d-flex flex-wrap gap-4 pt-2">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="PackageTier" id="pkg-standard" value="standard"
                          checked>
                        <label class="form-check-label" for="pkg-standard">Standard Package</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="PackageTier" id="pkg-premium" value="premium">
                        <label class="form-check-label" for="pkg-premium">Premium Package</label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="PackageTier" id="pkg-deluxe" value="deluxe">
                        <label class="form-check-label" for="pkg-deluxe">Deluxe Package</label>
                      </div>
                    </div>
                  </div>
                </div>

<<<<<<< HEAD
            <div class="row mb-2 align-items-start">
              <label class="col-sm-3 col-form-label">Payment Method:</label>
              <div class="col-sm-9">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="PaymentMethod" id="pay-cash" value="cash" checked>
                  <label class="form-check-label" for="pay-cash">Cash</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="PaymentMethod" id="pay-paypal" value="paypal">
                  <label class="form-check-label" for="pay-paypal">Paypal</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="PaymentMethod" id="pay-bank" value="bank">
                  <label class="form-check-label" for="pay-bank">Bank</label>
                </div>
              </div>
            </div>
              </fieldset>
            </div>
            <div class="col-12 col-lg-8">

              <!-- delivery details -->
              <fieldset class="info-panel shadow-sm mb-4 pb-5">
            <legend class="info-legend">
              <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Delivery Details</div>
            </legend>

            <div class="row mb-3 align-items-center">
              <label class="col-sm-3 col-form-label" for="EventDate">Event Date &amp; Time:</label>
              <div class="col-sm-9">
                <div class="row g-2">
                  <div class="col-md-4">
                    <input type="date" id="EventDate" name="EventDate" class="form-control bg-light" required>
                    <div class="floating-hint">Date</div>
                  </div>
                  <div class="col-md-4">
                    <input type="time" id="EventTime" name="EventTime" class="form-control bg-light" required>
                    <div class="floating-hint">Hour Minutes</div>
=======
                <div class="row mb-3 align-items-center">
                  <label class="col-sm-3 col-form-label" for="GuestCount">Number of Guest:</label>
                  <div class="col-sm-9 d-flex align-items-center gap-3">
                    <input type="number" id="GuestCount" name="GuestCount" class="form-control bg-light" min="1"
                      placeholder="50" required>
                    <span class="package-price-note">$500 per guest</span>
>>>>>>> origin/main
                  </div>
                </div>

                <div class="row mb-2 align-items-center">
                  <label class="col-sm-3 col-form-label" for="PackageSubtotal">Subtotal:</label>
                  <div class="col-sm-9">
                    <input type="text" id="PackageSubtotal" name="PackageSubtotal" class="form-control bg-light"
                      placeholder="$0.00">
                  </div>
                </div>
              </fieldset>
            </div>
<<<<<<< HEAD
              </fieldset>

              <!-- package details -->
              <fieldset class="info-panel shadow-sm mb-4">
            <legend class="info-legend">
              <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Package Details</div>
            </legend>

            <div class="row mb-3 align-items-start">
              <label class="col-sm-3 col-form-label" for="SelectPackage">Select Package:</label>
              <div class="col-sm-9">
                <select id="SelectPackage" name="SelectPackage" class="form-select bg-light" required>
                  <option value="" selected disabled>Choose a package</option>
                  <option value="wedding">Wedding Bliss</option>
                  <option value="corporate">Corporate Feast</option>
                  <option value="birthday">Birthday Bash</option>
                </select>
                <div class="floating-hint">Package Type</div>
                <div class="d-flex flex-wrap gap-4 pt-2">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="PackageTier" id="pkg-standard" value="standard"
                      checked>
                    <label class="form-check-label" for="pkg-standard">Standard Package</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="PackageTier" id="pkg-premium" value="premium">
                    <label class="form-check-label" for="pkg-premium">Premium Package</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="PackageTier" id="pkg-deluxe" value="deluxe">
                    <label class="form-check-label" for="pkg-deluxe">Deluxe Package</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-3 align-items-center">
              <label class="col-sm-3 col-form-label" for="GuestCount">Number of Guest:</label>
              <div class="col-sm-9 d-flex align-items-center gap-3">
                <input type="number" id="GuestCount" name="GuestCount" class="form-control bg-light" min="1"
                  placeholder="50" required>
                <span class="package-price-note">$500 per guest</span>
              </div>
            </div>

            <div class="row mb-2 align-items-center">
              <label class="col-sm-3 col-form-label" for="PackageSubtotal">Subtotal:</label>
              <div class="col-sm-9">
                <input type="text" id="PackageSubtotal" name="PackageSubtotal" class="form-control bg-light"
                  placeholder="$0.00">
              </div>
            </div>
              </fieldset>
            </div>
=======
>>>>>>> origin/main
          </div>

          <div class="row g-4 mt-2">
            <div class="col-12">
              <!-- food menu -->
              <fieldset class="info-panel shadow-sm mb-4">
                <legend class="info-legend">
<<<<<<< HEAD
                <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Food Menu</div>
                </legend>

                <div class="row mb-3 align-items-center">
                <div class="col-sm-12 d-flex gap-5 justify-content-center">
                    <div class="form-check">
                    <input class="form-check-input" type="radio" name="ServingStyle" id="style-serving"
                        value="per-serving" checked>
                    <label class="form-check-label" for="style-serving">Per serving</label>
                    </div>
                    <div class="form-check">
                    <input class="form-check-input" type="radio" name="ServingStyle" id="style-buffet" value="buffet">
                    <label class="form-check-label" for="style-buffet">Buffet style</label>
                    </div>
                </div>
=======
                  <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Food Menu</div>
                </legend>

                <div class="row mb-3 align-items-center">
                  <div class="col-sm-12 d-flex gap-5 justify-content-center">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="ServingStyle" id="style-serving"
                        value="per-serving" checked>
                      <label class="form-check-label" for="style-serving">Per serving</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="ServingStyle" id="style-buffet" value="buffet">
                      <label class="form-check-label" for="style-buffet">Buffet style</label>
                    </div>
                  </div>
>>>>>>> origin/main
                </div>

                <div id="menu-category-container"></div>

                <div class="mb-3">
<<<<<<< HEAD
                <label for="MenuNote" class="form-label fw-semibold">Note / Request:</label>
                <textarea id="MenuNote" name="MenuNote" class="form-control notes-area"
=======
                  <label for="MenuNote" class="form-label fw-semibold">Note / Request:</label>
                  <textarea id="MenuNote" name="MenuNote" class="form-control notes-area"
>>>>>>> origin/main
                    placeholder="Note any request..."></textarea>
                </div>
              </fieldset>
            </div>
            <div class="col-12">
              <!-- decoration -->
              <fieldset class="info-panel shadow-sm mb-4">
                <legend class="info-legend">
<<<<<<< HEAD
                <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Decoration</div>
                </legend>

                <div class="mb-3">
                <label for="DecorationNotes" class="form-label">Describe your preferred color, theme, or
                    decoration:</label>
                <textarea id="DecorationNotes" name="DecorationNotes" class="form-control notes-area"
=======
                  <div class="px-3 bg-white" style="margin-top: -2em;width: fit-content;">Decoration</div>
                </legend>

                <div class="mb-3">
                  <label for="DecorationNotes" class="form-label">Describe your preferred color, theme, or
                    decoration:</label>
                  <textarea id="DecorationNotes" name="DecorationNotes" class="form-control notes-area"
>>>>>>> origin/main
                    placeholder="e.g., blush pink, rustic theme, floral arch"></textarea>
                </div>
              </fieldset>
            </div>
          </div>

          <div class="text-center mt-4">
            <button type="submit" class="btn btn-outline-secondary px-5 rounded-5">Submit Order</button>
          </div>
<<<<<<< HEAD
=======
      </div>
>>>>>>> origin/main
    </div>
    </div>
 </div>


<<<<<<< HEAD
 <!-- JavaScript -->
=======
  <!-- JavaScript -->
>>>>>>> origin/main
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const foodMenuData = [{
          title: 'Appetizers',
          selectionNote: 'select 2',
          items: [{
              id: 'app-canapes',
              label: 'Canapes',
              price: 45
            },
            {
              id: 'app-shanghai',
              label: 'Lumpiang Shanghai',
              price: 55
            },
            {
              id: 'app-onion',
              label: 'Onion Rings',
              price: 40
            },
            {
              id: 'app-mushroom',
              label: 'Stuffed Mushrooms',
              price: 60
            },
            {
              id: 'app-cheese',
              label: 'Cheese Sticks',
              price: 40
            },
            {
              id: 'app-quiche',
              label: 'Mini Quiche',
              price: 60
            },
            {
              id: 'app-siomai',
              label: 'Pork Siomai',
              price: 55
            },
            {
              id: 'app-crackers',
              label: 'Cheese Crackers',
              price: 35
            },
            {
              id: 'app-lumpia',
              label: 'Fresh Lumpia',
              price: 50
            },
            {
              id: 'app-nachos',
              label: 'Nachos',
              price: 70
            },
            {
              id: 'app-tempura',
              label: 'Shrimp Tempura',
              price: 80
            },
            {
              id: 'app-fries',
              label: 'French Fries',
              price: 30
            },
            {
              id: 'app-hotdog',
              label: 'Hotdog Sticks',
              price: 35
            },
            {
              id: 'app-nuggets',
              label: 'Chicken Nuggets',
              price: 45
            },
            {
              id: 'app-skewers',
              label: 'Skewers',
              price: 65
            },
            {
              id: 'app-soup',
              label: 'Mushroom Soup',
              price: 50
            }
          ]
        },
        {
          title: 'Main Courses',
          selectionNote: 'select 4',
          items: [{
              id: 'main-caldereta',
              label: 'Caldereta',
              price: 180
            },
            {
              id: 'main-shrimp',
              label: 'Butter Shrimp',
              price: 220
            },
            {
              id: 'main-wings',
              label: 'Chicken Wings',
              price: 160
            },
            {
              id: 'main-mac',
              label: 'Mac and Cheese',
              price: 140
            },
            {
              id: 'main-fish',
              label: 'Fish Fillet',
              price: 150
            },
            {
              id: 'main-pizza',
              label: 'Cheese Pizza',
              price: 250
            },
            {
              id: 'main-cordon',
              label: 'Cordon Bleu',
              price: 200
            },
            {
              id: 'main-menudo',
              label: 'Menudo',
              price: 180
            },
            {
              id: 'main-roast',
              label: 'Roast Chicken',
              price: 320
            },
            {
              id: 'main-adobo',
              label: 'Chicken Adobo',
              price: 170
            },
            {
              id: 'main-eggcrabs',
              label: 'Egg Crabs',
              price: 280
            },
            {
              id: 'main-liempo',
              label: 'Pork Liempo',
              price: 220
            },
            {
              id: 'main-bistek',
              label: 'Bistek',
              price: 190
            },
            {
              id: 'main-afritada',
              label: 'Chicken Afritada',
              price: 170
            },
            {
              id: 'main-lechon',
              label: 'Lechon Belly',
              price: 260
            },
            {
              id: 'main-spaghetti',
              label: 'Spaghetti',
              price: 130
            }
          ]
        },
        {
          title: 'Desserts',
          selectionNote: 'select 4',
          items: [{
              id: 'dessert-puto',
              label: 'Puto',
              price: 60
            },
            {
              id: 'dessert-pandan',
              label: 'Buko Pandan',
              price: 80
            },
            {
              id: 'dessert-salad',
              label: 'Buko Salad',
              price: 90
            },
            {
              id: 'dessert-cake',
              label: 'Cake',
              price: 120
            },
            {
              id: 'dessert-coffee',
              label: 'Coffee Jelly',
              price: 70
            },
            {
              id: 'dessert-cookies',
              label: 'Cookies',
              price: 50
            },
            {
              id: 'dessert-cupcakes',
              label: 'Cupcakes',
              price: 65
            },
            {
              id: 'dessert-icecream',
              label: 'Ice Cream',
              price: 75
            },
            {
              id: 'dessert-maja',
              label: 'Maja Blanca',
              price: 70
            },
            {
              id: 'dessert-ube',
              label: 'Ube Halaya',
              price: 85
            },
            {
              id: 'dessert-leche',
              label: 'Leche Flan',
              price: 90
            }
          ]
        },
        {
          title: 'Beverages',
          selectionNote: 'select 2',
          items: [{
              id: 'bev-water',
              label: 'Water',
              price: 45
            },
            {
              id: 'bev-orange',
              label: 'Orange Juice',
              price: 45
            },
            {
              id: 'bev-coke',
              label: 'Coca cola',
              price: 45
            },
            {
              id: 'bev-sprite',
              label: 'Sprite',
              price: 45
            },
            {
              id: 'bev-cucumber',
              label: 'Cucumber Lemonade',
              price: 45
            },
            {
              id: 'bev-lemonade',
              label: 'Lemonade',
              price: 45
            },
            {
              id: 'bev-icedtea',
              label: 'Iced tea',
              price: 45
            },
            {
              id: 'bev-pineapple',
              label: 'Pineapple Juice',
              price: 45
            }
          ]
        }
      ];

      const menuContainer = document.getElementById('menu-category-container');
      if (!menuContainer) {
        return;
      }

      const formatPrice = (price) => `₱ ${price.toFixed(2)}`;

      const createMenuItem = (item) => {
        const col = document.createElement('div');
        col.className = 'col';
        col.innerHTML = `
          <div class="form-check d-flex justify-content-between align-items-center px-3">
            <div class="d-flex align-items-center">
<<<<<<< HEAD
              <input class="form-check-input me-2" type="checkbox" value="`+ item.id + `" id="` + item.id + `">
              <label style="font-size: 14px;" class="form-check-label" for="`+ item.id + `">` + item.label + `</label>
=======
              <input class="form-check-input me-2" type="checkbox" value="` + item.id + `" id="` + item.id + `">
              <label style="font-size: 14px;" class="form-check-label" for="` + item.id + `">` + item.label + `</label>
>>>>>>> origin/main
            </div>
            <span class="menu-price">` + formatPrice(item.price) + `</span>
          </div>
        `;
        return col;
      };

      foodMenuData.forEach((category) => {
        const section = document.createElement('div');
        section.className = 'menu-category';
        section.innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 text-uppercase fw-bold">` + category.title + `</h6>
            <span class="menu-note">` + category.selectionNote + `</span>
          </div>
        `;

        const row = document.createElement('div');
        row.className = 'row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2 menu-items';
        category.items.forEach((item) => row.appendChild(createMenuItem(item)));
        section.appendChild(row);

        menuContainer.appendChild(section);
      });
    });
  </script>
</body>

<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> origin/main
