<?php
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Safely get username and email only if keys exist
$username = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : null;
$userEmail = $isLoggedIn && isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Check if admin
$isAdmin = $isLoggedIn && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Handle flash errors
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
if ($error) {
    unset($_SESSION['error']);
}

// Redirect admin
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
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Yatra+One&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="files/styles.css">

  <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="manifest" href="site.webmanifest">

</head>
<body>
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="appToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <strong class="me-auto">Notification</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body"></div>
    </div>
  </div>

  <?php include('php/includes/navbar.php'); ?>

  <!-- Search Bar -->
  <div class="search-bar">
    <div class="input-group">
      <input type="text" class="form-control" id="searchInput" placeholder="Search for items..." onkeyup="searchItems()">
      <button class="btn btn-primary" type="button" onclick="searchItems()">Search</button>
    </div>
  </div>

  <!-- Header -->
  <header>
    <h1>Feira Moderna</h1>
    <h2>Order HouseHold Items</h2>
  </header>
  

  <!-- Error Message -->
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Main Content -->
  <main class="container-fluid my-4">
    <!-- Products Section -->
    <section id="product-section" class="products">
      <p class="loading-message">Loading products...</p>
    </section>

    <!-- Pagination -->
    <nav aria-label="Product pagination">
      <ul id="pagination" class="pagination"></ul>
    </nav>
  </main>

  <!-- Product Detail Modal -->
  <div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productDetailModalLabel">Product Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div id="detailCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner" id="detailCarouselInner"></div>
            <button class="carousel-control-prev" type="button" data-bs-target="#detailCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#detailCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
          <h3 id="detail-product-name"></h3>
          <p id="detail-product-price"></p>
          <p id="detail-product-stock"></p>
          <select id="detail-product-color" class="form-select mt-2">
            <option value="none">Select Color (if applicable)</option>
          </select>
          <div class="quantity-control mt-3">
            <button class="btn btn-outline-secondary btn-sm" onclick="adjustDetailQuantity(-1)"><i class="fas fa-minus"></i></button>
            <input type="number" id="detail-quantity" value="1" min="1" class="form-control w-auto d-inline-block mx-2" readonly>
            <button class="btn btn-outline-secondary btn-sm" onclick="adjustDetailQuantity(1)"><i class="fas fa-plus"></i></button>
          </div>
          <button class="btn btn-primary mt-2" onclick="openProductModalFromDetail()">Add to Cart</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Product Selection Modal -->
  <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productModalLabel">Select Product Options</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="productForm">
            <div class="form-group">
              <i class="fas fa-utensils"></i>
              <input type="text" class="form-control" id="modal-product-name" readonly>
            </div>
            <div class="form-group">
              <i class="fas fa-paint-brush"></i>
              <select class="form-control" id="modal-product-color">
                <option value="">Select Color</option>
              </select>
            </div>
            <div class="form-group">
              <i class="fas fa-ruler"></i>
              <select class="form-control" id="modal-product-size">
                <option value="">Select Size</option>
              </select>
            </div>
            <div class="form-group">
              <i class="fas fa-sort-numeric-up"></i>
              <input type="number" class="form-control" id="modal-quantity" min="1" value="1">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="addToCart()">Add to Cart</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Cart Modal -->
  <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cartModalLabel">Shopping Cart</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="cart-items"></div>
          <div class="cart-total" id="cart-total">Total: Ksh 0.00</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="placeOrder()">Place Order</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Track Orders Modal -->
  <div class="modal fade" id="trackOrdersModal" tabindex="-1" aria-labelledby="trackOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="trackOrdersModalLabel">Track Your Orders</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="orders-list"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Popup -->
  <div id="contact-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-address-book"></i> Contact Us</h2>
      <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <a href="mailto:kithinjihilary@gmail.com">kithinjihilary@gmail.com</a></p>
      <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <a href="tel:+254717703036">0717703036</a></p>
      <form id="contactForm" method="POST" action="php/send_contact_message.php">
        <div class="form-group">
          <i class="fas fa-envelope"></i>
          <input type="email" class="form-control" id="contact-email" name="email" required placeholder="Your Email" value="<?php echo htmlspecialchars($userEmail); ?>">
        </div>
        <div class="form-group">
          <i class="fas fa-comment"></i>
          <textarea class="form-control" id="contact-message" name="message" required placeholder="Your Message"></textarea>
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary w-100" value="Send Message">
        </div>
      </form>
    </div>
  </div>

  <!-- Help Popup -->
  <div id="help-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-question-circle"></i> Help</h2>
      <p>Welcome to Feira Moderna! Here’s how to use the system:</p>
      <ul>
        <li><strong>Search Products:</strong> Use the search bar to find items.</li>
        <li><strong>View Details:</strong> Click a product image to see a larger view and select options.</li>
        <li><strong>Add to Cart:</strong> Login, select product options, and add items to your cart.</li>
        <li><strong>Place Order:</strong> Review your cart and place an order to receive a confirmation email.</li>
        <li><strong>Track Orders:</strong> Check the status of your orders in the "Track Orders" section (login required).</li>
        <li><strong>Login/Sign Up:</strong> Create an account or login to access cart and order tracking.</li>
        <li><strong>Forgot Password:</strong> Reset your password via email if needed.</li>
        <li><strong>Contact Us:</strong> Send a message or call for support using the contact form or provided details.</li>
      </ul>
    </div>
  </div>

  <!-- About Us Popup -->
  <div id="about-us-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-info-circle"></i> About Us</h2>
      <p>Feira Moderna is your one-stop online store for high-quality household items. We are dedicated to providing a seamless shopping experience with a wide range of products, from kitchen essentials to home decor. Our mission is to bring convenience and quality to your doorstep.</p>
      <p>Founded in 2025, our team is passionate about customer satisfaction and innovation. Shop with us today and discover the joy of hassle-free online shopping!</p>
      <button class="btn btn-primary" onclick="closePopup()">OK</button>
    </div>
  </div>

  <!-- Message Sent Popup -->
  <div id="message-sent-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-check-circle"></i> Message Sent</h2>
      <p>Your message has been sent successfully! We'll get back to you soon.</p>
      <button class="btn btn-primary" onclick="closePopup()">OK</button>
    </div>
  </div>

  <!-- Message Failed Popup -->
  <div id="message-failed-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-exclamation-circle"></i> Message Failed</h2>
      <p>Failed to send your message. Please try again later.</p>
      <button class="btn btn-primary" onclick="closePopup()">OK</button>
    </div>
  </div>

  <!-- Login Popup -->
  <div id="login-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
      <form action="php/login.php" method="POST">
        <div class="form-group">
          <i class="fas fa-envelope"></i>
          <input type="email" class="form-control" id="login-email" name="email" required placeholder="Email">
        </div>
        <div class="form-group">
          <i class="fas fa-lock"></i>
          <i class="fas fa-eye" id="login-password-toggle" onclick="togglePassword('login-password', 'login-password-toggle')"></i>
          <input type="password" class="form-control" id="login-password" name="password" required placeholder="Password">
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary w-100" value="Login">
        </div>
      </form>
      <p><a href="javascript:void(0);" onclick="openForgotPasswordPopup()">Forgot Password?</a></p>
      <p>Don't have an account? <a href="javascript:void(0);" onclick="openSignupPopup()">Sign Up</a></p>
    </div>
  </div>

  <!-- Forgot Password Popup -->
  <div id="forgot-password-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-key"></i> Reset Password</h2>
      <p>Enter your email to receive a password reset link.</p>
      <form id="forgotPasswordForm" action="php/send_reset_otp.php" method="POST">
        <div class="form-group">
          <i class="fas fa-envelope"></i>
          <input type="email" class="form-control" id="forgot-email" name="email" required placeholder="Email">
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary w-100" value="Send Reset Email link">
        </div>
      </form>
    </div>
  </div>

  <!-- Reset Password Popup -->
  <div id="reset-password-popup" class="popup" style="display:none;" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-lock"></i> Set New Password</h2>
      <p>Enter your new password below.</p>
      <form id="resetPasswordForm" action="php/reset_password.php" method="POST">
        <input type="hidden" id="reset-email" name="email">
        <div class="form-group">
          <i class="fas fa-lock"></i>
          <i class="fas fa-eye" id="reset-password-toggle" onclick="togglePassword('reset-password', 'reset-password-toggle')"></i>
          <input type="password" class="form-control" id="reset-password" name="new_password" required placeholder="New Password">
        </div>
        <div class="form-group">
          <i class="fas fa-lock"></i>
          <i class="fas fa-eye" id="reset-confirm-password-toggle" onclick="togglePassword('reset-confirm-password', 'reset-confirm-password-toggle')"></i>
          <input type="password" class="form-control" id="reset-confirm-password" name="confirm_password" required placeholder="Confirm Password">
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary w-100" value="Reset Password">
        </div>
      </form>
    </div>
  </div>

  <!-- Signup Popup -->
  <div id="signup-popup" class="popup" onclick="closePopupOnClick(event)">
    <div class="popup-content" onclick="event.stopPropagation();">
      <button class="close-popup" onclick="closePopup()">X</button>
      <h2><i class="fas fa-user-plus"></i> Sign Up</h2>
      <form id="signupForm" method="POST">
        <div class="form-group">
          <i class="fas fa-user"></i>
          <input type="text" class="form-control" id="signup-name" name="name" required placeholder="Name">
        </div>
        <div class="form-group">
          <i class="fas fa-envelope"></i>
          <input type="email" class="form-control" id="signup-email" name="email" required placeholder="Email">
        </div>
        <div class="form-group">
          <i class="fas fa-phone"></i>
          <input type="tel" class="form-control" id="signup-phone" name="phone" required placeholder="Phone">
        </div>
        <div class="form-group">
          <i class="fas fa-lock"></i>
          <i class="fas fa-eye" id="signup-password-toggle" onclick="togglePassword('signup-password', 'signup-password-toggle')"></i>
          <input type="password" class="form-control" id="signup-password" name="password" required placeholder="Password">
        </div>
        <div class="form-group">
          <i class="fas fa-lock"></i>
          <i class="fas fa-eye" id="signup-confirm-password-toggle" onclick="togglePassword('signup-confirm-password', 'signup-confirm-password-toggle')"></i>
          <input type="password" class="form-control" id="signup-confirm-password" name="confirm_password" required placeholder="Confirm Password">
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary w-100" value="Sign Up">
        </div>
      </form>
    </div>
  </div>

  <!-- Thank You Popup -->
  <div id="thank-you-popup" class="thank-you-popup">
    <p>Thank you for your order! We'll contact you soon.</p>
    <button class="btn btn-primary" onclick="closeThankYouPopup()">OK</button>
  </div>

  <?php include('php/includes/footer.php'); ?>
  <script>
    function fetchProducts(page, searchTerm) {
        console.log(`Fetching products, page: ${page}, search: ${searchTerm}`);
        const productList = document.getElementById('product-list');
        if (!productList) return;
        productList.innerHTML = '<p class="loading-message">Loading products...</p>';
        fetch(`/php/products/get_products.php?page=${page}&search=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) throw new Error('HTTP error! Status: ' + response.status);
                return response.json();
            })
            .then(data => {
                productList.innerHTML = '';
                data.products.forEach(product => {
                    const productDiv = document.createElement('div');
                    productDiv.className = 'col-md-4 mb-4';
                    productDiv.innerHTML = `
                        <div class="card product-card">
                            <a href="products/items.php?id=${product.id}">
                                <img src="../admin/products/images/${product.images[0] || 'default.jpg'}" 
                                     class="card-img-top" 
                                     alt="${product.name}"
                                     onerror="this.src='../admin/products/images/default.jpg';">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">${product.name}</h5>
                                <p class="card-text">Ksh ${product.price}</p>
                                <button class="btn btn-primary" onclick="openProductModal(${product.id})">Add to Cart</button>
                            </div>
                        </div>
                    `;
                    productList.appendChild(productDiv);
                });
                // Update pagination (example)
                const pagination = document.getElementById('pagination');
                if (pagination) {
                    pagination.innerHTML = ''; // Clear existing
                    for (let i = 1; i <= data.totalPages; i++) {
                        const li = document.createElement('li');
                        li.className = `page-item ${i === page ? 'active' : ''}`;
                        li.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="fetchProducts(${i}, '${searchTerm}')">${i}</a>`;
                        pagination.appendChild(li);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching products:', error);
                showToast('Failed to load products', false);
            });
    }
