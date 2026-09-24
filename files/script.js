
const itemsPerPage = 6;
let selectedProduct = null;

window.onload = () => {
  console.log('Page loaded, fetching products');
  fetchProducts();
};

function showToast(message, isSuccess = true) {
  const toastEl = document.getElementById('appToast');
  const toastBody = toastEl.querySelector('.toast-body');
  toastBody.textContent = message;
  toastEl.classList.remove('bg-success', 'bg-danger');
  toastEl.classList.add(isSuccess ? 'bg-success' : 'bg-danger');
  const toast = new bootstrap.Toast(toastEl);
  toast.show();
}

function togglePassword(inputId, toggleId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(toggleId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

function openContactPopup() {
  console.log('Opening contact popup');
  closeAllPopups();
  document.getElementById('contact-popup').style.display = 'flex';
}

// ====================== HELP POPUP ======================
function openHelpPopup() {
  console.log('Opening help popup');
  
  closeAllPopups();   // Close any other open popups

  const popup = document.getElementById('help-popup');
  
  if (popup) {
    popup.style.display = 'flex';
    console.log('Help popup opened successfully');
  } else {
    console.error('Help popup element not found in the DOM!');
    showToast('Help popup could not be found', false);
  }
}

function openAboutUsPopup() {
  console.log('Attempting to open About Us popup');
  const popup = document.getElementById('about-us-popup');
  if (!popup) {
    console.error('About Us popup element not found');
    showToast('About Us popup not found', false);
    return;
  }
  closeAllPopups();
  popup.style.display = 'flex';
  console.log('About Us popup display set to flex');
}


function openLoginPopup() {
  console.log('Opening login popup');
  closeAllPopups();
  document.getElementById('login-popup').style.display = 'flex';
}

function openSignupPopup() {
  console.log('Opening signup popup');
  closeAllPopups();
  document.getElementById('signup-popup').style.display = 'flex';
}

function openForgotPasswordPopup() {
  console.log('Opening forgot password popup');
  closeAllPopups();
  document.getElementById('forgot-password-popup').style.display = 'flex';
}


function closePopup() {
  console.log('Closing all popups');
  closeAllPopups();
}

function closeAllPopups() {
  document.querySelectorAll('.popup').forEach(popup => popup.style.display = 'none');
}

function closePopupOnClick(event) {
  if (event.target.classList.contains('popup')) {
    closePopup();
  }
}

function closeThankYouPopup() {
  console.log('Closing thank you popup');
  document.getElementById('thank-you-popup').style.display = 'none';
}

function fetchProducts(searchTerm = '') {
  console.log('Fetching products, page:', currentPage, 'search:', searchTerm);
  const productSection = document.getElementById('product-section');
  productSection.innerHTML = '<p class="loading-message">Loading products...</p>';

  const isLargeScreen = window.innerWidth >= 992;
  const url = isLargeScreen 
    ? `products/get_products.php?search=${encodeURIComponent(searchTerm)}&all=true` 
    : `products/get_products.php?page=${currentPage}&search=${encodeURIComponent(searchTerm)}`;

  fetch(url)
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Received products:', data);
      if (data.success) {
        displayProducts(data.products);
        if (!isLargeScreen) {
          updatePagination(data.totalPages || 1);
        } else {
          document.getElementById('pagination').innerHTML = '';
        }
      } else {
        productSection.innerHTML = `<p class="error-message">${data.message || 'No products available'}</p>`;
      }
    })
    .catch(error => {
      console.error('Error fetching products:', error);
      productSection.innerHTML = '<p class="error-message">Failed to load products. Please try again later.</p>';
    });
}

function displayProducts(products) {
  const productSection = document.getElementById('product-section');
  productSection.innerHTML = '';
  if (!Array.isArray(products) || products.length === 0) {
    productSection.innerHTML = '<p class="text-center text-danger">No products found.</p>';
    return;
  }

  products.forEach(product => {
    const images = Array.isArray(product.images) && product.images.length > 0 ? product.images : ['default.jpg'];
    const carouselId = `productCarousel${product.id}`;
    let carouselItems = images.map((image, index) => `
      <div class="carousel-item ${index === 0 ? 'active' : ''}">
        <img src="admin/products/${image}" class="d-block w-100" alt="${product.name}"
             onerror="this.src='admin/products/images/default.jpg';">
      </div>
    `).join('');

    const productCard = document.createElement('div');
    productCard.classList.add('product-card');
    productCard.setAttribute('data-product-id', product.id);
    productCard.innerHTML = `
      <a href="php/items.php?id=${product.id}" class="product-link"> <!-- Updated path to php/items.php -->
        <div id="${carouselId}" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
          <div class="carousel-inner">
            ${carouselItems}
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
        <h3>${product.name}</h3>
      </a>
      <p>Ksh ${parseFloat(product.selling_price).toFixed(2)}</p>
      <p>Stock: ${product.stock}</p>
      <div class="quantity-control">
        <button onclick="adjustProductQuantity(${product.id}, -1, event)"><i class="fas fa-minus"></i></button>
        <input type="number" id="quantity-${product.id}" value="1" min="1" max="${product.stock}" readonly>
        <button onclick="adjustProductQuantity(${product.id}, 1, event)"><i class="fas fa-plus"></i></button>
      </div>
      <button class="btn btn-primary" onclick="openProductModal(${product.id}, event)"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
    `;
    productSection.appendChild(productCard);
  });

  document.querySelectorAll('.carousel').forEach(carousel => {
    new bootstrap.Carousel(carousel, {
      interval: 3000,
      wrap: true
    });
  });
}

