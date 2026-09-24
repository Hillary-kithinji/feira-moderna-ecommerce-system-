<?php
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

require_once '../php/db.php';

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $items_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);
    $offset = ($page - 1) * $items_per_page;

    if ($_GET['ajax'] === 'orders') {
        $result = $conn->query("SELECT o.id, u.username, o.total_amount, o.status, o.created_at 
                                FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                ORDER BY o.created_at DESC 
                                LIMIT $items_per_page OFFSET $offset");
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $total_result = $conn->query("SELECT COUNT(*) as total FROM orders");
        $total = $total_result->fetch_assoc()['total'];
        echo json_encode(['orders' => $orders, 'total' => $total, 'page' => $page, 'items_per_page' => $items_per_page]);
    } elseif ($_GET['ajax'] === 'products') {
        $result = $conn->query("SELECT p.id, p.name, c.name as category, p.images, p.stock, p.selling_price 
                                FROM products p 
                                JOIN categories c ON p.category_id = c.id 
                                LIMIT $items_per_page OFFSET $offset");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $total_result = $conn->query("SELECT COUNT(*) as total FROM products");
        $total = $total_result->fetch_assoc()['total'];
        echo json_encode(['products' => $products, 'total' => $total, 'page' => $page, 'items_per_page' => $items_per_page]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feira Moderna - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Yatra+One&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="files/styles.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; background-color: #f4f4f4; }
    .sidebar { width: 100%; background-color: #4CAF50; color: white; padding: 20px; position: relative; display: none; }
    .sidebar.active { display: block; }
    .sidebar .nav-link { color: white; font-size: 16px; padding: 10px; border-radius: 5px; margin-bottom: 5px; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #45a049; }
    .content { padding: 20px; flex-grow: 1; }
    .card { border: 2px solid #4CAF50; border-radius: 10px; margin-bottom: 20px; }
    .card-header { background-color: #4CAF50; color: white; font-family: 'Yatra One', cursive; }
    .table-responsive { overflow-x: auto; }
    .table { min-width: 100%; }
    .table th { background-color: #f8f9fa; }
    .modal-content { border: 2px solid #4CAF50; }
    .modal-header { background-color: #4CAF50; color: white; }
    .variant-entry { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
    .variant-entry .row { margin-bottom: 10px; }
    .remove-variant-btn { background-color: #ff4444; border: none; }
    .product-image { width: 50px; height: auto; margin-right: 5px; }
    #salesChart { max-height: 400px; width: 100%; }
    .hamburger { display: none; font-size: 24px; background: none; border: none; color: #4CAF50; padding: 10px; cursor: pointer; }
    .pagination { margin-top: 20px; }
    .pagination .page-item.active .page-link { background-color: #4CAF50; border-color: #4CAF50; }
    .pagination .page-link { color: #4CAF50; }
    .pagination .page-link:hover { background-color: #45a049; color: white; }
    @media (min-width: 769px) {
      body { flex-direction: row; }
      .sidebar { width: 250px; position: fixed; height: 100%; overflow-y: auto; display: block; }
      .content { margin-left: 250px; }
      .row { display: flex; flex-wrap: wrap; }
      .col-md-4 { flex: 0 0 33.3333%; max-width: 33.3333%; }
    }
    @media (max-width: 768px) {
      .row { flex-direction: column; }
      .col-md-4 { flex: 0 0 100%; max-width: 100%; }
      .hamburger { display: block; }
    }
  </style>
</head>
<body>
  <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
  <nav class="sidebar">
    <h2 class="text-center" style="font-family: 'Yatra One', cursive;">Admin Dashboard</h2>
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="#orders" onclick="showSection('orders')"><i class="fas fa-shopping-cart"></i> Orders</a></li>
      <li class="nav-item"><a class="nav-link" href="#products" onclick="showSection('products')"><i class="fas fa-box"></i> Products</a></li>
      <li class="nav-item"><a class="nav-link" href="#sales" onclick="showSection('sales')"><i class="fas fa-chart-line"></i> Sales Analytics</a></li>
      <li class="nav-item"><a class="nav-link" href="../php/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>

  <div class="content">
    <section id="dashboard" class="section">
      <h1 class="mb-4" style="font-family: 'Yatra One', cursive;">Welcome to the Admin Dashboard</h1>
      <div class="row">
        <div class="col-md-4">
          <div class="card">
            <div class="card-header"><h5>Total Orders</h5></div>
            <div class="card-body">
              <?php
              $result = $conn->query("SELECT COUNT(*) as total FROM orders");
              $total_orders = $result->fetch_assoc()['total'];
              ?>
              <h3><?php echo $total_orders; ?></h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card">
            <div class="card-header"><h5>Total Products</h5></div>
            <div class="card-body">
              <?php
              $result = $conn->query("SELECT COUNT(*) as total FROM products");
              $total_products = $result->fetch_assoc()['total'];
              ?>
              <h3><?php echo $total_products; ?></h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card">
            <div class="card-header"><h5>Total Sales</h5></div>
            <div class="card-body">
              <form id="totalSalesFilterForm" class="mb-3">
                <div class="row">
                  <div class="col-md-5">
                    <label for="salesFilterType">Filter By</label>
                    <select class="form-control" id="salesFilterType" name="salesFilterType">
                      <option value="day">Day</option>
                      <option value="week">Week</option>
                      <option value="month">Month</option>
                    </select>
                  </div>
                  <div class="col-md-5">
                    <label for="salesFilterValue">Select Date</label>
                    <input type="date" class="form-control" id="salesFilterValue" required>
                  </div>
                  <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <button type="button" class="btn btn-success btn-sm mt-2" onclick="downloadTotalSalesReport()">Download</button>
                  </div>
                </div>
              </form>
              <h3 id="totalSalesAmount">
                <?php
                $result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
                $total_sales = $result->fetch_assoc()['total'] ?? 0;
                echo "Ksh " . number_format($total_sales, 2);
                ?>
              </h3>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="orders" class="section" style="display: none;">
      <h1 class="mb-4" style="font-family: 'Yatra One', cursive;">Manage Orders</h1>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr><th>#</th><th>User</th><th>Total Amount</th><th>Status</th><th>Created At</th><th>Actions</th></tr>
          </thead>
          <tbody id="ordersTableBody">
          </tbody>
        </table>
      </div>
      <nav aria-label="Orders pagination">
        <ul class="pagination" id="ordersPagination">
        </ul>
      </nav>
    </section>

    <section id="products" class="section" style="display: none;">
      <h1 class="mb-4" style="font-family: 'Yatra One', cursive;">Manage Products</h1>
      <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product</button>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr><th>#</th><th>Name</th><th>Category</th><th>Images</th><th>Stock</th><th>Selling Price</th><th>Actions</th></tr>
          </thead>
          <tbody id="productsTableBody">
          </tbody>
        </table>
      </div>
      <nav aria-label="Products pagination">
        <ul class="pagination" id="productsPagination">
        </ul>
      </nav>
    </section>

    <section id="sales" class="section" style="display: none;">
      <h1 class="mb-4" style="font-family: 'Yatra One', cursive;">Sales Analytics</h1>
      <div class="card">
        <div class="card-header"><h5>Sales Report</h5></div>
        <div class="card-body">
          <form id="salesFilterForm" class="mb-3">
            <div class="row">
              <div class="col-md-4">
                <label for="startDate">Start Date</label>
                <input type="date" class="form-control" id="startDate" required>
              </div>
              <div class="col-md-4">
                <label for="endDate">End Date</label>
                <input type="date" class="form-control" id="endDate" required>
              </div>
              <div class="col-md-4 align-self-end">
                <button type="submit" class="btn btn-primary">Filter</button>
              </div>
            </div>
          </form>
          <canvas id="salesChart"></canvas>
          <div class="table-responsive mt-3">
            <table class="table table-bordered">
              <thead>
                <tr><th>Date</th><th>Total Sales</th><th>Order Count</th></tr>
              </thead>
              <tbody id="salesTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Add Product Modal -->
  <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addProductForm" enctype="multipart/form-data">
            <div class="form-group mb-3">
              <label for="productName">Name</label>
              <input type="text" class="form-control" id="productName" name="productName" required>
            </div>
            <div class="form-group mb-3">
              <label for="productCategory">Category</label>
              <select class="form-control" id="productCategory" name="productCategory" required>
                <?php
                $result = $conn->query("SELECT id, name FROM categories");
                while ($row = $result->fetch_assoc()) {
                  echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
              </select>
            </div>
            <div class="form-group mb-3">
              <label for="productStock">Total Stock</label>
              <input type="number" class="form-control" id="productStock" name="productStock" required min="0">
              <small class="form-text text-muted">Enter total stock manually. If variants are added, stock will be updated based on variant stock values.</small>
            </div>
            <div class="form-group mb-3">
              <label for="productBuyingPrice">Buying Price</label>
              <input type="number" class="form-control" id="productBuyingPrice" name="productBuyingPrice" step="0.01" required min="0">
            </div>
            <div class="form-group mb-3">
              <label for="productSellingPrice">Selling Price</label>
              <input type="number" class="form-control" id="productSellingPrice" name="productSellingPrice" step="0.01" required min="0">
            </div>
            <div class="form-group mb-3">
              <label for="productDescription">Description</label>
              <textarea class="form-control" id="productDescription" name="productDescription" rows="4"></textarea>
            </div>
            <div class="form-group mb-3">
              <label for="productImages">Product Images</label>
              <input type="file" class="form-control" id="productImages" name="productImages[]" multiple accept="image/*">
              <small class="form-text text-muted">Select multiple images for the product.</small>
            </div>
            <div class="form-group mb-3">
              <label>Variants (Optional)</label>
              <div id="addVariantContainer"></div>
              <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addVariant('addVariantContainer', 'productStock')">Add Variant</button>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="addProduct()">Add Product</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editProductForm" enctype="multipart/form-data">
            <input type="hidden" id="editProductId" name="editProductId">
            <div class="form-group mb-3">
              <label for="editProductName">Name</label>
              <input type="text" class="form-control" id="editProductName" name="editProductName" required>
            </div>
            <div class="form-group mb-3">
              <label for="editProductCategory">Category</label>
              <select class="form-control" id="editProductCategory" name="editProductCategory" required>
                <?php
                $result = $conn->query("SELECT id, name FROM categories");
                while ($row = $result->fetch_assoc()) {
                  echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
              </select>
            </div>
            <div class="form-group mb-3">
              <label for="editProductStock">Total Stock</label>
              <input type="number" class="form-control" id="editProductStock" name="editProductStock" required min="0">
              <small class="form-text text-muted">Enter total stock manually. If variants are added, stock will be updated based on variant stock values.</small>
            </div>
            <div class="form-group mb-3">
              <label for="editProductBuyingPrice">Buying Price</label>
              <input type="number" class="form-control" id="editProductBuyingPrice" name="editProductBuyingPrice" step="0.01" required min="0">
            </div>
            <div class="form-group mb-3">
              <label for="editProductSellingPrice">Selling Price</label>
              <input type="number" class="form-control" id="editProductSellingPrice" name="editProductSellingPrice" step="0.01" required min="0">
            </div>
            <div class="form-group mb-3">
              <label for="editProductDescription">Description</label>
              <textarea class="form-control" id="editProductDescription" name="editProductDescription" rows="4"></textarea>
            </div>
            <div class="form-group mb-3">
              <label for="editProductImages">Product Images</label>
              <input type="file" class="form-control" id="editProductImages" name="productImages[]" multiple accept="image/*">
              <small class="form-text text-muted">Select new images to upload. Existing images will be kept unless new ones are uploaded.</small>
              <div id="existingImages" class="mt-2"></div>
            </div>
            <div class="form-group mb-3">
              <label>Variants (Optional)</label>
              <div id="editVariantContainer"></div>
              <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addVariant('editVariantContainer', 'editProductStock')">Add Variant</button>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" onclick="updateProduct()">Update Product</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Order Details Modal -->
  <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="orderDetailsContent"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let salesChart;
    let currentOrdersPage = 1;
    let currentProductsPage = 1;

    function toggleSidebar() {
      const sidebar = document.querySelector('.sidebar');
      sidebar.classList.toggle('active');
    }

    function showSection(sectionId) {
      document.querySelectorAll('.section').forEach(section => section.style.display = 'none');
      document.getElementById(sectionId).style.display = 'block';
      document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
      document.querySelector(`.sidebar .nav-link[href="#${sectionId}"]`).classList.add('active');
      if (window.innerWidth <= 768) {
        document.querySelector('.sidebar').classList.remove('active');
      }
      if (sectionId === 'orders') {
        loadOrders(currentOrdersPage);
      } else if (sectionId === 'products') {
        loadProducts(currentProductsPage);
      }
    }

    async function loadOrders(page) {
      currentOrdersPage = page;
      try {
        const response = await fetch(`?ajax=orders&page=${page}`);
        const data = await response.json();
        const tbody = document.getElementById('ordersTableBody');
        tbody.innerHTML = '';
        data.orders.forEach((row, index) => {
          const counter = (data.page - 1) * data.items_per_page + index + 1;
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${counter}</td>
            <td>${row.username}</td>
            <td>Ksh ${Number(row.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
            <td>
              <select onchange='updateOrderStatus(${row.id}, this.value)'>
                <option value='pending' ${row.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value='confirmed' ${row.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                <option value='shipped' ${row.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                <option value='delivered' ${row.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                <option value='cancelled' ${row.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
              </select>
            </td>
            <td>${row.created_at}</td>
            <td>
              <button class='btn btn-primary btn-sm' onclick='viewOrderDetails(${row.id})'>View Details</button>
              <button class='btn btn-danger btn-sm' onclick='deleteOrder(${row.id})'>Delete</button>
            </td>
          `;
          tbody.appendChild(tr);
        });
        buildPagination('ordersPagination', data.page, Math.ceil(data.total / data.items_per_page), loadOrders);
      } catch (error) {
        alert('Error loading orders: ' + error.message);
      }
    }

    async function loadProducts(page) {
      currentProductsPage = page;
      try {
        const response = await fetch(`?ajax=products&page=${page}`);
        const data = await response.json();
        const tbody = document.getElementById('productsTableBody');
        tbody.innerHTML = '';
        data.products.forEach((row, index) => {
          const counter = (data.page - 1) * data.items_per_page + index + 1;
          const images = row.images ? row.images.split(',').map(img => `<img src="products/${img.trim()}" alt="Product Image" class="product-image">`).join('') : '';
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${counter}</td>
            <td>${row.name}</td>
            <td>${row.category}</td>
            <td>${images}</td>
            <td>${row.stock}</td>
            <td>Ksh ${Number(row.selling_price).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
            <td>
              <button class='btn btn-warning btn-sm' onclick='editProduct(${row.id})'>Edit</button>
              <button class='btn btn-danger btn-sm' onclick='deleteProduct(${row.id})'>Delete</button>
            </td>
          `;
          tbody.appendChild(tr);
        });
        buildPagination('productsPagination', data.page, Math.ceil(data.total / data.items_per_page), loadProducts);
      } catch (error) {
        alert('Error loading products: ' + error.message);
      }
    }

    function buildPagination(containerId, currentPage, totalPages, loadFunction) {
      const container = document.getElementById(containerId);
      container.innerHTML = '';
      if (totalPages <= 1) return;

      const prevLi = document.createElement('li');
      prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
      prevLi.innerHTML = `<a class="page-link" href="#" onclick="${currentPage > 1 ? `${loadFunction.name}(${currentPage - 1})` : 'return false;'}" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
      container.appendChild(prevLi);

      for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="${loadFunction.name}(${i}); return false;">${i}</a>`;
        container.appendChild(li);
      }

      const nextLi = document.createElement('li');
      nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
      nextLi.innerHTML = `<a class="page-link" href="#" onclick="${currentPage < totalPages ? `${loadFunction.name}(${currentPage + 1})` : 'return false;'}" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
      container.appendChild(nextLi);
    }

    function updateTotalStock(containerId, stockFieldId) {
      const container = document.getElementById(containerId);
      const stockInputs = container.querySelectorAll('.variant-stock');
      let totalStock = 0;
      stockInputs.forEach(input => {
        const value = parseInt(input.value) || 0;
        totalStock += value;
      });
      if (stockInputs.length > 0) {
        document.getElementById(stockFieldId).value = totalStock;
      }
    }

    function addVariant(containerId, stockFieldId) {
      const container = document.getElementById(containerId);
      const variantEntry = document.createElement('div');
      variantEntry.className = 'variant-entry';
      variantEntry.innerHTML = `
        <div class="row">
          <div class="col-md-3"><input type="text" class="form-control" placeholder="Color" name="variantColor[]"></div>
          <div class="col-md-3"><input type="text" class="form-control" placeholder="Size" name="variantSize[]"></div>
          <div class="col-md-3"><input type="number" class="form-control" placeholder="Additional Price" name="variantAdditionalPrice[]" step="0.01" min="0"></div>
          <div class="col-md-2"><input type="number" class="form-control variant-stock" placeholder="Stock" name="variantStock[]" min="0" oninput="updateTotalStock('${containerId}', '${stockFieldId}')"></div>
          <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-variant-btn" onclick="removeVariant(this, '${containerId}', '${stockFieldId}')">X</button></div>
        </div>`;
      container.appendChild(variantEntry);
      updateTotalStock(containerId, stockFieldId);
    }

    function removeVariant(button, containerId, stockFieldId) {
      button.closest('.variant-entry').remove();
      updateTotalStock(containerId, stockFieldId);
    }

    async function updateOrderStatus(orderId, status) {
      try {
        const response = await fetch('orders/update_order_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ order_id: orderId, status: status })
        });
        const data = await response.json();
        alert(data.success ? 'Order status updated successfully' : 'Failed to update order status: ' + data.message);
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    async function deleteOrder(orderId) {
      if (confirm('Are you sure you want to delete this order?')) {
        try {
          const response = await fetch('orders/delete_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
          });
          const result = await response.json();
          if (result.success) {
            // Check if current page has items left, if not go to previous page
            const total_result = await fetch('?ajax=orders&page=1');
            const total_data = await total_result.json();
            if ((currentOrdersPage - 1) * 10 >= total_data.total) {
              currentOrdersPage = Math.max(1, Math.ceil(total_data.total / 10));
            }
            loadOrders(currentOrdersPage);
            alert('Order deleted successfully');
          } else {
            alert('Failed to delete order: ' + result.message);
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    }
    

    async function viewOrderDetails(orderId) {
      try {
        const response = await fetch('orders/get_order_details.php?order_id=' + orderId);
        const data = await response.json();
        if (data.success) {
          let html = `
            <p><strong>Order ID:</strong> ${data.order.id}</p>
            <p><strong>User:</strong> ${data.user.username}</p>
            <p><strong>Total Amount:</strong> Ksh ${parseFloat(data.order.total_amount).toFixed(2)}</p>
            <p><strong>Status:</strong> ${data.order.status}</p>
            <p><strong>Created At:</strong> ${data.order.created_at}</p>
            <h5>Items:</h5>
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr><th>Product</th><th>Color</th><th>Size</th><th>Quantity</th><th>Price</th></tr>
                </thead>
                <tbody>`;
          data.items.forEach(item => {
            html += `
              <tr>
                <td>${item.name}</td>
                <td>${item.color || 'N/A'}</td>
                <td>${item.size || 'N/A'}</td>
                <td>${item.quantity}</td>
                <td>Ksh ${parseFloat(item.price).toFixed(2)}</td>
              </tr>`;
          });
          html += `</tbody></table></div>`;
          document.getElementById('orderDetailsContent').innerHTML = html;
          new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        } else {
          alert('Failed to load order details: ' + data.message);
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    async function addProduct() {
      const form = document.getElementById('addProductForm');
      const productName = document.getElementById('productName').value;
      const productCategory = document.getElementById('productCategory').value;
      const productStock = document.getElementById('productStock').value;
      const productBuyingPrice = document.getElementById('productBuyingPrice').value;
      const productSellingPrice = document.getElementById('productSellingPrice').value;

      if (!productName || !productCategory || !productStock || !productBuyingPrice || !productSellingPrice) {
        alert('Please fill in all required fields (Name, Category, Stock, Buying Price, Selling Price).');
        return;
      }

      const formData = new FormData(form);
      const variants = [];
      const colors = form.querySelectorAll('input[name="variantColor[]"]');
      const sizes = form.querySelectorAll('input[name="variantSize[]"]');
      const additionalPrices = form.querySelectorAll('input[name="variantAdditionalPrice[]"]');
      const stocks = form.querySelectorAll('input[name="variantStock[]"]');
      for (let i = 0; i < colors.length; i++) {
        if (colors[i].value || sizes[i].value || additionalPrices[i].value || stocks[i].value) {
          variants.push({
            color: colors[i].value || null,
            size: sizes[i].value || null,
            additional_price: parseFloat(additionalPrices[i].value) || 0,
            stock: parseInt(stocks[i].value) || 0
          });
        }
      }
      formData.append('variants', JSON.stringify(variants));
      try {
        const response = await fetch('products/add_product.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.success) {
          bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
          form.reset();
          document.getElementById('addVariantContainer').innerHTML = '';
          loadProducts(1); // Reload to page 1 after adding
          alert('Product added successfully');
        } else {
          alert('Failed to add product: ' + result.message);
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    async function editProduct(productId) {
      try {
        const response = await fetch('products/get_product.php?product_id=' + productId);
        const data = await response.json();
        if (data.success) {
          document.getElementById('editProductId').value = data.product.id;
          document.getElementById('editProductName').value = data.product.name;
          document.getElementById('editProductCategory').value = data.product.category_id;
          document.getElementById('editProductStock').value = data.product.stock;
          document.getElementById('editProductBuyingPrice').value = data.product.buying_price;
          document.getElementById('editProductSellingPrice').value = data.product.selling_price;
          document.getElementById('editProductDescription').value = data.product.description;
          const existingImages = document.getElementById('existingImages');
          existingImages.innerHTML = data.product.images ? data.product.images.split(',').map(img => `
            <div>
              <img src="${img}" alt="Product Image" style="width: 100px; height: auto; margin-right: 10px;">
              <button type="button" class="btn btn-danger btn-sm" onclick="removeExistingImage('${img}', ${data.product.id})">Remove</button>
            </div>`).join('') : '';
          const container = document.getElementById('editVariantContainer');
          container.innerHTML = '';
          data.variants.forEach(variant => {
            const variantEntry = document.createElement('div');
            variantEntry.className = 'variant-entry';
            variantEntry.innerHTML = `
              <div class="row">
                <div class="col-md-3"><input type="text" class="form-control" placeholder="Color" name="variantColor[]" value="${variant.color || ''}"></div>
                <div class="col-md-3"><input type="text" class="form-control" placeholder="Size" name="variantSize[]" value="${variant.size || ''}"></div>
                <div class="col-md-3"><input type="number" class="form-control" placeholder="Additional Price" name="variantAdditionalPrice[]" step="0.01" min="0" value="${variant.additional_price || 0}"></div>
                <div class="col-md-2"><input type="number" class="form-control variant-stock" placeholder="Stock" name="variantStock[]" min="0" value="${variant.stock || 0}" oninput="updateTotalStock('editVariantContainer', 'editProductStock')"></div>
                <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-variant-btn" onclick="removeVariant(this, 'editVariantContainer', 'editProductStock')">X</button></div>
              </div>`;
            container.appendChild(variantEntry);
          });
          updateTotalStock('editVariantContainer', 'editProductStock');
          new bootstrap.Modal(document.getElementById('editProductModal')).show();
        } else {
          alert('Failed to load product: ' + data.message);
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    async function removeExistingImage(imagePath, productId) {
      if (confirm('Are you sure you want to remove this image?')) {
        try {
          const response = await fetch('products/remove_product_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, image_path: imagePath })
          });
          const result = await response.json();
          if (result.success) {
            document.getElementById('existingImages').innerHTML = result.images ? result.images.split(',').map(img => `
              <div>
                <img src="${img}" alt="Product Image" style="width: 100px; height: auto; margin-right: 10px;">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeExistingImage('${img}', ${productId})">Remove</button>
              </div>`).join('') : '';
            alert('Image removed successfully');
          } else {
            alert('Failed to remove image: ' + result.message);
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    }

    async function updateProduct() {
      const form = document.getElementById('editProductForm');
      const editProductId = document.getElementById('editProductId').value;
      const editProductName = document.getElementById('editProductName').value;
      const editProductCategory = document.getElementById('editProductCategory').value;
      const editProductStock = document.getElementById('editProductStock').value;
      const editProductBuyingPrice = document.getElementById('editProductBuyingPrice').value;
      const editProductSellingPrice = document.getElementById('editProductSellingPrice').value;

      if (!editProductId || !editProductName || !editProductCategory || !editProductStock || !editProductBuyingPrice || !editProductSellingPrice) {
        alert('Please fill in all required fields (ID, Name, Category, Stock, Buying Price, Selling Price).');
        return;
      }

      const formData = new FormData(form);
      const variants = [];
      const colors = form.querySelectorAll('input[name="variantColor[]"]');
      const sizes = form.querySelectorAll('input[name="variantSize[]"]');
      const additionalPrices = form.querySelectorAll('input[name="variantAdditionalPrice[]"]');
      const stocks = form.querySelectorAll('input[name="variantStock[]"]');
      for (let i = 0; i < colors.length; i++) {
        if (colors[i].value || sizes[i].value || additionalPrices[i].value || stocks[i].value) {
          variants.push({
            color: colors[i].value || null,
            size: sizes[i].value || null,
            additional_price: parseFloat(additionalPrices[i].value) || 0,
            stock: parseInt(stocks[i].value) || 0
          });
        }
      }
      formData.append('variants', JSON.stringify(variants));
      try {
        const response = await fetch('products/update_product.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.success) {
          bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
          loadProducts(currentProductsPage);
          alert('Product updated successfully');
        } else {
          alert('Failed to update product: ' + result.message);
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    async function deleteProduct(productId) {
      if (confirm('Are you sure you want to delete this product?')) {
        try {
          const response = await fetch('products/delete_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
          });
          const result = await response.json();
          if (result.success) {
            // Check if current page has items left
            const total_result = await fetch('?ajax=products&page=1');
            const total_data = await total_result.json();
            if ((currentProductsPage - 1) * 10 >= total_data.total) {
              currentProductsPage = Math.max(1, Math.ceil(total_data.total / 10));
            }
            loadProducts(currentProductsPage);
            alert('Product deleted successfully');
          } else {
            alert('Failed to delete product: ' + result.message);
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    }

    async function fetchTotalSales(filterType, filterValue) {
      try {
        const response = await fetch(`get_sales.php?filter_type=${filterType}&filter_value=${filterValue}`);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const data = await response.json();
        if (data.success) {
          const totalSales = parseFloat(data.total_sales) || 0;
          document.getElementById('totalSalesAmount').textContent = `Ksh ${totalSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        } else {
          alert('Failed to load sales data: ' + data.message);
          document.getElementById('totalSalesAmount').textContent = 'Ksh 0.00';
        }
      } catch (error) {
        alert('Error fetching sales data: ' + error.message);
        document.getElementById('totalSalesAmount').textContent = 'Ksh 0.00';
      }
    }

    async function downloadTotalSalesReport() {
      const filterType = document.getElementById('salesFilterType').value;
      const filterValue = document.getElementById('salesFilterValue').value;
      if (!filterValue) {
        alert('Please select a date to download the report.');
        return;
      }

      try {
        const response = await fetch(`get_sales.php?filter_type=${filterType}&filter_value=${filterValue}`);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const data = await response.json();
        console.log(data); // Debug: Log the response to check items array
        if (data.success) {
          const totalSales = parseFloat(data.total_sales) || 0;
          const items = data.items || [];
          const csvContent = [
            ['Filter Type', 'Date', 'Product Name', 'Quantity', 'Total Amount (Ksh)'],
            ...items.map(item => [
              filterType.charAt(0).toUpperCase() + filterType.slice(1),
              filterValue,
              `"${(item.name || 'Unknown Product').replace(/"/g, '""')}"`, // Escape quotes and handle null/undefined
              item.quantity,
              item.item_total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            ]),
            ['', '', 'Total Sales', '', totalSales.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })]
          ].map(row => row.join(',')).join('\n');

          const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.setAttribute('href', url);
          link.setAttribute('download', `sales_report_${filterType}_${filterValue}.csv`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          URL.revokeObjectURL(url);
        } else {
          alert('Failed to generate report: ' + data.message);
        }
      } catch (error) {
        alert('Error generating report: ' + error.message);
      }
    }

    document.getElementById('totalSalesFilterForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const filterType = document.getElementById('salesFilterType').value;
      const filterValue = document.getElementById('salesFilterValue').value;
      if (!filterValue) {
        alert('Please select a date.');
        return;
      }
      await fetchTotalSales(filterType, filterValue);
    });

    document.getElementById('salesFilterForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;

      const canvas = document.getElementById('salesChart');
      if (!canvas) {
        alert('Error: Sales chart canvas not found');
        return;
      }

      try {
        const response = await fetch(`reports/get_sales_data.php?start_date=${startDate}&end_date=${endDate}`);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (data.success) {
          const tableBody = document.getElementById('salesTableBody');
          tableBody.innerHTML = '';
          let grandTotal = 0;
          let grandCount = 0;

          data.sales.forEach(sale => {
            grandTotal += parseFloat(sale.total_amount);
            grandCount += parseInt(sale.order_count);
            tableBody.innerHTML += `
              <tr>
                <td>${sale.date}</td>
                <td>Ksh ${parseFloat(sale.total_amount).toLocaleString()}</td>
                <td>${sale.order_count}</td>
              </tr>`;
          });

          tableBody.innerHTML += `
            <tr style="font-weight:bold; background:#f0f0f0;">
              <td>Grand Total</td>
              <td>Ksh ${grandTotal.toLocaleString()}</td>
              <td>${grandCount}</td>
            </tr>`;

          if (salesChart) salesChart.destroy();
          const ctx = canvas.getContext('2d');
          salesChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: data.sales.map(s => s.date),
              datasets: [{
                label: 'Total Sales (Ksh)',
                data: data.sales.map(s => parseFloat(s.total_amount)),
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                fill: true,
                tension: 0.3
              }]
            },
            options: {
              responsive: true,
              plugins: {
                legend: { display: true }
              },
              scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Sales (Ksh)' } },
                x: { title: { display: true, text: 'Date' } }
              }
            }
          });
        } else {
          alert('Failed to load sales data: ' + data.message);
        }
      } catch (error) {
        alert('Error fetching sales data: ' + error.message);
      }
    });

    // Initial load
    showSection('dashboard');
  </script>
</body>
</html>