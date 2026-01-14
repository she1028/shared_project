
  <?php
  $standalone = (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'contact.php');
  ?>

  <?php if ($standalone): ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />
  </head>
  <body>
  <?php endif; ?>

  <style>
    .contact-modal {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      max-width: 500px;
    }

    .contact-close {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 5;
    }

    .modal-dialog {
      max-width: 500px;
    }

    .contact-info {
      background: linear-gradient(135deg, rgba(139, 101, 80, 1) 0%, rgba(101, 67, 33, 1) 100%);
    }

    .contact-info h5 {
      font-weight: 600;
      margin-bottom: 15px;
      font-size: 18px;
    }

    .contact-info p {
      margin-bottom: 10px;
      font-size: 12px;
      opacity: 0.95;
    }

    .contact-info i {
      font-size: 14px;
      margin-right: 6px;
    }

    .contact-form h5 {
      font-weight: 600;
      color: #222;
      margin-bottom: 4px;
      font-size: 18px;
    }

    .contact-form .text-muted {
      font-size: 12px;
      margin-bottom: 15px;
    }

    .form-control {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 13px;
      transition: all 0.3s;
      font-family: 'Poppins', Arial, sans-serif;
    }

    .form-control:focus {
      border-color: rgba(139, 101, 80, 1);
      box-shadow: 0 0 0 3px rgba(139, 101, 80, 0.1);
      outline: none;
    }

    .form-control:invalid:not(:placeholder-shown) {
      border-color: #dc3545;
    }

    .error-text {
      color: #dc3545;
      font-size: 11px;
      margin-top: 4px;
      display: none;
    }

    .form-control:invalid:not(:placeholder-shown) + .error-text {
      display: block;
    }

    .btn-submit {
      background: linear-gradient(93.5deg, rgba(139, 101, 80, 1) 0%, rgba(101, 67, 33, 1) 100%);
      border: none;
      color: #fff;
      font-weight: 600;
      padding: 8px 30px;
      border-radius: 20px;
      font-size: 13px;
      transition: all 0.3s;
      font-family: 'Poppins', Arial, sans-serif;
    }

    .btn-submit:hover {
      background: linear-gradient(93.5deg, rgba(101, 67, 33, 1) 0%, rgba(80, 50, 30, 1) 100%);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      color: #fff;
    }

    @media (max-width: 768px) {
      .modal-dialog {
        max-width: 90%;
      }

      .contact-info {
        flex: 0 0 100% !important;
        padding: 20px 15px !important;
      }

      .contact-form {
        flex: 0 0 100% !important;
        padding: 20px 15px !important;
      }

      .contact-modal {
        flex-direction: column;
      }
    }
  </style>

  <!-- Contact Modal -->
  <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content contact-modal p-0 d-flex position-relative">
        <button type="button" class="btn-close contact-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <!-- Left Info Panel -->
        <div class="contact-info d-flex flex-column justify-content-center text-center text-white p-3" style="flex: 0 0 35%;">
          <h5 id="contactModalLabel">Contact Us</h5>
          <p><i class="bi bi-geo-alt-fill"></i>067, Bawi, Padre Garcia, Batangas</p>
          <p><i class="bi bi-envelope-fill"></i>ymzmcateringservices@gmail.com</p>
          <p><i class="bi bi-telephone-fill"></i>090 8691 2265</p>
        </div>

        <div class="contact-form p-3" style="flex: 1; background-color: #fff;">
          <h5>Get in Touch</h5>
          <p class="text-muted mb-3">Feel free to contact us!</p>

          <form id="contactForm" action="send-mail.php" method="post">
            <div class="mb-2">
              <input type="text" name="name" class="form-control" placeholder="Your Name" minlength="2" maxlength="50" required>
              <div class="error-text">Please enter your name (2-50 characters)</div>
            </div>
            <div class="mb-2">
              <input type="email" name="email" class="form-control" placeholder="Your Email Address" required>
              <div class="error-text">Please enter a valid email address</div>
            </div>
            <div class="mb-2">
              <input type="text" name="subject" class="form-control" placeholder="Subject" minlength="3" maxlength="100" required>
              <div class="error-text">Subject must be 3-100 characters</div>
            </div>
            <div class="mb-2">
              <textarea name="note" class="form-control" placeholder="Message" rows="3" style="resize: none;" minlength="10" maxlength="500" required></textarea>
              <div class="error-text">Message must be 10-500 characters</div>
            </div>
            <div class="text-center">
              <button type="submit" name="send" class="btn btn-submit rounded-pill">
                SEND MESSAGE
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php if ($standalone): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('contactModal');
        if (!el || !window.bootstrap) return;
        const modal = new bootstrap.Modal(el);
        modal.show();
      });
    </script>
  </body>
  </html>
  <?php endif; ?>