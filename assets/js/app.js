// Elixir & Co. E-Commerce Script

// 1. Toast Notification Helper
function showToast(type, text) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-message ${type}`;
    
    let iconClass = 'fa-circle-info';
    if (type === 'success') iconClass = 'fa-circle-check';
    if (type === 'error') iconClass = 'fa-circle-xmark';
    if (type === 'warning') iconClass = 'fa-circle-exclamation';

    toast.innerHTML = `
        <div class="toast-icon"><i class="fa-solid ${iconClass}"></i></div>
        <div class="toast-text">${text}</div>
        <button class="toast-close" onclick="closeToast(this)">&times;</button>
    `;

    container.appendChild(toast);

    // Auto-remove after 5s
    setTimeout(() => {
        toast.classList.add('toast-fade-out');
        setTimeout(() => toast.remove(), 400);
    }, 5000);
}

// 2. Live Search Suggestions
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const suggestionsBox = document.getElementById('search-suggestions');

    if (searchInput && suggestionsBox) {
        searchInput.addEventListener('input', debounce(() => {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                suggestionsBox.style.display = 'none';
                suggestionsBox.innerHTML = '';
                return;
            }

            fetch(`${BASE_URL}/api/search.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.results.length > 0) {
                        suggestionsBox.innerHTML = '';
                        data.results.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.style.cursor = 'pointer';
                            div.innerHTML = `
                                <img src="${BASE_URL}/assets/images/${item.image_url}" class="suggestion-img" alt="${item.name}">
                                <div class="suggestion-info">
                                    <div class="suggestion-name">${item.name}</div>
                                    <div class="suggestion-brand">${item.brand}</div>
                                </div>
                                <div class="suggestion-price">₹${parseFloat(item.discount_price > 0 ? item.discount_price : item.price).toFixed(2)}</div>
                            `;
                            div.addEventListener('click', () => {
                                window.location.href = `${BASE_URL}/product/${item.id}`;
                            });
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    } else {
                        suggestionsBox.style.display = 'none';
                    }
                })
                .catch(() => {
                    suggestionsBox.style.display = 'none';
                });
        }, 300));

        // Close search suggestions on clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });
    }
});

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return functionExecuted = (...args) => {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 3. Cart Functions
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(`${BASE_URL}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartBadge(data.cart_count);
            showToast('success', data.message);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(() => {
        showToast('error', 'Failed to add product to cart.');
    });
}

function updateCartQty(productId, quantity) {
    if (quantity < 1) return;
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(`${BASE_URL}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartBadge(data.cart_count);
            // Reload page to refresh cart layout subtotals easily
            window.location.reload();
        } else {
            showToast('error', data.message);
        }
    });
}

function removeFromCart(productId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    fetch(`${BASE_URL}/api/cart.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartBadge(data.cart_count);
            window.location.reload();
        } else {
            showToast('error', data.message);
        }
    });
}

function updateCartBadge(count) {
    const badge = document.getElementById('cart-count');
    if (badge) {
        if (count > 0) {
            badge.innerText = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// 4. Wishlist Functions
function toggleWishlist(productId, btnElement) {
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('product_id', productId);

    fetch(`${BASE_URL}/api/wishlist.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (btnElement) {
                btnElement.classList.toggle('active');
                const heart = btnElement.querySelector('i');
                if (heart) {
                    if (data.state === 'added') {
                        heart.className = 'fa-solid fa-heart';
                    } else {
                        heart.className = 'fa-regular fa-heart';
                    }
                }
            }
            updateWishlistBadge(data.wishlist_count);
            showToast('success', data.message);
        } else {
            if (data.code === 'unauthorized') {
                showToast('warning', 'Please sign in to manage your wishlist.');
                setTimeout(openLoginModal, 400);
            } else {
                showToast('error', data.message);
            }
        }
    })
    .catch(() => {
        showToast('error', 'Failed to update wishlist.');
    });
}

