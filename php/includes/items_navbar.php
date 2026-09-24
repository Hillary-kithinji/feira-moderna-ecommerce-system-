<?php
// Assuming session_start() has already been called in the including file
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : null;
?>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="../index.php">Fm</a>

    <!-- Cart and Welcome Message (always visible) -->
    <div class="d-flex align-items-center ms-auto">
      <span id="welcome-message" class="welcome-message"
        style="display: <?php echo $isLoggedIn ? 'inline-block' : 'none'; ?>;">
        Welcome, <?php echo htmlspecialchars($username); ?>!
      </span>
    </div>

    <!-- Toggler -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" 
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible nav items -->
    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
        <li class="nav-item">
          <a class="nav-link" id="logout-link" href="../php/logout.php" 
             style="display: <?php echo $isLoggedIn ? 'inline-block' : 'none'; ?>;">
             Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>