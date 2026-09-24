<?php
session_start();

const IMAGE_BASE_PATH = '/shop/admin/products/';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

require_once '../php/db.php';
if (!$conn) die('DB connection failed.');

$product = null;
$error_message = null;
$variants = [];
$colors = [];
$sizes = [];
$reviews = [];
$average_rating = 0;

// === FETCH PRODUCT ===
if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, name, description, selling_price, stock, images FROM products WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();

            // Split images: "img1.jpg,img2.jpg" → ['img1.jpg', 'img2.jpg']
            $image_paths = $product['images'] ? array_map('trim', explode(',', $product['images'])) : [];
            $product['images'] = array_filter($image_paths);
            $product['main_image'] = $product['images'][0] ?? null;
        } else {
            $error_message = "Product not found.";
        }
        $stmt->close();
    }

    // === FETCH VARIANTS ===
    if ($product) {
        $stmt = $conn->prepare("SELECT color, size, stock, additional_price FROM product_variants WHERE product_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $colors = array_filter(array_unique(array_column($variants, 'color')));
            $sizes = array_filter(array_unique(array_column($variants, 'size')));
        }
    }

    // === FETCH REVIEWS ===
    if ($product) {
        $stmt = $conn->prepare("
            SELECT r.rating, r.comment, r.created_at, u.username 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? 
            ORDER BY r.created_at DESC
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if ($reviews) {
                $total = array_sum(array_column($reviews, 'rating'));
                $average_rating = round($total / count($reviews), 1);
            }
        }
    }
} else {
    $error_message = "Invalid product ID.";
}

// === ADD TO CART ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$isLoggedIn) {
        $_SESSION['error'] = "Login to add to cart";
        header("Location: ../index.php");
        exit();
    }

    $color = $_POST['color'] ?? 'N/A';
    $size = $_POST['size'] ?? 'N/A';
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $variant_id = null;
    $max_stock = $product['stock'];
    $price = $product['selling_price'];

    if ($variants) {
        $stmt = $conn->prepare("SELECT id, stock, additional_price FROM product_variants WHERE product_id = ? AND color = ? AND size = ?");
        if ($stmt) {
            $stmt->bind_param("iss", $id, $color, $size);
            $stmt->execute();
            $res = $stmt->get_result();
            $variant = $res->num_rows === 1 ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$variant) {
                $_SESSION['error'] = "Invalid variant.";
                header("Location: items.php?id=$id");
                exit();
            }
            $variant_id = $variant['id'];
            $max_stock = $variant['stock'];
            $price += $variant['additional_price'];
        }
    }

    if ($quantity > $max_stock) {
        $_SESSION['error'] = "Only $max_stock in stock.";
        header("Location: items.php?id=$id");
        exit();
    }

    $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND " . ($variant_id ? "variant_id = ?" : "variant_id IS NULL");
    $check_stmt = $conn->prepare($check_sql);
    if ($variant_id) {
        $check_stmt->bind_param("iii", $_SESSION['user_id'], $id, $variant_id);
    } else {
        $check_stmt->bind_param("ii", $_SESSION['user_id'], $id);
    }
    $check_stmt->execute();
    $cart_item = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($cart_item) {
        $new_qty = $cart_item['quantity'] + $quantity;
        if ($new_qty > $max_stock) {
            $_SESSION['error'] = "Cannot exceed stock.";
            header("Location: items.php?id=$id");
            exit();
        }
        $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $upd->bind_param("ii", $new_qty, $cart_item['id']);
        $upd->execute();
        $upd->close();
        $_SESSION['success'] = "Cart updated!";
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
        $user_id = $_SESSION['user_id'];
        if ($variant_id) {
            $ins->bind_param("iiii", $user_id, $id, $variant_id, $quantity);
        } else {
            $null = null;
            $ins->bind_param("iiis", $user_id, $id, $null, $quantity);
        }
        $ins->execute();
        $ins->close();
        $_SESSION['success'] = "Added to cart!";
    }
    header("Location: items.php?id=$id");
    exit();
}

// === SUBMIT REVIEW ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$isLoggedIn) {
        $_SESSION['error'] = "Login to review.";
        header("Location: ../index.php");
        exit();
    }
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5 || empty($comment)) {
        $_SESSION['error'] = "Valid rating and comment required.";
        header("Location: items.php?id=$id");
        exit();
    }
    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, username, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("iisis", $id, $_SESSION['user_id'], $username, $rating, $comment);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Review submitted!";
    }
    header("Location: items.php?id=$id");
    exit();
}