</script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="files/script.js"></script>
</body>
</html>



<?php
require_once '../php/db.php';
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $fetchAll = isset($_GET['all']) && $_GET['all'] === 'true';
    $search_param = "%$search%";

    if ($fetchAll) {
        $query = "SELECT p.id, p.name, p.selling_price, p.stock, COALESCE(p.images, 'default.jpg') as images, 
                         GROUP_CONCAT(DISTINCT pv.color) as colors, 
                         GROUP_CONCAT(DISTINCT pv.size) as sizes
                  FROM products p
                  LEFT JOIN product_variants pv ON p.id = pv.product_id
                  WHERE p.name LIKE ? OR p.description LIKE ?
                  GROUP BY p.id";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $search_param, $search_param);
        $totalPages = 1;
        $currentPage = 1;
    } else {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $itemsPerPage = 6;
        $offset = ($page - 1) * $itemsPerPage;

        $query = "SELECT p.id, p.name, p.selling_price, p.stock, COALESCE(p.images, 'default.jpg') as images, 
                         GROUP_CONCAT(DISTINCT pv.color) as colors, 
                         GROUP_CONCAT(DISTINCT pv.size) as sizes
                  FROM products p
                  LEFT JOIN product_variants pv ON p.id = pv.product_id
                  WHERE p.name LIKE ? OR p.description LIKE ?
                  GROUP BY p.id
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssii', $search_param, $search_param, $itemsPerPage, $offset);

        $totalQuery = "SELECT COUNT(*) as total FROM products WHERE name LIKE ? OR description LIKE ?";
        $totalStmt = $conn->prepare($totalQuery);
        $totalStmt->bind_param('ss', $search_param, $search_param);
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result();
        $totalItems = $totalResult->fetch_assoc()['total'];
        $totalStmt->close();
        $totalPages = ceil($totalItems / $itemsPerPage);
        $currentPage = $page;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['colors'] = $row['colors'] ? array_unique(explode(',', $row['colors'])) : [];
        $row['sizes'] = $row['sizes'] ? array_unique(explode(',', $row['sizes'])) : [];
        $row['images'] = $row['images'] ? explode(',', $row['images']) : ['default.jpg'];
        $products[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'products' => $products,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch products: ' . $e->getMessage()
    ]);
}
?>





