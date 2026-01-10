
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Rating</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head><style>
      body {
        min-height: 100vh;
      }
      .hero-card {
        max-width: 420px;
      }
      .rating-group {
        direction: rtl;
      }
      .rating-input {
        position: absolute;
        opacity: 0;
      }
      .rating-label {
        cursor: pointer;
        transition: transform 150ms ease;
      }
      .rating-label svg {
        width: 38px;
        height: 38px;
        fill: #d5d9e2;
        transition: fill 150ms ease;
      }
      .rating-label:hover,
      .rating-input:focus-visible + .rating-label {
        transform: translateY(-2px);
      }
      .rating-label:hover svg,
      .rating-label:hover ~ .rating-label svg,
      .rating-input:checked + .rating-label svg,
      .rating-input:checked + .rating-label ~ .rating-label svg {
        fill: #f59e0b;
      }
    </style>
  </head>
  <body class="d-flex justify-content-center align-items-center">
    <div class="hero-card bg-white rounded-4 shadow p-4" style="border: 2px solid #ffdfabff;">
    <div class="bg-white" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0 p-0">
            <div>
              
              <p class="text-uppercase text-muted small mb-1"><i class="bi bi-star-fill"></i> Overall rating</p>
              <h5 class="modal-title" id="ratingModalLabel">How was everything?</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-2">
            <form id="ratingForm" class="text-center">
              <div class="rating-group d-flex justify-content-center gap-1 mb-4">
                <input class="rating-input" type="radio" name="rating" id="rating-5" value="5">
                <label class="rating-label" for="rating-5" aria-label="5 stars">
                  <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                    <path d="M12 2.5l2.9 6.1 6.7.9-4.9 4.7 1.2 6.6L12 17.7l-5.9 3.1 1.2-6.6-4.9-4.7 6.7-.9z" />
                  </svg>
                </label>
                <input class="rating-input" type="radio" name="rating" id="rating-4" value="4">
                <label class="rating-label" for="rating-4" aria-label="4 stars">
                  <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                    <path d="M12 2.5l2.9 6.1 6.7.9-4.9 4.7 1.2 6.6L12 17.7l-5.9 3.1 1.2-6.6-4.9-4.7 6.7-.9z" />
                  </svg>
                </label>
                <input class="rating-input" type="radio" name="rating" id="rating-3" value="3" checked>
                <label class="rating-label" for="rating-3" aria-label="3 stars">
                  <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                    <path d="M12 2.5l2.9 6.1 6.7.9-4.9 4.7 1.2 6.6L12 17.7l-5.9 3.1 1.2-6.6-4.9-4.7 6.7-.9z" />
                  </svg>
                </label>
                <input class="rating-input" type="radio" name="rating" id="rating-2" value="2">
                <label class="rating-label" for="rating-2" aria-label="2 stars">
                  <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                    <path d="M12 2.5l2.9 6.1 6.7.9-4.9 4.7 1.2 6.6L12 17.7l-5.9 3.1 1.2-6.6-4.9-4.7 6.7-.9z" />
                  </svg>
                </label>
                <input class="rating-input" type="radio" name="rating" id="rating-1" value="1">
                <label class="rating-label" for="rating-1" aria-label="1 star">
                  <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                    <path d="M12 2.5l2.9 6.1 6.7.9-4.9 4.7 1.2 6.6L12 17.7l-5.9 3.1 1.2-6.6-4.9-4.7 6.7-.9z" />
                  </svg>
                </label>
              </div>
              <div class="mb-3 text-start">
                <label class="form-label" for="feedback">Tell us more</label>
                <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="The food was amazing..."></textarea>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-warning text-white">Submit rating</button>
                <small class="text-muted">Your review helps other clients pick the perfect package.</small>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    </div> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('ratingModal');
        var ratingModal = modalElement ? new bootstrap.Modal(modalElement) : null;
        var form = document.getElementById('ratingForm');

        if (!form) {
          return;
        }

        form.addEventListener('submit', function (event) {
          event.preventDefault();
          var formData = new FormData(form);
          var rating = formData.get('rating');
          var feedback = formData.get('feedback') || 'No additional feedback provided.';
          alert('Thank you for your feedback! Rating: ' + rating + '/5\nFeedback: ' + feedback);
          if (ratingModal) {
            ratingModal.hide();
          }
          form.reset();
        });
      });
    </script>
  </body>
  </html>