function updateWishlistBadge(count) {
    const badge = document.getElementById('wishlist-count');
    if (badge) {
        if (count > 0) {
            badge.innerText = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Move wishlist item to cart
function moveWishlistToCart(productId) {
    const formData = new FormData();
    formData.append('action', 'move_to_cart');
    formData.append('product_id', productId);

    fetch(`${BASE_URL}/api/wishlist.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            updateCartBadge(data.cart_count);
            updateWishlistBadge(data.wishlist_count);
            showToast('success', data.message);
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast('error', data.message);
        }
    });
}

// 5. Coupon Application
function applyCoupon() {
    const codeInput = document.getElementById('coupon-input');
    if (!codeInput) return;
    const code = codeInput.value.trim();
    if (!code) {
        showToast('warning', 'Please enter a coupon code.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'apply_coupon');
    formData.append('coupon_code', code);

    fetch(`${BASE_URL}/api/checkout.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('success', data.message);
            // Refresh order summary values by reloading or dynamic DOM edits
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast('error', data.message);
        }
    });
}

function removeCoupon() {
    const formData = new FormData();
    formData.append('action', 'remove_coupon');

    fetch(`${BASE_URL}/api/checkout.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('success', data.message);
            setTimeout(() => window.location.reload(), 500);
        }
    });
}

// 6. Newsletter Subscription
document.addEventListener('DOMContentLoaded', () => {
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = document.getElementById('newsletter-email');
            const responseMsg = document.getElementById('newsletter-message');
            if (!emailInput) return;

            const formData = new FormData();
            formData.append('email', emailInput.value.trim());

            fetch(`${BASE_URL}/api/checkout.php?action=newsletter`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (responseMsg) {
                    responseMsg.innerText = data.message;
                    responseMsg.style.display = 'block';
                    if (data.status === 'success') {
                        responseMsg.style.color = 'var(--color-gold)';
                        emailInput.value = '';
                    } else {
                        responseMsg.style.color = 'var(--color-error)';
                    }
                }
            })
            .catch(() => {
                showToast('error', 'Newsletter subscription failed.');
            });
        });
    }
});

// 7. Payment Simulation Popup Manager
function startPaymentSimulation(totalAmount, billingData) {
    // Create modern luxury payment gateway simulator overlay
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.id = 'payment-simulation-modal';

    overlay.innerHTML = `
        <div class="payment-modal">
            <div class="payment-modal-header">
                <!-- Golden styling title -->
                <span style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 700; letter-spacing: 2px;">
                    SECURE <span style="color: var(--color-gold);">PAY</span>
                </span>
                <p style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 5px;">ELIXIR & CO. GATEWAY INTEGRATION</p>
            </div>
            
            <div class="payment-modal-amount">
                Total: ₹${parseFloat(totalAmount).toFixed(2)}
            </div>

            <div id="payment-gateways-container">
                <p style="font-size: 0.85rem; font-weight: 500; margin-bottom: 15px; text-align: center;">Select Simulated Mode</p>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
                    <button class="btn btn-black" onclick="processMockTransaction('Card', '${totalAmount}')" style="font-size: 0.8rem; padding: 10px;">
                        <i class="fa-solid fa-credit-card"></i> Pay via Credit / Debit Card
                    </button>
                    <button class="btn btn-black" onclick="processMockTransaction('UPI', '${totalAmount}')" style="font-size: 0.8rem; padding: 10px;">
                        <i class="fa-solid fa-qrcode"></i> Pay via UPI Scanner
                    </button>
                    <button class="btn btn-black" onclick="processMockTransaction('Net Banking', '${totalAmount}')" style="font-size: 0.8rem; padding: 10px;">
                        <i class="fa-solid fa-building-columns"></i> Pay via Net Banking
                    </button>
                </div>
                <button class="btn btn-outline-gold" onclick="cancelPaymentSimulation()" style="width: 100%; font-size: 0.8rem; padding: 10px;">
                    Cancel Payment
                </button>
            </div>

            <div id="payment-loading-screen" style="display: none; text-align: center; padding: 20px 0;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 3rem; color: var(--color-gold); margin-bottom: 20px;"></i>
                <h4 style="font-family: var(--font-heading); font-size: 1.2rem; margin-bottom: 10px;">Authorizing Transaction...</h4>
                <p style="font-size: 0.8rem; color: var(--color-text-muted);">Verifying safe communication with financial institute</p>
            </div>

            <div id="payment-success-screen" style="display: none; text-align: center; padding: 20px 0;">
                <i class="fa-solid fa-circle-check" style="font-size: 3.5rem; color: var(--color-success); margin-bottom: 20px;"></i>
                <h4 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--color-success); margin-bottom: 10px;">Payment Captured</h4>
                <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-bottom: 25px;">Receipt details generated successfully</p>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
}

function cancelPaymentSimulation() {
    const modal = document.getElementById('payment-simulation-modal');
    if (modal) modal.remove();
    showToast('error', 'Payment cancelled by user.');
}

function processMockTransaction(method, amount) {
    const gatewayContainer = document.getElementById('payment-gateways-container');
    const loadingScreen = document.getElementById('payment-loading-screen');
    const successScreen = document.getElementById('payment-success-screen');

    if (gatewayContainer && loadingScreen) {
        gatewayContainer.style.display = 'none';
        loadingScreen.style.display = 'block';

        // Simulate 2 seconds network delays
        setTimeout(() => {
            loadingScreen.style.display = 'none';
            if (successScreen) {
                successScreen.style.display = 'block';
                
                // Finalize order registration in database via checkout API
                const billingForm = document.getElementById('checkout-form-element');
                if (billingForm) {
                    const formData = new FormData(billingForm);
                    formData.append('action', 'place_order');
                    formData.append('payment_method', method);
                    formData.append('payment_status', 'Paid');

                    fetch(`${BASE_URL}/api/checkout.php`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            setTimeout(() => {
                                document.getElementById('payment-simulation-modal').remove();
                                showToast('success', 'Order Placed Successfully!');
                                // Redirect to order details/history tracking
                                window.location.href = `${BASE_URL}/order-tracking?order_id=${data.order_id}&email=${encodeURIComponent(data.email)}`;
                            }, 1500);
                        } else {
                            document.getElementById('payment-simulation-modal').remove();
                            showToast('error', data.message);
                        }
                    })
                    .catch(() => {
                        document.getElementById('payment-simulation-modal').remove();
                        showToast('error', 'Checkout failed. Please check form parameters.');
                    });
                }
            }
        }, 2200);
    }
}