<?php
require_once 'php/db.php';
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
       <input
  type="email"
  class="form-control"
  id="contact-email"
  name="email"
  value="<?= htmlspecialchars($userEmail) ?>"
  placeholder="Your Email"
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

  <!-- ==================== JAVASCRIPT ==================== -->
  <script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let currentPage = 1;
    const PER_PAGE = 12;
    let currentCategory = 'all';
    let searchTerm = '';

    /* ---------- TOAST ---------- */
    function showToast(msg, ok = true) {
      const t = document.getElementById('appToast');
      t.querySelector('.toast-body').textContent = msg;
      t.classList.toggle('bg-success', ok);
      t.classList.toggle('bg-danger', !ok);
      new bootstrap.Toast(t).show();
    }

    /* ---------- PASSWORD TOGGLE ---------- */
    function togglePassword(inputId, icon) {
      const input = document.getElementById(inputId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        input.type = 'password';
        icon.innerHTML = '<i class="fas fa-eye"></i>';
      }
    }

    /* ---------- POPUP HELPERS ---------- */
    function openPopup(id) {
      closeAllPopups();
      const el = document.getElementById(id);
      if (el) el.style.display = 'flex';
    }
    function closeAllPopups() {
      document.querySelectorAll('.popup').forEach(p => p.style.display = 'none');
    }
    function closePopup() { closeAllPopups(); }
    function closePopupOnClick(e) {
      if (e.target.classList.contains('popup')) closePopup();
    }
    

    function openContactPopup() { openPopup('contact-popup'); }
    function openHelpPopup() { openPopup('help-popup'); }
    
    function openSignupPopup() { openPopup('signup-popup'); }
    function openLoginPopup() { openPopup('login-popup'); }
    function openForgotPasswordPopup() { openPopup('forgot-password-popup'); }

    /* ---------- CART ---------- */
    
