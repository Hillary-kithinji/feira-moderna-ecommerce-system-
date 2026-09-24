<?php
// Assuming session_start() has already been called in the including file
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : null;

// Detect if we are on the About Us page
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$isAboutUsPage = ($currentPage === 'about_us');
?>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    
    <a class="navbar-brand" href="index.php">M.E</a>

    <!-- Cart and Welcome Message -->
    <?php if (!$isAboutUsPage): ?>
    <div class="d-flex align-items-center ms-auto">
      <a class="nav-link cart-icon me-3" href="javascript:void(0);" onclick="openCartModal()">
        <i class="fas fa-shopping-cart"></i>
        <span id="cart-count" class="badge"></span>
      </a>
      <span id="welcome-message" class="welcome-message"
        style="display: <?php echo $isLoggedIn ? 'inline-block' : 'none'; ?>;">
        Welcome, <?php echo htmlspecialchars($username); ?>!
      </span>
    </div>
    <?php endif; ?>

    <!-- Toggler -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" 
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible nav items -->
    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>

        <!-- Contact - hide on About Us page -->
        <?php if (!$isAboutUsPage): ?>
        <li class="nav-item">
          <a class="nav-link" href="javascript:void(0);" onclick="openContactPopup()">Contact</a>
        </li>
        <?php endif; ?>

        <!-- Track Orders - hide on About Us page -->
        <?php if (!$isAboutUsPage): ?>
        <li class="nav-item">
          <a class="nav-link" id="track-order-link" href="javascript:void(0);" 
             onclick="openTrackOrdersModal()" 
             style="display: <?php echo $isLoggedIn ? 'inline-block' : 'none'; ?>;">
             Track Orders
          </a>
        </li>
        <?php endif; ?>

        <!-- Login / Logout -->
        <li class="nav-item">
          <a class="nav-link" id="login-link" href="javascript:void(0);" 
             onclick="openLoginPopup()" 
             style="display: <?php echo $isLoggedIn ? 'none' : 'inline-block'; ?>;">
             Login/signup
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="logout-link" href="php/logout.php" 
             style="display: <?php echo $isLoggedIn ? 'inline-block' : 'none'; ?>;">
             Logout
          </a>
        </li>

        <!-- About Us - always visible -->
        <li class="nav-item">
          <a class="nav-link" href="about_us.php">About Us</a>
        </li>

        <!-- Settings dropdown - hide on About Us page -->
<?php if (!$isAboutUsPage): ?>
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown"
     role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-cog"></i>
  </a>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
    <li>
      <a class="dropdown-item" href="#" onclick="openHelpPopup(); return false;">
        Help
      </a>
    </li>
  </ul>
</li>
<?php endif; ?>
      </ul>
    </div>
  </div>
</nav>