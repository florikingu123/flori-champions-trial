<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat GPT API</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    body {
      background-color: gray;
      font-family: 'Roboto', sans-serif;
    }
    .chat-container {
      max-width: 800px;
      margin: 200px auto; /* Added margin to move the whole chatbot section lower */
      padding: 20px;
    }
    .chat-box {
      background-color: #fff;
      padding: 16px;
      height: 400px;
      overflow-y: auto;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      margin-top: 20px; /* Added margin to push the chat box down */
    }
    .input-field {
      flex-grow: 1;
      border: 1px solid #e2e8f0;
      padding: 10px;
      border-radius: 8px;
      outline: none;
    }
    .button {
      margin-left: 8px;
      background-color: #6b7280;
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .button:hover {
      background-color: #4b5563;
    }
    .message {
      margin-bottom: 8px;
      padding: 8px;
      border-radius: 8px;
    }
    .message-you {
      background-color: #bfdbfe;
    }
    .message-gpt {
      background-color: #f3f4f6;
    }
  </style>
</head>
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">Famify</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
      <li><a href="member.php">Family center</a></li>      
        <li><a href="games.php">Games</a></li>
        <li><a href="rew.php">Rewards</a></li>
        <li><a href="account.php">Your Account</a></li>
        <li><a href="ai.php">Chore ai</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>

<body>
  <div class="chat-container">
    <h1 class="text-2xl font-bold mb-4">Chat with ur chore ai</h1>

    <div id="chat-box" class="chat-box">
    </div>

    <form id="chat-form" class="flex">
      <input
        type="text"
        id="user-input"
        class="input-field"
        placeholder="Type your message here"
        required
      />
      <button
        type="submit"
        class="button"
      >
        Send
      </button>
    </form>
  </div>
  <footer id="footer" class="footer dark-background">

<div class="footer-newsletter">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-6">
        <h4>Join Our Newsletter</h4>
        <p>Subscribe to our newsletter and receive the latest news about our products and services!</p>
        <form action="newsletter.php" method="post" class="php-email-form">
          <div class="newsletter-form">
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="submit" value="Subscribe">
          </div>
          <div class="loading">Loading</div>
          <div class="sent-message" style="display:none;">Your subscription request has been sent. Thank you!</div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="container footer-top">
  <div class="row gy-4">
    <div class="col-lg-4 col-md-6 footer-about">
      <a href="index.html" class="d-flex align-items-center">
        <span class="sitename">Famify</span>
      </a>
      <div class="footer-contact pt-3">
        <p>1234 Elm Street</p>
        <p>Los Angeles, CA 90001</p>
        <p class="mt-3"><strong>Phone:</strong> <span>+1 2345 6789 01</span></p>
        <p><strong>Email:</strong> <span>famify@info.com</span></p>
      </div>
    </div>

    <div class="col-lg-2 col-md-3 footer-links">
      <h4>Useful Links</h4>
      <ul>
        <li><i class="bi bi-chevron-right"></i> <a href="index.html">Home</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="about.html" class="active">About</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="services.html">Services</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="portfolio.html">Portfolio</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="team.html">Team</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="blog.html">Blog</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="contact.html">Contact</a></li>
      </ul>
    </div>

    <div class="col-lg-2 col-md-3 footer-links">
      <h4>Our Services</h4>
      <ul>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Keeping Your Family Organized</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">How Famify Keeps Your Family on Track</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Manage Chores and Rewards</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Effortless Communication</a></li>
      </ul>
    </div>

    <div class="col-lg-4 col-md-12">
      <h4>Follow Us</h4>
      <div class="social-links d-flex">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
    </div>

  </div>
</div>

<div class="container copyright text-center mt-4">
  <p>© <span>Copyright</span> <strong class="px-1 sitename">Famify</strong> <span>All Rights Reserved</span></p>
</div>

</footer>

  <script>
    const form = document.getElementById('chat-form');
    const input = document.getElementById('user-input');
    const chatBox = document.getElementById('chat-box');
    let loadingMessage;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const userMessage = input.value;
      addMessage('You', userMessage);
      input.value = '';

      loadingMessage = addMessage('GPT', 'Typing...');

      try {
        const response = await fetch(`https://free-unoficial-gpt4o-mini-api-g70n.onrender.com/chat/?query=${userMessage}`, {
          method: 'GET',
          headers: {
            'accept': 'application/json'
          }
        });
        const data = await response.json();

        loadingMessage.textContent = `GPT: ${data.results || 'No response from the API'}`;
      } catch (error) {
        loadingMessage.textContent = 'GPT: Failed to fetch API';
      }
    });

    function addMessage(sender, message) {
      const messageElement = document.createElement('div');
      messageElement.classList.add('message', sender === 'You' ? 'message-you' : 'message-gpt');
      messageElement.textContent = `${sender}: ${message}`;
      chatBox.appendChild(messageElement);
      chatBox.scrollTop = chatBox.scrollHeight;
      return messageElement;
    }
  </script>
</body>
</html>
