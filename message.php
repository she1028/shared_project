<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Form</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: 'Poppins', Arial, sans-serif;
      background: linear-gradient(to bottom, #fff6f6 0%, #8d314a 100%);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .container {
      background: transparent;
      width: 100%;
      max-width: 400px;
      padding: 40px 32px 32px 32px;
      border-radius: 16px;
      box-shadow: 0 0 0 8px rgba(140, 49, 74, 0.08);
      box-sizing: border-box;
    }

    form {
      width: 100%;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 18px;
      color: #222;
    }

    input,
    textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
      font-family: 'Poppins', Arial, sans-serif;
      box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.08);
      outline: none;
      transition: border 0.2s;
    }

    input:focus,
    textarea:focus {
      border: 1.5px solid #8d314a;
    }

    textarea {
      height: 100px;
      resize: none;
    }

    .submit-btn {
      background: linear-gradient(90deg, #b03a5b 0%, #8d314a 100%);
      color: #fff;
      border: none;
      border-radius: 16px;
      padding: 10px 0;
      font-size: 18px;
      font-weight: 600;
      width: 120px;
      box-shadow: 4px 6px 16px rgba(80, 20, 40, 0.15);
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
      margin-left: auto;
      display: block;
    }

    .submit-btn:hover {
      background: linear-gradient(90deg, #8d314a 0%, #b03a5b 100%);
      box-shadow: 2px 3px 8px rgba(80, 20, 40, 0.25);
    }

    .back-arrow {
      position: absolute;
      top: 32px;
      left: 32px;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      z-index: 10;
      background: none;
      border: none;
      padding: 0;
      cursor: pointer;
    }

    .back-arrow svg {
      display: block;
      width: 28px;
      height: 28px;
      transition: stroke 0.2s;
    }

    .back-arrow:hover svg path {
      stroke: #b03a5b;
    }
  </style>
</head>

<body>
  <a href="index.php" class="back-arrow" title="Back">
    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
      <path d="M18 6L10 14L18 22" stroke="#8d314a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  </a>
  <div class="container">
    <form id="contact" action="send-mail.php" method="post">
      <label for="name">Name</label>
      <input id="name" name="name" type="text" required autofocus>

      <label for="recipient">Recipient Email</label>
      <input id="recipient" name="recipient" type="email" required>

      <label for="subject">Subject</label>
      <input id="subject" name="subject" type="text" required>

      <label for="message">Message</label>
      <textarea id="message" name="message" required></textarea>

      <button type="submit" name="send" class="submit-btn">SEND</button>
    </form>
  </div>
</body>

</html>