function addToCart() {
  console.log('Adding to cart');
  const color = document.getElementById('modal-product-color').value || null;
  const size = document.getElementById('modal-product-size').value || null;
  const quantity = parseInt(document.getElementById('modal-quantity').value);
  console.log('Selected values for addToCart:', { product_id: selectedProduct.id, color, size, quantity });
  if (quantity < 1 || quantity > selectedProduct.stock) {
    showToast('Invalid quantity', false);
    return;
  }
  const variantBody = `product_id=${selectedProduct.id}&color=${encodeURIComponent(color || 'None')}&size=${encodeURIComponent(size || 'None')}`;
  console.log('get_product_variants.php request body:', variantBody);
  fetch('products/get_product_variants.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: variantBody
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Variant response:', data);
      let variantId = null;
      if (data.success) {
        variantId = data.variant_id;
      } else {
        console.log('Variant not found, proceeding with provided color and size');
      }
      const cartBody = `product_id=${selectedProduct.id}${variantId ? `&variant_id=${variantId}` : ''}&quantity=${quantity}${color ? `&color=${encodeURIComponent(color)}` : ''}${size ? `&size=${encodeURIComponent(size)}` : ''}`;
      console.log('add_to_cart.php request body:', cartBody);
      fetch('products/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: cartBody
      })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          console.log('Add to cart response:', data);
          if (data.success) {
            showToast(data.message, true);
            fetchCart();
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
          } else {
            showToast(data.message, false);
            openLoginPopup();
          }
        })
        .catch(error => {
          console.error('Error adding to cart:', error);
          showToast('Failed to add to cart', false);
        });
    })
    .catch(error => {
      console.error('Error fetching variant:', error);
      const cartBody = `product_id=${selectedProduct.id}&quantity=${quantity}${color ? `&color=${encodeURIComponent(color)}` : ''}${size ? `&size=${encodeURIComponent(size)}` : ''}`;
      console.log('add_to_cart.php fallback request body:', cartBody);
      fetch('products/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: cartBody
      })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          console.log('Add to cart response (fallback):', data);
          if (data.success) {
            showToast(data.message, true);
            fetchCart();
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
          } else {
            showToast(data.message, false);
            openLoginPopup();
          }
        })
        .catch(error => {
          console.error('Error adding to cart (fallback):', error);
          showToast('Failed to add to cart', false);
        });
    });
}


