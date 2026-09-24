<?php
require_once 'php/db.php';
require_once 'vendor/autoload.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$username    = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : null;
$userEmail   = $isLoggedIn && isset($_SESSION['email']) ? $_SESSION['email'] : '';
$isAdmin     = $isLoggedIn && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
if ($error) unset($_SESSION['error']);

if ($isAdmin) {
    header('Location: admin/admin_dashboard.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feira Moderna</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Yatra+One&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="files/styles.css">

  <!-- Favicons -->
  <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
  <link rel="manifest" href="site.webmanifest">
</head>
<body>

  <!-- Toast -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="appToast" class="toast" role="alert">
      <div class="toast-header">
        <strong class="me-auto">Notification</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div class="toast-body"></div>
    </div>
  </div>

  <!-- Navbar -->
  <?php include('php/includes/navbar.php'); ?>

  <!-- Search Bar -->
  <div class="search-bar my-3">
    <div class="input-group w-100 w-md-50 mx-auto">
      <input type="text" class="form-control" id="searchInput" placeholder="Search items..." onkeyup="searchItems()">
      <button class="btn btn-primary" onclick="searchItems()">Search</button>
    </div>
  </div>

  <!-- Header -->
  <header class="text-center py-4">
    <h1 class="display-4">Feira Moderna</h1>
    <h2 class="fs-4">Order Household Items</h2>
  </header>

  <!-- Error -->
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-3" role="alert">
      <?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- CATEGORY FILTER -->
  <section class="container-fluid my-4">
    <div class="d-flex flex-wrap justify-content-center gap-2" id="category-filter">
      <button class="btn btn-outline-success active" data-category="all">All</button>
      <button class="btn btn-outline-success" data-category="1">Cookware</button>
      <button class="btn btn-outline-success" data-category="2">Utensils</button>
      <button class="btn btn-outline-success" data-category="3">Appliances</button>
      <button class="btn btn-outline-success" data-category="4">Tableware</button>
      <button class="btn btn-outline-success" data-category="5">Cutlery</button>
    </div>
  </section>
  
  <!-- Products -->
  <main class="container-fluid">
    <section id="product-section" class="row g-3 g-md-4">
      <div class="text-center loading-message w-100 py-5">
        <p class="display-6 text-muted">Loading products...</p>
        <div class="spinner-border text-primary mt-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    </section>

    <!-- Pagination -->
    <nav aria-label="Product pagination" class="mt-4">
      <ul id="pagination" class="pagination justify-content-center"></ul>
    </nav>
  </main>

  <!-- ==================== POPUPS ==================== -->
  <!-- Track Orders Modal -->
  <div class="modal fade" id="trackOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Track Your Orders</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="orders-list">
          <p class="text-center">Loading orders...</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Cart Modal -->
  <div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Shopping Cart</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="cart-items">
          <p class="text-center text-muted">Your cart is empty.</p>
        </div>
        <div class="modal-footer">
          <div class="me-auto"><strong>Total: Ksh <span id="cart-total">0.00</span></strong></div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="placeOrder()">Place Order</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Popup -->
  <div id="contact-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">

      <button class="close-popup" onclick="closePopup()">X</button>

      <h2><i class="fas fa-envelope"></i> Contact Us</h2>

      <form id="contact-form">
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" class="form-control" id="contact-email" name="email" value="<?= htmlspecialchars($userEmail) ?>" placeholder="Your Email"
            required>
        </div>

        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-comment"></i></span>
          <textarea
            class="form-control"
            id="contact-message"
            name="message"
            placeholder="Your Message"
            required></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100">
          Send Message
        </button>
      </form>
    </div>
  </div>


  <!-- Help Popup -->
  <div id="help-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-question-circle"></i> Help</h2>
      <ul>
        <li>Use the search bar to find items</li>
        <li>Click a product to view details</li>
        <li><strong>Login to add to cart</strong></li>
        <li>Use + / – buttons in cart to adjust quantity</li>
        <li>Click trash to remove item</li>
      </ul>
    </div>
  </div>

  <!-- Login Popup -->
  <div id="login-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
      <form action="php/login.php" method="POST">
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" class="form-control" name="email" placeholder="Email" required>
        </div>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
          <span class="input-group-text" onclick="togglePassword('login-password', this)">
            <i class="fas fa-eye"></i>
          </span>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      <p class="mt-2"><a href="#" onclick="openForgotPasswordPopup()">Forgot Password?</a></p>
      <p>No account? <a href="#" onclick="openSignupPopup()">Sign Up</a></p>
    </div>
  </div>

  <!-- Signup Popup -->
  <div id="signup-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-user-plus"></i> Sign Up</h2>

      <form id="signup-form">
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-user"></i></span>
          <input type="text" class="form-control" name="name" placeholder="Full Name" required>
        </div>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" class="form-control" name="email" placeholder="Email" required>
        </div>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-phone"></i></span>
          <input type="tel" class="form-control" name="phone" placeholder="Phone" required>
        </div>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" class="form-control" id="signup-password" name="password" placeholder="Password" required>
          <span class="input-group-text" onclick="togglePassword('signup-password', this)">
            <i class="fas fa-eye"></i>
          </span>
        </div>
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" class="form-control" id="signup-confirm-password" name="confirm_password" placeholder="Confirm Password" required>
          <span class="input-group-text" onclick="togglePassword('signup-confirm-password', this)">
            <i class="fas fa-eye"></i>
          </span>
        </div>
        <button type="submit" class="btn btn-primary w-100" id="signup-btn">
          Sign Up
        </button>
      </form>
    </div>
  </div>

  <!-- Forgot Password -->
  <div id="forgot-password-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-key"></i> Reset Password</h2>
      <form action="php/send_reset_otp.php" method="POST">
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" class="form-control" name="email" placeholder="Your Email" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
      </form>
    </div>
  </div>

  <?php include('php/includes/footer.php'); ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script> const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;</script>
  <script src="files/index.js"></script>
  <!-- Custom JS -->
  <!-- <script src="afiles/script.js"></script>
   ==================== JAVASCRIPT ==================== -->
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
</body>
</html>