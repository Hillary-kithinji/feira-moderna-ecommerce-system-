<?php
require_once 'php/db.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin     = $isLoggedIn && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

if ($isAdmin) {
    header('Location: admin/admin_dashboard.php');
    exit();
}

// Add this line
$currentPage = 'about_us';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - Feira Moderna</title>

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

  <!-- Navbar -->
  <?php include('php/includes/navbar.php'); ?>

  <!-- Main Content -->
  <div class="container my-5 py-5">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <h1 class="display-5 text-center mb-5">
          <i class="fas fa-info-circle text-primary me-3"></i>About Feira Moderna
        </h1>

        <div class="card shadow border-0">
          <div class="card-body p-5">
            <p class="lead text-center mb-5">
              Your trusted online store for quality household essentials — delivered fast and with care.
            </p>

            <hr class="my-5">

            <h3 class="mb-4">Who We Are</h3>
            <p>
              Feira Moderna is more than just an online shop — we're your reliable partner for all household needs. 
              From cookware and utensils to appliances, tableware, and cutlery, we bring the best products right to your door.
            </p>

            <h3 class="mt-5 mb-4">Our Mission</h3>
            <p>
              To make everyday shopping simple, affordable, and enjoyable by offering high-quality items, 
              excellent customer service, and seamless delivery.
            </p>

            <h3 class="mt-5 mb-4">Why Customers Love Us</h3>
            <div class="row g-4 text-center">
              <div class="col-md-4">
                <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                <h5>Fast Delivery</h5>
                <p>Quick and secure shipping to your doorstep</p>
              </div>
              <div class="col-md-4">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h5>Secure Shopping</h5>
                <p>Your data and payments are always protected</p>
              </div>
              <div class="col-md-4">
                <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                <h5>Great Support</h5>
                <p>We're here to help you every step of the way</p>
              </div>
            </div>

            <div class="text-center mt-5">
              <a href="index.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-shopping-bag me-2"></i> Start Shopping
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include('php/includes/footer.php'); ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>