function openCartModal() {
  console.log('Opening cart modal');
  fetch('php/check_auth.php')
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Auth check for cart:', data);
      if (data.success && data.is_authenticated) {
        fetchCart();
        new bootstrap.Modal(document.getElementById('cartModal')).show();
      } else {
        showToast('Please login to view cart', false);
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error checking authentication:', error);
      showToast('Failed to check authentication', false);
      openLoginPopup();
    });
}

function fetchCart() {
  console.log('Fetching cart');
  fetch('products/get_cart.php')
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Cart response:', data);
      if (data.success) {
        updateCartDisplay(data.cart, data.total);
        updateCartCount(data.cart);
      } else {
        showToast(data.message, false);
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error fetching cart:', error);
      showToast('Failed to fetch cart', false);
    });
}

function updateCartDisplay(cart, total) {
  const cartItemsDiv = document.getElementById('cart-items');
  cartItemsDiv.innerHTML = '';
  if (cart.length === 0) {
    cartItemsDiv.innerHTML = '<p class="text-center">Your cart is empty.</p>';
  } else {
    cart.forEach((item, index) => {
      console.log(`Cart item ${index + 1}:`, JSON.stringify(item, null, 2));
      const cartItem = document.createElement('div');
      cartItem.classList.add('cart-item');
      const imagePath = item.image 
        ? `admin/products/${item.image}` 
        : 'admin/products/images/default.jpg';
      cartItem.innerHTML = `
        <img src="${imagePath}" alt="${item.name}" 
             onerror="this.src='admin/products/images/default.jpg';">
        <div class="cart-item-details">
          <p><strong>${item.name}</strong></p>
          <p>Color: ${item.color || 'N/A'}</p>
          <p>Size: ${item.size || 'N/A'}</p>
          <p>Price: Ksh ${item.item_total.toFixed(2)}</p>
          <div class="quantity-control">
            <button id="cartMinus-${item.cart_id}" onclick="adjustCartQuantity(${item.cart_id}, -1)"><i class="fas fa-minus"></i></button>
            <input type="number" value="${item.quantity}" id="cart-quantity-${item.cart_id}" min="1" max="${item.stock}" readonly>
            <button id="cartPlus-${item.cart_id}" onclick="adjustCartQuantity(${item.cart_id}, 1)"><i class="fas fa-plus"></i></button>
          </div>
        </div>
        <button class="btn btn-danger btn-sm" onclick="removeFromCart(${item.cart_id})">Remove</button>
      `;
      cartItemsDiv.appendChild(cartItem);
    });
  }
  document.getElementById('cart-total').textContent = `Total: Ksh ${total.toFixed(2)}`;
}

function updateCartCount(cart) {
  const cartCount = cart.length;
  document.getElementById('cart-count').textContent = cartCount;
}