// 8. Mobile Menu & Navigation Drawer
document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.getElementById('mobile-menu-toggle');
    const closeBtn = document.getElementById('mobile-nav-close');
    const overlay = document.getElementById('mobile-nav-overlay');
    const drawer = document.getElementById('mobile-nav-drawer');

    if (hamburger && closeBtn && overlay && drawer) {
        function openDrawer() {
            drawer.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        hamburger.addEventListener('click', openDrawer);
        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);
    }

    // 9. Mobile Filter Panel Toggle
    const filterMobileBtn = document.getElementById('filter-mobile-btn');
    const filterForm = document.getElementById('filter-form');

    if (filterMobileBtn && filterForm) {
        filterMobileBtn.addEventListener('click', () => {
            filterForm.classList.toggle('show-filters');
            if (filterForm.classList.contains('show-filters')) {
                filterMobileBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Hide Filters';
            } else {
                filterMobileBtn.innerHTML = '<i class="fa-solid fa-sliders"></i> Filter & Sort';
            }
        });
    }

    // Bind login triggers
    const loginTriggers = document.querySelectorAll('.login-trigger');
    loginTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            if (!window.location.pathname.includes('/account')) {
                e.preventDefault();
                openLoginModal();
                const drawer = document.getElementById('mobile-nav-drawer');
                const navOverlay = document.getElementById('mobile-nav-overlay');
                if (drawer && drawer.classList.contains('active')) {
                    drawer.classList.remove('active');
                    navOverlay.classList.remove('active');
                }
            }
        });
    });

    const modalClose = document.getElementById('login-modal-close');
    const modalOverlay = document.getElementById('login-modal-overlay');
    if (typeof IS_LOGGED_IN !== 'undefined' && IS_LOGGED_IN) {
        if (modalClose) modalClose.addEventListener('click', closeLoginModal);
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    closeLoginModal();
                }
            });
        }
    } else {
        // Guest user — show close button so they can dismiss the modal freely
        if (modalClose) modalClose.addEventListener('click', closeLoginModal);
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) closeLoginModal();
            });
        }
        // DO NOT auto-open modal — users browse first, login when they choose
    }

    // Intercept modal form submissions to handle via AJAX
    const modalLoginForm    = document.getElementById('modal-login-form');
    const modalRegisterForm = document.getElementById('modal-register-form');
    const modalForgotForm   = document.getElementById('modal-forgot-form');

    const handleModalFormSubmit = (form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const submitBtn = form.querySelector('.modal-submit-btn');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            }

            const formData = new FormData(form);
            formData.append('is_ajax', '1');

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => { if (!res.ok) throw new Error('Network error'); return res.json(); })
            .then(data => {
                if (data.status === 'success') {
                    showToast('success', data.message || 'Done!');

                    // Forgot password: show success panel
                    if (form.id === 'modal-forgot-form') {
                        setTimeout(() => {
                            switchLoginTab('forgot-success');
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = originalBtnText; }
                        }, 800);
                    } else {
                        setTimeout(() => {
                            window.location.href = data.redirect || `${BASE_URL}/account`;
                        }, 1000);
                    }
                } else {
                    showToast('error', data.message || 'An error occurred.');
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = originalBtnText; }
                }
            })
            .catch(err => {
                console.error(err);
                showToast('error', 'A network error occurred. Please try again.');
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = originalBtnText; }
            });
        });
    };

    if (modalLoginForm)    handleModalFormSubmit(modalLoginForm);
    if (modalRegisterForm) handleModalFormSubmit(modalRegisterForm);
    if (modalForgotForm)   handleModalFormSubmit(modalForgotForm);

});

