/* ---------- GLOBALS ---------- */
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
// console.log('Adding to cart');
const color = document.getElementById('modal-product-color').value || null;
const size = document.getElementById('modal-product-size').value || null;
const quantity = parseInt(document.getElementById('modal-quantity').value);
// console.log('Selected values for addToCart:', { product_id: selectedProduct.id, color, size, quantity });
if (quantity < 1 || quantity > selectedProduct.stock) {
showToast('Invalid quantity', false);
return;
}
const variantBody = `product_id=${selectedProduct.id}&color=${encodeURIComponent(color || 'None')}&size=${encodeURIComponent(size || 'None')}`;
// console.log('get_product_variants.php request body:', variantBody);
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
    // console.log('Variant response:', data);
    let variantId = null;
    if (data.success) {
    variantId = data.variant_id;
    } else {
    // console.log('Variant not found, proceeding with provided color and size');
    }
    const cartBody = `product_id=${selectedProduct.id}${variantId ? `&variant_id=${variantId}` : ''}&quantity=${quantity}${color ? `&color=${encodeURIComponent(color)}` : ''}${size ? `&size=${encodeURIComponent(size)}` : ''}`;
    // console.log('add_to_cart.php request body:', cartBody);
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
        // console.log('Add to cart response:', data);
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
    // console.log('add_to_cart.php fallback request body:', cartBody);
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
        // console.log('Add to cart response (fallback):', data);
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
// console.log('Opening cart modal');
fetch('php/check_auth.php')
.then(response => {
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
})
.then(data => {
    // console.log('Auth check for cart:', data);
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
// console.log('Fetching cart');
fetch('products/get_cart.php')
.then(response => {
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
})
.then(data => {
    // console.log('Cart response:', data);
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
    // console.log(`Cart item ${index + 1}:`, JSON.stringify(item, null, 2));
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
        
        <div class="quantity-control" style="display: inline-flex; align-items: center; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; margin-top: 8px;">
        <button 
            id="cartMinus-${item.cart_id}" 
            onclick="adjustCartQuantity(${item.cart_id}, -1)"
            style="padding: 6px 6px; background: #015e28ff; border: none; cursor: pointer; font-size: 14px;">
            <i class="fas fa-minus"></i>
        </button>
        
        <input 
            type="number" 
            value="${item.quantity}" 
            id="cart-quantity-${item.cart_id}" 
            min="1" 
            max="${item.stock}" 
            readonly 
            style="width: 30px; text-align: center; border: none; padding: 10px 0; font-size: 16px; background: white;">
        
        <button 
            id="cartPlus-${item.cart_id}" 
            onclick="adjustCartQuantity(${item.cart_id}, 1)"
            style="padding: 6px 6px; background: #015e28ff; border: none; cursor: pointer; font-size: 14px;">
            <i class="fas fa-plus"></i>
        </button>
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
// console.log('Adjusting cart quantity for cartId:', cartId, 'change:', change);
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
    // console.log('Update cart quantity response:', data);
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
// console.log('Removing from cart, cartId:', cartId);
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
    // console.log('Remove from cart response:', data);
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
// console.log('Placing order');
const placeOrderBtn = document.querySelector('#cartModal .btn-primary');
placeOrderBtn.disabled = true;
placeOrderBtn.textContent = 'Processing...';

fetch('products/get_cart.php')
.then(response => {
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
})
.then(data => {
    // console.log('Cart for order:', data);
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
        // console.log('Place order response:', data);
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
    fetchProducts();
}

// Category Filter
document.querySelectorAll('#category-filter button').forEach(btn => {
    btn.addEventListener('click', () => {
    document.querySelectorAll('#category-filter button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentCategory = btn.dataset.category;
    fetchProducts();
    });
});

// Track Orders

function openTrackOrdersModal() {
// console.log('Opening track orders modal');
fetch('products/get_orders.php')
.then(response => response.json())
.then(data => {
    // console.log('Orders response:', data);
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
function closeAllPopups() {
document.querySelectorAll('.popup').forEach(p => p.style.display = 'none');
}

/* ---------- PLACE ORDER ---------- */
function placeOrder() {
const placeOrderBtn = document.querySelector('#cartModal .btn-primary');
if (!placeOrderBtn) return;

placeOrderBtn.disabled = true;
placeOrderBtn.textContent = 'Processing...';

fetch('products/get_cart.php')
.then(r => r.ok ? r.json() : Promise.reject(new Error('Failed to fetch cart')))
.then(data => {
    if (!data.success || !data.cart.length) {
    showToast('Cart is empty', false);
    placeOrderBtn.disabled = false;
    placeOrderBtn.textContent = 'Place Order';
    return;
    }

    const orderData = {
    items: data.cart.map(item => ({
        cart_id: item.cart_id,
        product_id: item.product_id,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
        price: item.item_total / item.quantity,
        color: item.color || 'N/A',
        size: item.size || 'N/A'
    }))
    };

    return fetch('products/place_order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(orderData)
    });
})
.then(r => r?.ok ? r.json() : Promise.reject(new Error('Failed to place order')))
.then(data => {
    if (data.success) {
    showToast('Order placed successfully!', true);
    bootstrap.Modal.getInstance(document.getElementById('cartModal'))?.hide();
    fetchCart(); // refresh cart
    } else {
    showToast(data.message || 'Failed to place order', false);
    }
})
.catch(err => {
    console.error('Place order error:', err);
    showToast('Error placing order: ' + err.message, false);
})
.finally(() => {
    placeOrderBtn.disabled = false;
    placeOrderBtn.textContent = 'Place Order';
});
}

/* ---------- SEND CONTACT MESSAGE ---------- */
function sendContactMessage(formId = 'contact-form') {
const form = document.getElementById(formId);
if (!form) return;

form.addEventListener('submit', function(e) {
e.preventDefault();

const email = form.querySelector('#contact-email').value.trim();
const message = form.querySelector('#contact-message').value.trim();
const btn = form.querySelector('button[type="submit"]');

if (!email || !message) {
    showToast('All fields are required', false);
    return;
}

btn.disabled = true;
btn.textContent = 'Sending...';

fetch('php/send_contact_message.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `email=${encodeURIComponent(email)}&message=${encodeURIComponent(message)}`
})
.then(r => r.ok ? r.json() : Promise.reject(new Error('Failed to send message')))
.then(data => {
    if (data.success) {
    showToast('Message sent successfully!', true);
    form.reset();
    closeAllPopups();
    } else {
    showToast(data.message || 'Failed to send message', false);
    }
})
.catch(err => {
    console.error('Send message error:', err);
    showToast('Error sending message: ' + err.message, false);
})
.finally(() => {
    btn.disabled = false;
    btn.textContent = 'Send Message';
});
});
}

// Initialize contact form listener
document.addEventListener('DOMContentLoaded', () => {
sendContactMessage(); // attach submit event
});