function adjustCartQuantity(cartId, change) {
  console.log('Adjusting cart quantity for cartId:', cartId, 'change:', change);
  const input = document.getElementById(`cart-quantity-${cartId}`);
  let quantity = parseInt(input.value) + change;
  const max = parseInt(input.getAttribute('data-max'));
  if (quantity < 1) quantity = 1;
  if (quantity > max) quantity = max;

  fetch('products/update_cart_quantity.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `cart_id=${cartId}&quantity=${quantity}`
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Update cart quantity response:', data);
      if (data.success) {
        input.value = quantity;
        fetchCart();
        showToast('Quantity updated', true);
      } else {
        showToast(data.message, false);
      }
    })
    .catch(error => {
      console.error('Error updating quantity:', error);
      showToast('Failed to update quantity', false);
    });
}

function removeFromCart(cartId) {
  console.log('Removing from cart, cartId:', cartId);
  fetch('products/remove_from_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `cart_id=${cartId}`
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Remove from cart response:', data);
      if (data.success) {
        fetchCart();
        showToast('Item removed from cart', true);
      } else {
        showToast(data.message, false);
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error removing from cart:', error);
      showToast('Failed to remove from cart', false);
    });
}

function placeOrder() {
  console.log('Placing order');
  const placeOrderBtn = document.querySelector('#cartModal .btn-primary');
  placeOrderBtn.disabled = true;
  placeOrderBtn.textContent = 'Processing...';

  fetch('products/get_cart.php')
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Cart for order:', data);
      if (!data.success || data.cart.length === 0) {
        placeOrderBtn.disabled = false;
        placeOrderBtn.textContent = 'Place Order';
        showToast('Cart is empty or failed to fetch cart', false);
        openLoginPopup();
        return;
      }

      const orderData = {
        items: data.cart.map(item => ({
          cart_id: item.cart_id,
          product_id: item.product_id,
          variant_id: item.variant_id,
          quantity: item.quantity,
          price: item.item_total / item.quantity,
          color: item.color || 'N/A',
          size: item.size || 'N/A'
        }))
      };

      fetch('products/place_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
      })
        .then(response => {
          if (!response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
          }
          return response.json();
        })
        .then(data => {
          console.log('Place order response:', data);
          placeOrderBtn.disabled = false;
          placeOrderBtn.textContent = 'Place Order';
          if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
            const thankYou = document.getElementById('thank-you-popup');
if (thankYou) {
  thankYou.style.display = 'flex';
}

            fetchCart();
            showToast('Order placed successfully', true);
          } else {
            showToast(data.message || 'Failed to place order', false);
            openLoginPopup();
          }
        })
        .catch(error => {
          placeOrderBtn.disabled = false;
          placeOrderBtn.textContent = 'Place Order';
          console.error('Error placing order:', error);
          showToast(`Failed to place order: ${error.message}`, false);
        });
    })
    .catch(error => {
      placeOrderBtn.disabled = false;
      placeOrderBtn.textContent = 'Place Order';
      console.error('Error fetching cart for order:', error);
      showToast('Failed to fetch cart for order', false);
    });
}
    /* ---------- PRODUCTS ---------- */
    function fetchProducts() {
      const section = document.getElementById('product-section');
      section.innerHTML = '<p class="text-center loading-message">Loading products...</p>';

       const url = `products/get_products.php?page=${currentPage}&per_page=${PER_PAGE}&search=${encodeURIComponent(searchTerm)}&category=${currentCategory}`;

      fetch(url)
        .then(r => r.ok ? r.json() : Promise.reject(new Error('Network error')))
        .then(data => {
          if (data.success && data.products) {
            displayProducts(data.products);
            renderPagination(data.totalPages || 1);
          } else {
            section.innerHTML = `<p class="text-danger text-center">${data.message || 'No products found.'}</p>`;
          }
        })
        .catch(err => {
          console.error(err);
          section.innerHTML = '<p class="text-danger text-center">Failed to load products. Please try again.</p>';
        });
    }

    function displayProducts(products) {
      const section = document.getElementById('product-section');
      section.innerHTML = '';

      if (!products.length) {
        section.innerHTML = '<p class="text-center text-muted">No products found.</p>';
        return;
      }

      products.forEach(p => {
        const img = p.images?.[0] || 'default.jpg';
        const col = document.createElement('div');
        col.className = 'col-6 col-sm-4 col-md-3 col-lg-3 col-xl-2';
        col.innerHTML = `
          <div class="card h-100 product-card shadow-sm">
            <a href="php/items.php?id=${p.id}" class="text-decoration-none text-dark">
              <img src="admin/products/${img}" class="card-img-top" alt="${p.name}" style="height:150px; object-fit:cover;" 
                   onerror="this.src='admin/products/images/default.jpg';">
              <div class="card-body d-flex flex-column p-2">
                <h6 class="card-title mb-1 small">${p.name}</h6>
                <p class="card-text text-success fw-bold mb-1 small">Ksh ${parseFloat(p.selling_price).toFixed(2)}</p>
                <p class="card-text text-muted mb-2 small">Stock: ${p.stock}</p>
                <div class="mt-auto">
                  <button class="btn btn-primary btn-sm w-100" 
                          onclick="event.preventDefault(); addToCart($ '${p.name.replace(/'/g, "\\'")}', ${p.selling_price}, '${img}')">
                    view Details
                  </button> 
                </div>
              </div>
            </a>
          </div>
        `;
        section.appendChild(col);
      });
    }

    function renderPagination(totalPages) {
      const ul = document.getElementById('pagination');
      ul.innerHTML = '';
      for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="currentPage=${i}; fetchProducts(); return false;">${i}</a>`;
        ul.appendChild(li);
      }
    }
    document.getElementById('pagination').style.display = 'flex';
document.getElementById('pagination').style.position = 'relative';
document.getElementById('pagination').style.zIndex = '9999';


    function searchItems() {
      searchTerm = document.getElementById('searchInput').value.trim();
      currentPage = 1;
      fetchProducts();
    }

    // Category Filter
    document.querySelectorAll('#category-filter button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('#category-filter button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = btn.dataset.category;
        currentPage = 1;
        fetchProducts();
      });
    });

    // Track Orders
    
function openTrackOrdersModal() {
  console.log('Opening track orders modal');
  fetch('products/get_orders.php')
    .then(response => response.json())
    .then(data => {
      console.log('Orders response:', data);
      if (data.success) {
        displayOrders(data.orders);
        new bootstrap.Modal(document.getElementById('trackOrdersModal')).show();
      } else {
        showToast(data.message, false);
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error fetching orders:', error);
      showToast('Failed to fetch orders', false);
    });
}
function displayOrders(orders) {
  const ordersList = document.getElementById('orders-list');
  ordersList.innerHTML = '';

  if (orders.length === 0) {
    ordersList.innerHTML = '<p class="text-center text-muted">No orders found.</p>';
    return;
  }

  // Sort newest first
  orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

  const ORDERS_PER_PAGE = 2;
  let currentStartIndex = 0;

  const container = document.createElement('div');
  container.id = 'orders-container';

  // Function to render current page of orders
  function renderPage() {
    container.innerHTML = ''; // Clear previous orders

    const start = currentStartIndex;
    const end = Math.min(start + ORDERS_PER_PAGE, orders.length);

    for (let i = start; i < end; i++) {
      const order = orders[i];
      const orderItem = document.createElement('div');
      orderItem.className = 'order-item mb-4 p-3 border rounded bg-light';

      orderItem.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <strong>Order Date:</strong> ${new Date(order.created_at).toLocaleDateString('en-US', {
              weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            })}<br>
            <strong>Total:</strong> <span class="text-success fw-bold">Ksh ${parseFloat(order.total_amount).toFixed(2)}</span><br>
            <strong>Status:</strong> 
            <span class="badge bg-${order.status === 'pending' ? 'warning' : 
                                   order.status === 'shipped' ? 'info' : 
                                   order.status === 'delivered' ? 'success' : 'secondary'}">
              ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
            </span>
          </div>
        </div>
        <hr>
        <strong>Items:</strong>
        <ul class="list-unstyled ms-3">
          ${order.items.map(item => `
            <li class="mb-2">
              • <strong>${item.name}</strong><br>
              &nbsp;&nbsp; Qty: ${item.quantity} × Ksh ${parseFloat(item.price).toFixed(2)} 
              ${item.color ? ` | Color: ${item.color}` : ''} 
              ${item.size ? ` | Size: ${item.size}` : ''}
            </li>
          `).join('')}
        </ul>
      `;

      container.appendChild(orderItem);
    }

    // Update buttons
    updateButtons();
  }

  // Update Load More / Previous buttons
  function updateButtons() {
    // Remove old buttons
    const oldButtons = ordersList.querySelector('.pagination-controls');
    if (oldButtons) oldButtons.remove();

    const hasPrevious = currentStartIndex > 0;
    const hasNext = currentStartIndex + ORDERS_PER_PAGE < orders.length;

    if (hasPrevious || hasNext) {
      const buttonGroup = document.createElement('div');
      buttonGroup.className = 'pagination-controls d-flex justify-content-between mt-4';

      if (hasPrevious) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'btn btn-outline-secondary';
        prevBtn.textContent = '← Previous';
        prevBtn.onclick = () => {
          currentStartIndex = Math.max(0, currentStartIndex - ORDERS_PER_PAGE);
          renderPage();
        };
        buttonGroup.appendChild(prevBtn);
      }

      if (hasNext) {
        const nextBtn = document.createElement('button');
        nextBtn.className = 'btn btn-primary ms-auto';
        nextBtn.textContent = 'Load More →';
        nextBtn.onclick = () => {
          currentStartIndex += ORDERS_PER_PAGE;
          renderPage();
        };
        buttonGroup.appendChild(nextBtn);
      }

      ordersList.appendChild(buttonGroup);
    }
  }

  // Initial render
  ordersList.appendChild(container);
  renderPage();
}
    /* ---------- INIT ---------- */
    window.addEventListener('load', () => {
      fetchProducts();
      
    });

    document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contact-form');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const email = document.getElementById('contact-email');
    const message = document.getElementById('contact-message');
    const btn = form.querySelector('button[type="submit"]');

    btn.disabled = true;
    btn.textContent = 'Sending...';

    fetch('php/send_contact_message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `email=${encodeURIComponent(email.value)}&message=${encodeURIComponent(message.value)}`
    })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false;
        btn.textContent = 'Send Message';

        closeAllPopups();

        if (data.success) {
          showToast('Message sent successfully!', true);
          message.value = '';
        } else {
          showToast('Failed to send message', false);
        }
      })
      .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.textContent = 'Send Message';
        showToast('Error sending message', false);
      });
  });
});

 // Pure JS for the Settings Dropdown