function changePage(direction) {
  if (window.innerWidth >= 992) return;
  currentPage += direction;
  if (currentPage < 1) currentPage = 1;
  fetchProducts(document.getElementById('searchInput').value);
}

function goToPage(page) {
  if (window.innerWidth >= 992) return;
  currentPage = page;
  fetchProducts(document.getElementById('searchInput').value);
}

function searchItems() {
  console.log('Searching items');
  currentPage = 1;
  fetchProducts(document.getElementById('searchInput').value);
}

function adjustProductQuantity(productId, change, event) {
  console.log('Adjusting quantity for product:', productId, 'change:', change);
  event.stopPropagation();
  const input = document.getElementById(`quantity-${productId}`);
  let quantity = parseInt(input.value) + change;
  const max = parseInt(input.getAttribute('max'));
  if (quantity < 1) quantity = 1;
  if (quantity > max) quantity = max;
  input.value = quantity;
}

function openProductModal(productId, event) {
  if (event) event.stopPropagation();
  console.log('Opening product modal for product:', productId);
  fetch('php/check_auth.php')
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Auth check response:', data);
      if (data.success && data.is_authenticated) {
        fetch(`products/get_product_details.php?id=${productId}`)
          .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
          })
          .then(data => {
            console.log('Product details for modal:', data);
            if (data.success) {
              selectedProduct = data.product;
              document.getElementById('modal-product-name').value = selectedProduct.name;
              const colorSelect = document.getElementById('modal-product-color');
              const sizeSelect = document.getElementById('modal-product-size');
              colorSelect.innerHTML = '<option value="">Select Color (Optional)</option>';
              sizeSelect.innerHTML = '<option value="">Select Size (Optional)</option>';
              (selectedProduct.colors || []).forEach(color => {
                const option = document.createElement('option');
                option.value = color;
                option.textContent = color;
                colorSelect.appendChild(option);
              });
              (selectedProduct.sizes || []).forEach(size => {
                const option = document.createElement('option');
                option.value = size;
                option.textContent = size;
                sizeSelect.appendChild(option);
              });
              document.getElementById('modal-quantity').value = document.getElementById(`quantity-${productId}`).value;
              document.getElementById('modal-quantity').setAttribute('max', selectedProduct.stock);
              new bootstrap.Modal(document.getElementById('productModal')).show();
            } else {
              showToast(data.message || 'Failed to load product details', false);
            }
          })
          .catch(error => {
            console.error('Error fetching product details:', error);
            showToast('Failed to fetch product details', false);
          });
      } else {
        console.log('User not authenticated, opening login popup');
        showToast('Please login to add to cart', false);
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error checking authentication:', error);
      showToast('Failed to check authentication', false);
      openLoginPopup();
    });
}

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
            document.getElementById('thank-you-popup').style.display = 'block';
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

document.getElementById('signupForm').addEventListener('submit', function(e) {
  e.preventDefault();
  console.log('Signup form submitted');
  const name = document.getElementById('signup-name').value;
  const email = document.getElementById('signup-email').value;
  const phone = document.getElementById('signup-phone').value;
  const password = document.getElementById('signup-password').value;
  const confirmPassword = document.getElementById('signup-confirm-password').value;
  const submitBtn = this.querySelector('input[type="submit"]');
  const originalBtnText = submitBtn.value;

  if (password !== confirmPassword) {
    showToast('Passwords do not match', false);
    return;
  }

  submitBtn.disabled = true;
  submitBtn.value = 'Sending...';

  fetch('php/signup.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&phone=${encodeURIComponent(phone)}&password=${encodeURIComponent(password)}`
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Signup response:', data);
      submitBtn.disabled = false;
      submitBtn.value = originalBtnText;
      showToast(data.message, data.success);
      if (data.success) {
        closePopup();
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error signing up:', error);
      submitBtn.disabled = false;
      submitBtn.value = originalBtnText;
      showToast('Failed to sign up', false);
    });
});

