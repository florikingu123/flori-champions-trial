<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment</title>
  
  <style>
    /* Center the entire page content */
    body, html {
      height: 100%;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      background-color: #f0f0f0;
      font-family: Arial, Helvetica, sans-serif;
    }

    /* Title above the form */
    .title {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #4A4A4A;
    }

    /* Form container */
    .form-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      width: 600px;
      padding: 20px;
      background-color: #fff;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      color: #4A4A4A;
      font-family: 'Arial', sans-serif;
    }

    /* Input Fields */
    .inputstyle {
      background-color: transparent;
      border: none;
      outline: none;
      color: #4A4A4A;
      caret-color: #ff9800;
      font-size: 18px;
      letter-spacing: 1.5px;
      padding: 10px;
      border-bottom: 2px solid #4A4A4A;
      width: 100%;
      margin-bottom: 20px;
    }

    .inputstyle::placeholder {
      color: #4A4A4A;
    }

    .input-label {
      font-size: 12px;
      letter-spacing: 1px;
      color: #4A4A4A;
      width: 100%;
      margin-bottom: 5px;
    }

    /* Button Styles */
    .button-submit {
      display: inline-block;
      padding: 15px 30px;
      text-align: center;
      letter-spacing: 1px;
      text-decoration: none;
      background: transparent;
      transition: ease-out 0.5s;
      border: 2px solid #4A4A4A;
      border-radius: 10em;
      margin-top: 20px;
      color: #4A4A4A;
      font-size: 15px;
      font-weight: 500;
      height: 50px;
      width: 100%;
      cursor: pointer;
      animation: buttonAppear 0.8s ease-out;
    }

    .button-submit:hover {
      color: white;
      background-color: #4A4A4A;
    }

    .button-submit:active {
      transform: scale(0.9);
    }

    /* Button Animation */
    @keyframes buttonAppear {
      0% {
        opacity: 0;
        transform: translateY(10px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

  </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>
<body>

  <div class="title">Signed up for your Organization</div>

 

  <!-- Continue Button -->
  <button class="button-submit" onclick="window.location.href='famify.php'">Continue</button>

</body>
</html>