// 10. Login Modal Functions (Global Scope)
function openLoginModal() {
    const modal = document.getElementById('login-modal-overlay');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeLoginModal() {
    const modal = document.getElementById('login-modal-overlay');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function switchLoginTab(tab) {
    const loginForm       = document.getElementById('modal-login-form');
    const registerForm    = document.getElementById('modal-register-form');
    const forgotForm      = document.getElementById('modal-forgot-form');
    const forgotSuccess   = document.getElementById('modal-forgot-success');
    const loginTab        = document.getElementById('modal-tab-login');
    const registerTab     = document.getElementById('modal-tab-register');
    const modalTabs       = document.querySelector('.login-modal-tabs');

    // Hide all panels
    [loginForm, registerForm, forgotForm, forgotSuccess].forEach(el => {
        if (el) { el.classList.remove('active'); el.style.display = 'none'; }
    });

    if (tab === 'login') {
        if (modalTabs)  modalTabs.style.display = 'flex';
        if (loginForm)  { loginForm.style.display = ''; loginForm.classList.add('active'); }
        if (loginTab)   loginTab.classList.add('active');
        if (registerTab) registerTab.classList.remove('active');
    } else if (tab === 'register') {
        if (modalTabs)    modalTabs.style.display = 'flex';
        if (registerForm) { registerForm.style.display = ''; registerForm.classList.add('active'); }
        if (registerTab)  registerTab.classList.add('active');
        if (loginTab)     loginTab.classList.remove('active');
    } else if (tab === 'forgot') {
        if (modalTabs)  modalTabs.style.display = 'none';
        if (forgotForm) { forgotForm.style.display = ''; forgotForm.classList.add('active'); }
    } else if (tab === 'forgot-success') {
        if (modalTabs)      modalTabs.style.display = 'none';
        if (forgotSuccess)  { forgotSuccess.style.display = ''; forgotSuccess.classList.add('active'); }
    }
}