document.getElementById('contactForm').addEventListener('submit', function(e) {
  e.preventDefault();
  console.log('Contact form submitted');
  const email = document.getElementById('contact-email').value;
  const message = document.getElementById('contact-message').value;
  const submitBtn = this.querySelector('input[type="submit"]');
  const originalBtnText = submitBtn.value;

  submitBtn.disabled = true;
  submitBtn.value = 'Sending...';

  fetch('php/send_contact_message.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `email=${encodeURIComponent(email)}&message=${encodeURIComponent(message)}`
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Contact form response:', data);
      submitBtn.disabled = false;
      submitBtn.value = originalBtnText;
      closeAllPopups();
      if (data.success) {
        document.getElementById('contact-email').value = '<?php echo htmlspecialchars($userEmail); ?>';
        document.getElementById('contact-message').value = '';
        document.getElementById('message-sent-popup').style.display = 'flex';
      } else {
        document.getElementById('message-failed-popup').style.display = 'flex';
      }
    })
    .catch(error => {
      console.error('Error sending message:', error);
      submitBtn.disabled = false;
      submitBtn.value = originalBtnText;
      closeAllPopups();
      document.getElementById('message-failed-popup').style.display = 'flex';
    });
});
document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
  e.preventDefault();
  console.log('Forgot password form submitted');

  const email = document.getElementById('forgot-email').value.trim();
  const submitBtn = this.querySelector('button[type="submit"]');
  
  if (!email) {
    showToast('Please enter your email', false);
    return;
  }

  const originalBtnText = submitBtn.textContent;   // ← Changed

  submitBtn.disabled = true;
  submitBtn.textContent = 'Sending...';            // ← Changed

  fetch('php/send_reset_otp.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `email=${encodeURIComponent(email)}`
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Forgot password response:', data);
      
      submitBtn.disabled = false;
      submitBtn.textContent = originalBtnText;     // ← Changed

      showToast(data.message, data.success);
      if (data.success) {
        closePopup();
      }
    })
    .catch(error => {
      console.error('Error sending reset email:', error);
      
      submitBtn.disabled = false;
      submitBtn.textContent = originalBtnText;     // ← Changed
      
      showToast('Failed to send reset email. Please try again.', false);
    });
});
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
  e.preventDefault();
  console.log('Reset password form submitted');
  const email = document.getElementById('reset-email').value;
  const newPassword = document.getElementById('reset-password').value;
  const confirmPassword = document.getElementById('reset-confirm-password').value;
  const submitBtn = this.querySelector('input[type="submit"]');
  const originalBtnText = submitBtn.value;

  if (newPassword !== confirmPassword) {
    showToast('Passwords do not match', false);
    return;
  }

  submitBtn.disabled = true;
  submitBtn.value = 'Resetting...';

  fetch('php/reset_password.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `email=${encodeURIComponent(email)}&new_password=${encodeURIComponent(newPassword)}`
  })
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      console.log('Reset password response:', data);
      submitBtn.disabled = false;
      submitBtn.value = originalBtnText;
      showToast(data.message, data.success);
      if (data.success) {
        closePopup();
        openLoginPopup();
      }
    })
    .catch(error => {
      console.error('Error resetting password:', error);
      submitBtn.disabled = false;
      submitBtn.value = originalBtnText;
      showToast('Failed to reset password', false);
    });
});

function openProductModal(productId) {
    console.log('Opening product modal for ID:', productId);
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

function openProductModalFromDetail() {
    console.log('Opening product modal from detail');
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

function adjustDetailQuantity(change) {
    const quantityInput = document.getElementById('detail-quantity');
    let quantity = parseInt(quantityInput.value) + change;
    if (quantity < 1) quantity = 1;
    quantityInput.value = quantity;
}

function addToCart() {
    console.log('Adding to cart');
    showToast('Added to cart', true);
    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
}

function placeOrder() {
    console.log('Placing order');
    showToast('Order placed successfully', true);
    bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
}

function closePopup() {
    document.querySelectorAll('.popup').forEach(popup => {
        popup.style.display = 'none';
    });
}

function closePopupOnClick(event) {
    if (event.target.classList.contains('popup')) {
        event.target.style.display = 'none';
    }
}

function closeThankYouPopup() {
    document.getElementById('thank-you-popup').style.display = 'none';
}

function openForgotPasswordPopup() {
    closePopup();
    document.getElementById('forgot-password-popup').style.display = 'block';
}

function openSignupPopup() {
    closePopup();
    document.getElementById('signup-popup').style.display = 'block';
}