function closePopupOnClick(e) {
  if (e.target.classList.contains('popup')) closePopup();
}

const settingsToggle = document.getElementById('settingsDropdown');
  const dropdownMenu = settingsToggle?.nextElementSibling;

  if (settingsToggle && dropdownMenu) {
    // Toggle dropdown on click
    settingsToggle.addEventListener('click', function (e) {
      e.preventDefault(); // Prevent default anchor behavior
      e.stopPropagation(); // Prevent event bubbling

      const isShown = dropdownMenu.classList.contains('show');
      
      // First, close any other open dropdowns (optional, but clean)
      document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
        menu.classList.remove('show');
      });

      // Toggle current menu
      if (isShown) {
        dropdownMenu.classList.remove('show');
      } else {
        dropdownMenu.classList.add('show');
      }
    });

    // Close dropdown when clicking anywhere outside
    document.addEventListener('click', function () {
      dropdownMenu.classList.remove('show');
    });

    // Prevent clicks inside the dropdown menu from closing it
    dropdownMenu.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  
// Handle Signup Form Submission via AJAX
document.getElementById('signup-form').addEventListener('submit', function(e) {
  e.preventDefault(); // Prevent page redirect

  const btn = document.getElementById('signup-btn');
  btn.disabled = true;
  btn.textContent = 'Signing up...';

  const formData = new FormData(this);

  fetch('php/signup.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json()) // Expecting JSON response
  .then(data => {
    btn.disabled = false;
    btn.textContent = 'Sign Up';

    if (data.success) {
      showToast('Account created successfully! Please log in.', true);
      closeAllPopups();
      openLoginPopup(); // Optional: open login after success
      this.reset(); // Clear form
    } else {
      showToast(data.message || 'Signup failed', false);
    }
  })
  .catch(error => {
    console.error('Signup error:', error);
    btn.disabled = false;
    btn.textContent = 'Sign Up';
    showToast('Network error. Please try again.', false);
  });
});


  </script>

  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JS -->
  <script src="files/script.js"></script>
</body>
</html>
 function openContactPopup() { openPopup('contact-popup'); }
    function openHelpPopup() { openPopup('help-popup'); }
    function openAboutUsPopup() { openPopup('about-us-popup'); }
    function openSignupPopup() { openPopup('signup-popup'); }
    function openLoginPopup() { openPopup('login-popup'); }
    function openForgotPasswordPopup() { openPopup('forgot-password-popup'); }