// Flash message
$flash_message = $_SESSION['error'] ?? $_SESSION['success'] ?? null;
$flash_type = isset($_SESSION['success']) ? 'success' : 'danger';
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $product ? htmlspecialchars($product['name']) : 'Product' ?> - Feira Moderna</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Yatra+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../files/styles.css">
  <style>
    .product-container { max-width: 900px; margin: 2rem auto; padding: 1rem; }
    .carousel-item img { max-height: 450px; object-fit: contain; background: #f8f9fa; }
    .image-gallery { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; justify-content: center; }
    .image-gallery img { width: 80px; height: 80px; object-fit: cover; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .image-gallery img:hover, .image-gallery img.active { border-color: #4CAF50; }
    .quantity-control { display: flex; align-items: center; gap: 10px; }
    .quantity-control input { width: 70px; text-align: center; }
    .star-rating-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; }
    .star-rating-input input { display: none; }
    .star-rating-input label { font-size: 1.8rem; color: #ccc; cursor: pointer; }
    .star-rating-input label:hover, .star-rating-input label:hover ~ label,
    .star-rating-input input:checked ~ label { color: #f39c12; }
    .review-item { border-bottom: 1px solid #eee; padding: 15px 0; }
    .price-stock { font-size: 1.3rem; font-weight: 600; color: #2c3e50; }
    .btn-primary { background-color: #4CAF50; border: none; }
    .btn-primary:hover { background-color: #45a049; }
    .img-fallback { 
      width: 100%; height: 400px; background: #f0f0f0; 
      display: flex; align-items: center; justify-content: center;
      color: #999; font-size: 1.2rem; border: 2px dashed #ccc;
    }
  </style>
</head>
<body>

  <!-- Toast -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center text-white border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <?php include('../php/includes/items_navbar.php'); ?>

  <main class="container-fluid my-5">
    <div class="product-container">

      <?php if ($product): ?>
        <div class="row g-4">
          <!-- Image Section -->
          <div class="col-lg-6">
            <div id="productCarousel" class="carousel slide">
              <div class="carousel-inner">
                <?php if (!empty($product['images'])): ?>
                  <?php foreach ($product['images'] as $index => $img): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                      <img 
                        src="<?= IMAGE_BASE_PATH . htmlspecialchars($img) ?>" 
                        class="d-block w-100" 
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        onerror="this.onerror=null; this.outerHTML='<div class=\'img-fallback\'>Image Not Available</div>'">
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="carousel-item active">
                    <div class="img-fallback">No Image</div>
                  </div>
                <?php endif; ?>
              </div>

              <?php if (count($product['images']) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                  <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                  <span class="carousel-control-next-icon"></span>
                </button>
              <?php endif; ?>
            </div>

            <?php if (count($product['images']) > 1): ?>
              <div class="image-gallery mt-3">
                <?php foreach ($product['images'] as $img): ?>
                  <img 
                    src="<?= IMAGE_BASE_PATH . htmlspecialchars($img) ?>"
                    alt="thumb"
                    data-img="<?= htmlspecialchars($img) ?>"
                    class="<?= $img === $product['main_image'] ? 'active' : '' ?>"
                    onclick="selectThumb(this)"
                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNDAiIHk9IjQwIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObzwvdGV4dD48L3N2Zz4='">
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Details -->
          <div class="col-lg-6">
            <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="price-stock mb-3">
              <p><strong>Price:</strong> <span id="display-price">Ksh <?= number_format($product['selling_price'], 2) ?></span></p>
              <p><strong>Stock:</strong> <span id="stock-info"><?= $product['stock'] ?></span></p>
              <p><strong>Rating:</strong>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fas fa-star <?= $i <= $average_rating ? 'text-warning' : 'text-muted' ?>"></i>
                <?php endfor; ?>
                <small>(<?= $average_rating ?> / 5)</small>
              </p>
            </div>

            <form method="POST" id="addToCartForm">
              <?php if ($colors): ?>
                <div class="mb-3">
                  <label class="form-label">Color</label>
                  <select class="form-select" id="color" name="color" onchange="updateVariant()" required>
                    <option value="" disabled selected>Select</option>
                    <?php foreach ($colors as $c): ?>
                      <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php else: ?>
                <input type="hidden" name="color" value="N/A">
              <?php endif; ?>

              <?php if ($sizes): ?>
                <div class="mb-3">
                  <label class="form-label">Size</label>
                  <select class="form-select" id="size" name="size" onchange="updateVariant()" required>
                    <option value="" disabled selected>Select</option>
                    <?php foreach ($sizes as $s): ?>
                      <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php else: ?>
                <input type="hidden" name="size" value="N/A">
              <?php endif; ?>

              <div class="mb-3">
                <label class="form-label">Quantity</label>
                <div class="quantity-control">
                  <button type="button" class="btn btn-outline-secondary" onclick="adjustQty(-1)">-</button>
                  <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" readonly>
                  <button type="button" class="btn btn-outline-secondary" onclick="adjustQty(1)">+</button>
                </div>
              </div>

              <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                Add to Cart
              </button>
              <a href="../index.php" class="btn btn-outline-secondary btn-lg ms-2">Back</a>
            </form>
          </div>
        </div>

        <hr class="my-5">

        <!-- Description -->
        <div class="mb-5">
          <h3>Description</h3>
          <p class="lead"><?= nl2br(htmlspecialchars($product['description'] ?: 'No description.')) ?></p>
        </div>

               <!-- Reviews (Collapsible) -->
        <div class="review-section mt-5">
          <h3 class="mb-3">
            <a href="#reviewsCollapse" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="reviewsCollapse"
               class="text-decoration-none text-dark">
              Reviews (<?= count($reviews) ?>)
              <i class="fas fa-chevron-down ms-2" id="reviewsChevron"></i>
            </a>
          </h3>

          <div class="collapse" id="reviewsCollapse">
            <?php if (empty($reviews)): ?>
              <p class="text-muted">No reviews yet. Be the first to review!</p>
            <?php else: ?>
              <div class="border rounded p-3 bg-light">
                <?php foreach ($reviews as $r): ?>
                  <div class="review-item">
                    <div class="d-flex justify-content-between align-items-start">
                      <strong><?= htmlspecialchars($r['username']) ?></strong>
                      <small class="text-muted"><?= date('M d, Y', strtotime($r['created_at'])) ?></small>
                    </div>
                    <div class="mb-1">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= $r['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                      <?php endfor; ?>
                    </div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                  </div>
                  <hr class="my-3">
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Write a Review (always visible, outside the collapse so users can easily find it) -->
            <?php if ($isLoggedIn): ?>
              <div class="mt-4 p-4 border rounded bg-white">
                <h5>Write a Review</h5>
                <form method="POST">
                  <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <div class="star-rating-input">
                      <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                        <label for="star<?= $i ?>" class="fas fa-star"></label>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <div class="mb-3">
                    <textarea class="form-control" name="comment" rows="3" placeholder="Your review..." required></textarea>
                  </div>
                  <button type="submit" name="submit_review" class="btn btn-success">Submit Review</button>
                </form>
              </div>
            <?php else: ?>
              <p class="mt-3"><a href="../index.php#login-popup">Login</a> to write a review.</p>
            <?php endif; ?>
          </div>
        </div>

      <?php else: ?>
        <div class="text-center py-5">
          <h1>Not Found</h1>
          <p><?= htmlspecialchars($error_message) ?></p>
         <a href="/shop/index.php" class="btn btn-primary">Home</a>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <?php include('../php/includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const variants = <?= json_encode($variants) ?>;
    const basePrice = <?= $product['selling_price'] ?? 0 ?>;
    const baseStock = <?= $product['stock'] ?? 0 ?>;

   function selectThumb(thumb) {
      // Remove active class from all thumbnails
      document.querySelectorAll('.image-gallery img').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');

      const file = thumb.dataset.img;
      const carouselElement = document.querySelector('#productCarousel');
      let carousel = bootstrap.Carousel.getInstance(carouselElement);

      // If instance doesn't exist, create one
      if (!carousel) {
        carousel = new bootstrap.Carousel(carouselElement);
      }

      const items = document.querySelectorAll('.carousel-item');
      items.forEach((item, i) => {
        const img = item.querySelector('img');
        if (img && img.src.includes(file)) {
          carousel.to(i); // Now safe to call
        }
      });
    }

    function adjustQty(change) {
      const input = document.getElementById('quantity');
      let val = parseInt(input.value) + change;
      const max = parseInt(input.max) || baseStock;
      if (val < 1) val = 1;
      if (val > max) val = max;
      input.value = val;
    }

    function updateVariant() {
      const color = document.getElementById('color')?.value || 'N/A';
      const size = document.getElementById('size')?.value || 'N/A';
      const qtyInput = document.getElementById('quantity');
      const stockSpan = document.getElementById('stock-info');
      const priceSpan = document.getElementById('display-price');

      let stock = baseStock;
      let price = basePrice;

      if (variants.length && color !== 'N/A' && size !== 'N/A') {
        const v = variants.find(x => x.color === color && x.size === size);
        if (v) {
          stock = v.stock;
          price += v.additional_price;
        }
      }

      stockSpan.textContent = stock;
      qtyInput.max = stock;
      if (parseInt(qtyInput.value) > stock) qtyInput.value = stock > 0 ? stock : 1;
      priceSpan.textContent = `Ksh ${price.toFixed(2)}`;
    }

    function showToast(msg, success = false) {
      const toast = document.getElementById('appToast');
      toast.className = 'toast align-items-center text-white border-0 ' + (success ? 'bg-success' : 'bg-danger');
      toast.querySelector('.toast-body').textContent = msg;
      new bootstrap.Toast(toast).show();
    }

    document.addEventListener('DOMContentLoaded', () => {
      updateVariant();
      <?php if ($flash_message): ?>
        showToast("<?= addslashes($flash_message) ?>", <?= $flash_type === 'success' ? 'true' : 'false' ?>);
      <?php endif; ?>
    });
  </script>

  <script src="../files/script.js"></script>
</body>
</html>
<?php $conn->close(); ?>