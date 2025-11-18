jQuery(document).ready(function($) {
    'use strict';

    // ===== INITIALIZATION =====
    function initModernCart() {
        updateCartDisplay();
        initSmoothAnimations();
        initProductListInteractions();
        bindGlobalEvents();
        
        console.log('🛒 Midtrans Modern Cart System Initialized');
    }

    function initSmoothAnimations() {
        // Animate product cards on load
        $('.product-card').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(30px)'
            }).delay(index * 100).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 600, 'easeOutCubic');
        });

        // Add smooth hover effects
        $('.product-card').hover(
            function() {
                $(this).css('transform', 'translateY(-8px) scale(1.02)');
            },
            function() {
                $(this).css('transform', 'translateY(0) scale(1)');
            }
        );
    }

    // Tambahkan di file midtrans-standalone.js dalam function initProductListInteractions()

function initProductListInteractions() {
    // Enhanced hover effects
    $('.product-card').hover(
        function() {
            $(this).addClass('hover-active');
            $(this).find('.quick-actions').css({
                'opacity': '1',
                'transform': 'translateY(0)'
            });
        },
        function() {
            $(this).removeClass('hover-active');
            $(this).find('.quick-actions').css({
                'opacity': '0',
                'transform': 'translateY(-5px)'
            });
        }
    );

    // Quick actions
    $('body').on('click', '.quick-action-btn.heart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        $btn.toggleClass('active');
        showModernToast($btn.hasClass('active') ? 'Added to wishlist' : 'Removed from wishlist', 'info');
    });

    $('body').on('click', '.quick-action-btn.eye', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var productId = $(this).closest('.product-card').data('product-id');
        // Quick view functionality can be added here
        showModernToast('Quick view coming soon!', 'info');
    });

    // Lazy loading with intersection observer
    if ('IntersectionObserver' in window) {
        const lazyImageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const lazyImage = entry.target;
                    if (lazyImage.dataset.src) {
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.classList.remove('lazy');
                    }
                    lazyImageObserver.unobserve(lazyImage);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(lazyImage => {
            lazyImageObserver.observe(lazyImage);
        });
    }
}

    function bindGlobalEvents() {
        // Close cart when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#floating-cart').length && $('#floating-cart').hasClass('active')) {
                closeFloatingCart();
            }
        });

        // Prevent cart close when clicking inside cart
        $('.cart-panel').on('click', function(e) {
            e.stopPropagation();
        });

        // Initialize on page load
        $(window).on('load', function() {
            $('body').addClass('page-loaded');
        });

        // Handle page transitions
        $(document).on('ajaxStart', function() {
            $('body').addClass('loading');
        });

        $(document).on('ajaxStop', function() {
            $('body').removeClass('loading');
        });
    }

    // ===== ENHANCED CART FUNCTIONALITY =====
    function updateCartDisplay() {
        $.post(midtrans_ajax.ajax_url, {
            action: 'get_cart',
            nonce: midtrans_ajax.nonce
        }, function(response) {
            if (response.success) {
                var cartCount = response.data.cart_count;
                var cartTotal = response.data.cart_total;
                
                animateCounter('.cart-count', cartCount);
                $('.total-amount').text(formatCurrency(cartTotal));
                
                if (cartCount > 0) {
                    $('.cart-icon').addClass('pulse');
                    setTimeout(() => {
                        $('.cart-icon').removeClass('pulse');
                    }, 600);
                }
                
                $('.checkout-btn').prop('disabled', cartCount === 0);
                
                if ($('#cart-contents').length) {
                    loadCartPage(response.data.cart, cartTotal);
                }
            }
        }).fail(function() {
            showModernToast('Failed to load cart', 'error');
        });
    }

    function animateCounter(selector, newValue) {
        var $element = $(selector);
        var currentValue = parseInt($element.text()) || 0;
        
        if (currentValue !== newValue) {
            $element.addClass('counting');
            setTimeout(() => {
                $element.text(newValue);
                $element.removeClass('counting');
            }, 300);
        }
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID').format(amount);
    }

    // ===== PRODUCT LIST INTERACTIONS =====
    $('body').on('click', '.quantity-btn.plus', function() {
        var $input = $(this).siblings('.quantity-input');
        var max = parseInt($input.attr('max')) || 9999;
        var currentVal = parseInt($input.val()) || 1;
        
        if (currentVal < max) {
            $input.val(currentVal + 1).trigger('change');
            animateQuantityChange($input, 'plus');
        } else {
            showModernToast('Maximum quantity reached', 'warning');
        }
    });

    $('body').on('click', '.quantity-btn.minus', function() {
        var $input = $(this).siblings('.quantity-input');
        var currentVal = parseInt($input.val()) || 1;
        
        if (currentVal > 1) {
            $input.val(currentVal - 1).trigger('change');
            animateQuantityChange($input, 'minus');
        }
    });

    $('body').on('change', '.quantity-input', function() {
        var $input = $(this);
        var value = parseInt($input.val());
        var min = parseInt($input.attr('min')) || 1;
        var max = parseInt($input.attr('max')) || 9999;
        
        if (isNaN(value) || value < min) {
            $input.val(min);
        } else if (value > max) {
            $input.val(max);
            showModernToast('Maximum quantity is ' + max, 'warning');
        }
    });

    $('body').on('keypress', '.quantity-input', function(e) {
        var charCode = (e.which) ? e.which : e.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    function animateQuantityChange($input, direction) {
        $input.addClass('changing');
        
        if (direction === 'plus') {
            $input.css('transform', 'scale(1.1)');
        } else {
            $input.css('transform', 'scale(0.9)');
        }
        
        setTimeout(() => {
            $input.removeClass('changing');
            $input.css('transform', 'scale(1)');
        }, 200);
    }

    // ===== MODERN ADD TO CART =====
    $('body').on('click', '.add-to-cart-btn:not(:disabled)', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productId = $button.data('product-id');
        var $productCard = $button.closest('.product-card, .product-detail-container');
        var quantity = 1;
        
        // Get quantity from input if available
        var $quantityInput = $productCard.find('.quantity-input, #quantity');
        if ($quantityInput.length) {
            quantity = parseInt($quantityInput.val());
            if (quantity < 1) quantity = 1;
        }
        
        var originalHtml = $button.html();
        
        // Create modern loading state
        $button.prop('disabled', true).html(`
            <span class="button-loader"></span>
            Adding...
        `);
        
        // Add ripple effect
        createRippleEffect($button, e);
        
        // Add loading state to product card
        $productCard.addClass('loading');
        
        $.post(midtrans_ajax.ajax_url, {
            action: 'add_to_cart',
            nonce: midtrans_ajax.nonce,
            product_id: productId,
            quantity: quantity
        }, function(response) {
            $productCard.removeClass('loading');
            
            if (response.success) {
                // Success state with icon
                $button.html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Added!
                `);
                
                updateCartDisplay();
                showModernToast('Product added to cart! 🎉', 'success');
                
                // Product celebration animation
                celebrateProduct($productCard);
                
                // Reset button after delay
                setTimeout(function() {
                    $button.html(originalHtml).prop('disabled', false);
                }, 2000);
                
            } else {
                showModernToast('Error: ' + response.data, 'error');
                $button.html(originalHtml).prop('disabled', false);
            }
        }).fail(function() {
            $productCard.removeClass('loading');
            showModernToast('Network error. Please try again.', 'error');
            $button.html(originalHtml).prop('disabled', false);
        });
    });

    // ===== MODERN TOAST NOTIFICATIONS =====
    function showModernToast(message, type = 'info') {
        var icon = getToastIcon(type);
        var $toast = $(`
            <div class="cart-toast cart-toast-${type}">
                <div class="toast-content">
                    <span class="toast-icon">${icon}</span>
                    <span class="toast-message">${message}</span>
                </div>
                <div class="toast-progress"></div>
            </div>
        `);
        
        $('body').append($toast);
        
        // Animate progress bar
        setTimeout(() => {
            $toast.find('.toast-progress').css('width', '0%');
        }, 100);
        
        $toast.fadeIn(400);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            $toast.fadeOut(400, function() {
                $(this).remove();
            });
        }, 4000);
    }

    function getToastIcon(type) {
        const icons = {
            success: '✓',
            error: '⚠',
            info: 'ℹ',
            warning: '⚠'
        };
        return icons[type] || 'ℹ';
    }

    // ===== ENHANCED ANIMATIONS =====
    function celebrateProduct($element) {
        $element.addClass('celebrating');
        
        // Create multiple confetti particles
        for (let i = 0; i < 12; i++) {
            createConfetti($element);
        }
        
        setTimeout(() => {
            $element.removeClass('celebrating');
        }, 1000);
    }

    function createConfetti($parent) {
        var colors = ['#667eea', '#764ba2', '#f093fb', '#10b981', '#f59e0b', '#ef4444'];
        var confetti = $(`
            <div class="confetti" style="
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${Math.random() * 100}%;
                animation-delay: ${Math.random() * 0.5}s;
                transform: rotate(${Math.random() * 360}deg);
            "></div>
        `);
        
        $parent.append(confetti);
        
        setTimeout(() => {
            confetti.remove();
        }, 1000);
    }

    function createRippleEffect($button, event) {
        var $ripple = $('<span class="button-ripple"></span>');
        $button.append($ripple);
        
        var buttonPos = $button.offset();
        var x = event.pageX - buttonPos.left;
        var y = event.pageY - buttonPos.top;
        
        $ripple.css({
            left: x,
            top: y
        });
        
        setTimeout(() => {
            $ripple.remove();
        }, 600);
    }

    // ===== FLOATING CART ENHANCEMENTS =====
    $('body').on('click', '.cart-icon', function(e) {
        e.stopPropagation();
        var $cart = $('#floating-cart');
        
        if ($cart.hasClass('active')) {
            closeFloatingCart();
        } else {
            openFloatingCart();
        }
    });

    function openFloatingCart() {
        var $cart = $('#floating-cart');
        $cart.addClass('active');
        loadFloatingCart();
        
        $cart.find('.cart-panel').css({
            'transform': 'translateY(20px) scale(0.95)',
            'opacity': '0'
        }).animate({
            'transform': 'translateY(0) scale(1)',
            'opacity': '1'
        }, 300);
    }

    function closeFloatingCart() {
        var $cart = $('#floating-cart');
        $cart.find('.cart-panel').animate({
            'transform': 'translateY(20px) scale(0.95)',
            'opacity': '0'
        }, 200, function() {
            $cart.removeClass('active');
        });
    }

    $('body').on('click', '.close-cart', function(e) {
        e.stopPropagation();
        closeFloatingCart();
    });

    // ===== CART MANAGEMENT =====
    function loadFloatingCart() {
        $.post(midtrans_ajax.ajax_url, {
            action: 'get_cart',
            nonce: midtrans_ajax.nonce
        }, function(response) {
            if (response.success) {
                var cart = response.data.cart;
                var cartTotal = response.data.cart_total;
                var $cartItems = $('.cart-items');
                
                if (Object.keys(cart).length === 0) {
                    $cartItems.html(`
                        <div class="empty-cart-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                            <p>Your cart is empty</p>
                            <small>Add some products to get started</small>
                        </div>
                    `);
                } else {
                    var html = '';
                    $.each(cart, function(productId, item) {
                        var itemTotal = item.quantity * item.price;
                        html += `
                            <div class="cart-item" data-product-id="${productId}">
                                <div class="item-image">
                                    ${item.image ? 
                                        `<img src="${item.image}" alt="${item.name}" loading="lazy">` : 
                                        `<div class="no-image">No Image</div>`
                                    }
                                </div>
                                <div class="item-details">
                                    <h4>${item.name}</h4>
                                    <div class="item-meta">
                                        <span class="item-price">Rp ${formatCurrency(item.price)}</span>
                                        <span class="item-total">Rp ${formatCurrency(itemTotal)}</span>
                                    </div>
                                    <div class="item-quantity">
                                        <button class="quantity-btn minus" data-product-id="${productId}">−</button>
                                        <input type="number" class="cart-quantity-input" 
                                               data-product-id="${productId}" value="${item.quantity}" min="1">
                                        <button class="quantity-btn plus" data-product-id="${productId}">+</button>
                                    </div>
                                </div>
                                <button class="remove-from-cart" data-product-id="${productId}" title="Remove item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                    });
                    $cartItems.html(html);
                }
                
                $('.total-amount').text(formatCurrency(cartTotal));
            }
        });
    }

    function loadCartPage(cart, cartTotal) {
        var $cartContents = $('#cart-contents');
        var $customerInfo = $('.customer-info');
        
        if (Object.keys(cart).length === 0) {
            $cartContents.html(`
                <div class="empty-cart-state detailed">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="${midtrans_ajax.home_url}" class="continue-shopping-btn">Continue Shopping</a>
                </div>
            `);
            $customerInfo.hide();
        } else {
            var html = '<div class="cart-items-list">';
            $.each(cart, function(productId, item) {
                var itemTotal = item.quantity * item.price;
                html += `
                    <div class="cart-item detailed" data-product-id="${productId}">
                        <div class="item-image">
                            ${item.image ? 
                                `<img src="${item.image}" alt="${item.name}" loading="lazy">` : 
                                `<div class="no-image">No Image</div>`
                            }
                        </div>
                        <div class="item-info">
                            <h3>${item.name}</h3>
                            <div class="item-price">Rp ${formatCurrency(item.price)}</div>
                        </div>
                        <div class="item-quantity">
                            <button class="quantity-btn minus" data-product-id="${productId}">−</button>
                            <input type="number" class="cart-quantity-input" 
                                   data-product-id="${productId}" value="${item.quantity}" min="1">
                            <button class="quantity-btn plus" data-product-id="${productId}">+</button>
                        </div>
                        <div class="item-total">Rp ${formatCurrency(itemTotal)}</div>
                        <button class="remove-from-cart" data-product-id="${productId}" title="Remove item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                `;
            });
            
            html += `
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>Rp ${formatCurrency(cartTotal)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span class="cart-total">Rp ${formatCurrency(cartTotal)}</span>
                    </div>
                </div>
            `;
            
            $cartContents.html(html);
            $customerInfo.show();
        }
    }

    // ===== QUANTITY MANAGEMENT =====
    $('body').on('click', '.quantity-btn.minus', function() {
        var productId = $(this).data('product-id');
        var $input = $(this).siblings('.cart-quantity-input');
        var currentVal = parseInt($input.val());
        
        if (currentVal > 1) {
            $input.val(currentVal - 1).trigger('change');
        }
    });

    $('body').on('click', '.quantity-btn.plus', function() {
        var productId = $(this).data('product-id');
        var $input = $(this).siblings('.cart-quantity-input');
        var currentVal = parseInt($input.val());
        
        $input.val(currentVal + 1).trigger('change');
    });

    $('body').on('change', '.cart-quantity-input', function() {
        var $input = $(this);
        var productId = $input.data('product-id');
        var quantity = parseInt($input.val());
        
        if (quantity < 1) {
            removeFromCart(productId);
            return;
        }
        
        $input.addClass('updating').prop('disabled', true);
        
        $.post(midtrans_ajax.ajax_url, {
            action: 'update_cart',
            nonce: midtrans_ajax.nonce,
            product_id: productId,
            quantity: quantity
        }, function(response) {
            if (response.success) {
                updateCartDisplay();
                if ($('#floating-cart').hasClass('active')) {
                    loadFloatingCart();
                }
                showModernToast('Cart updated', 'success');
            } else {
                showModernToast('Error: ' + response.data, 'error');
                $input.val($input.data('old-value'));
            }
            $input.removeClass('updating').prop('disabled', false);
        }).fail(function() {
            showModernToast('Network error', 'error');
            $input.val($input.data('old-value'));
            $input.removeClass('updating').prop('disabled', false);
        });
    });

    $('body').on('focus', '.cart-quantity-input', function() {
        $(this).data('old-value', $(this).val());
    });

    // ===== REMOVE FROM CART =====
    $('body').on('click', '.remove-from-cart', function() {
        var productId = $(this).data('product-id');
        var $item = $(this).closest('.cart-item');
        
        $item.addClass('removing');
        setTimeout(() => {
            removeFromCart(productId);
        }, 300);
    });

    function removeFromCart(productId) {
        $.post(midtrans_ajax.ajax_url, {
            action: 'remove_from_cart',
            nonce: midtrans_ajax.nonce,
            product_id: productId
        }, function(response) {
            if (response.success) {
                updateCartDisplay();
                if ($('#floating-cart').hasClass('active')) {
                    loadFloatingCart();
                }
                showModernToast('Product removed from cart', 'info');
            } else {
                showModernToast('Error: ' + response.data, 'error');
            }
        }).fail(function() {
            showModernToast('Network error', 'error');
        });
    }

    // ===== PAYMENT PROCESSING =====
    $('#midtrans-payment-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $payButton = $('#pay-button');
        var $status = $('#payment-status');
        var originalText = $payButton.text();
        
        // Enhanced loading state
        $payButton.prop('disabled', true).html(`
            <span class="button-loader"></span>
            Processing Payment...
        `);
        
        $status.html(`
            <div class="payment-status status-loading">
                <div class="loading-spinner"></div>
                <div>Creating secure payment session...</div>
            </div>
        `);
        
        var formData = {
            action: 'create_midtrans_payment',
            nonce: midtrans_ajax.nonce,
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            amount: $('#amount').val()
        };
        
        $.post(midtrans_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                var snapToken = response.data.snap_token;
                var orderId = response.data.order_id;
                
                $status.html(`
                    <div class="payment-status status-loading">
                        <div class="loading-spinner"></div>
                        <div>Redirecting to secure payment gateway...</div>
                    </div>
                `);
                
                // Open Midtrans Snap with enhanced options
                window.snap.pay(snapToken, {
                    onSuccess: function(result) {
                        showPaymentSuccess($status, $form);
                        showModernToast('Payment successful! Thank you! 🎉', 'success');
                    },
                    onPending: function(result) {
                        showPaymentPending($status, orderId);
                        showModernToast('Payment pending. Please complete your payment.', 'info');
                    },
                    onError: function(result) {
                        showPaymentError($status, $payButton, originalText);
                        showModernToast('Payment failed. Please try again.', 'error');
                    },
                    onClose: function() {
                        $payButton.html(originalText).prop('disabled', false);
                        checkPaymentStatus(orderId);
                    }
                });
                
            } else {
                showPaymentError($status, $payButton, originalText);
                showModernToast('Error: ' + response.data, 'error');
            }
        }).fail(function() {
            showPaymentError($status, $payButton, originalText);
            showModernToast('Network error. Please try again.', 'error');
        });
    });

    // ===== DIRECT PAYMENT BUTTON =====
    $('body').on('click', '.midtrans-pay-button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $status = $('#' + $button.attr('id') + '-status');
        var originalText = $button.text();
        var originalHtml = $button.html();
        
        $button.prop('disabled', true).html(`
            <span class="button-loader"></span>
            Processing...
        `);
        
        createRippleEffect($button, e);
        
        var paymentData = {
            action: 'create_direct_midtrans_payment',
            nonce: midtrans_ajax.nonce,
            amount: $button.data('amount'),
            customer_name: $button.data('customer-name'),
            customer_email: $button.data('customer-email'),
            customer_phone: $button.data('customer-phone')
        };
        
        $.post(midtrans_ajax.ajax_url, paymentData, function(response) {
            if (response.success) {
                var snapToken = response.data.snap_token;
                var orderId = response.data.order_id;
                
                $status.html(`
                    <div class="payment-status status-loading">
                        <div class="loading-spinner"></div>
                        <div>Opening payment gateway...</div>
                    </div>
                `);
                
                window.snap.pay(snapToken, {
                    onSuccess: function(result) {
                        $status.html(`
                            <div class="payment-status status-success">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div>
                                    <strong>Payment successful!</strong>
                                    <p>Thank you for your payment.</p>
                                </div>
                            </div>
                        `);
                        showModernToast('Payment completed successfully! 🎉', 'success');
                        setTimeout(() => window.location.reload(), 3000);
                    },
                    onPending: function(result) {
                        $status.html(`
                            <div class="payment-status status-pending">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div>
                                    <strong>Payment pending</strong>
                                    <p>Please complete your payment.</p>
                                </div>
                            </div>
                        `);
                        checkDirectPaymentStatus(orderId, $status);
                        showModernToast('Payment pending. Complete your payment.', 'info');
                    },
                    onError: function(result) {
                        $status.html(`
                            <div class="payment-status status-error">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div>
                                    <strong>Payment failed</strong>
                                    <p>Please try again.</p>
                                </div>
                            </div>
                        `);
                        $button.html(originalHtml).prop('disabled', false);
                        showModernToast('Payment failed. Please try again.', 'error');
                    },
                    onClose: function() {
                        $button.html(originalHtml).prop('disabled', false);
                        checkDirectPaymentStatus(orderId, $status);
                    }
                });
                
            } else {
                $status.html(`
                    <div class="payment-status status-error">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <div>Error: ${response.data}</div>
                    </div>
                `);
                $button.html(originalHtml).prop('disabled', false);
                showModernToast('Error: ' + response.data, 'error');
            }
        }).fail(function(xhr, status, error) {
            $status.html(`
                <div class="payment-status status-error">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <div>Network error: ${error}</div>
                </div>
            `);
            $button.html(originalHtml).prop('disabled', false);
            showModernToast('Network error. Please try again.', 'error');
        });
    });

    // ===== CART CHECKOUT =====
    $('#cart-checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('.checkout-button');
        var $status = $('#cart-payment-status');
        var originalText = $button.text();
        
        $button.prop('disabled', true).html(`
            <span class="button-loader"></span>
            Processing Order...
        `);
        
        $status.html(`
            <div class="payment-status status-loading">
                <div class="loading-spinner"></div>
                <div>Creating secure payment session...</div>
            </div>
        `);
        
        var formData = {
            action: 'create_cart_payment',
            nonce: midtrans_ajax.nonce,
            customer_name: $('#cart_customer_name').val(),
            customer_email: $('#cart_customer_email').val(),
            customer_phone: $('#cart_customer_phone').val()
        };
        
        $.post(midtrans_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                var snapToken = response.data.snap_token;
                var orderId = response.data.order_id;
                
                $status.html(`
                    <div class="payment-status status-loading">
                        <div class="loading-spinner"></div>
                        <div>Redirecting to payment gateway...</div>
                    </div>
                `);
                
                window.snap.pay(snapToken, {
                    onSuccess: function(result) {
                        $status.html(`
                            <div class="payment-status status-success">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div>
                                    <strong>Order Successful!</strong>
                                    <p>Thank you for your purchase. You will receive a confirmation email shortly.</p>
                                </div>
                            </div>
                        `);
                        $form[0].reset();
                        updateCartDisplay();
                        showModernToast('Order completed successfully! 🎉', 'success');
                    },
                    onPending: function(result) {
                        $status.html(`
                            <div class="payment-status status-pending">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div>
                                    <strong>Payment Pending</strong>
                                    <p>Please complete your payment to confirm your order.</p>
                                </div>
                            </div>
                        `);
                        checkPaymentStatus(orderId);
                        showModernToast('Payment pending. Complete to confirm order.', 'info');
                    },
                    onError: function(result) {
                        $status.html(`
                            <div class="payment-status status-error">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div>
                                    <strong>Payment Failed</strong>
                                    <p>Please try again or contact support.</p>
                                </div>
                            </div>
                        `);
                        $button.html(originalText).prop('disabled', false);
                        showModernToast('Payment failed. Please try again.', 'error');
                    },
                    onClose: function() {
                        $button.html(originalText).prop('disabled', false);
                        checkPaymentStatus(orderId);
                    }
                });
                
            } else {
                $status.html(`
                    <div class="payment-status status-error">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <div>Error: ${response.data}</div>
                    </div>
                `);
                $button.html(originalText).prop('disabled', false);
                showModernToast('Error: ' + response.data, 'error');
            }
        }).fail(function() {
            $status.html(`
                <div class="payment-status status-error">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <div>Network error. Please try again.</div>
                </div>
            `);
            $button.html(originalText).prop('disabled', false);
            showModernToast('Network error. Please try again.', 'error');
        });
    });

    // ===== PAYMENT STATUS CHECKERS =====
    function checkPaymentStatus(orderId) {
        $.post(midtrans_ajax.ajax_url, {
            action: 'check_payment_status',
            nonce: midtrans_ajax.nonce,
            order_id: orderId
        }, function(response) {
            if (response.success) {
                var status = response.data.status;
                
                if (status === 'success') {
                    $('.payment-status').html(`
                        <div class="payment-status status-success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <div>
                                <strong>Payment Verified!</strong>
                                <p>Thank you for your payment.</p>
                            </div>
                        </div>
                    `);
                } else if (status === 'pending') {
                    setTimeout(() => checkPaymentStatus(orderId), 5000);
                }
            }
        });
    }

    function checkDirectPaymentStatus(orderId, $statusElement) {
        $.post(midtrans_ajax.ajax_url, {
            action: 'check_payment_status',
            nonce: midtrans_ajax.nonce,
            order_id: orderId
        }, function(response) {
            if (response.success) {
                var status = response.data.status;
                
                if (status === 'success') {
                    $statusElement.html(`
                        <div class="payment-status status-success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <div>
                                <strong>Payment Successful!</strong>
                                <p>Thank you for your payment.</p>
                            </div>
                        </div>
                    `);
                    setTimeout(() => {
                        window.location.href = window.location.href + '?payment=success';
                    }, 2000);
                } else if (status === 'pending') {
                    setTimeout(() => checkDirectPaymentStatus(orderId, $statusElement), 5000);
                }
            }
        });
    }

    // ===== PAYMENT STATUS HELPERS =====
    function showPaymentSuccess($status, $form) {
        $status.html(`
            <div class="payment-status status-success">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"/>
                </svg>
                <div>
                    <strong>Payment Successful!</strong>
                    <p>Thank you for your payment. A confirmation email has been sent.</p>
                </div>
            </div>
        `);
        $form[0].reset();
    }

    function showPaymentPending($status, orderId) {
        $status.html(`
            <div class="payment-status status-pending">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" stroke-width="2"/>
                </svg>
                <div>
                    <strong>Payment Pending</strong>
                    <p>Please complete your payment. We'll notify you once confirmed.</p>
                </div>
            </div>
        `);
        checkPaymentStatus(orderId);
    }

    function showPaymentError($status, $button, originalText) {
        $status.html(`
            <div class="payment-status status-error">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <div>
                    <strong>Payment Failed</strong>
                    <p>Please try again or contact our support team.</p>
                </div>
            </div>
        `);
        $button.html(originalText).prop('disabled', false);
    }

    // ===== UTILITY FUNCTIONS =====
    $('body').on('click', '.view-cart-btn', function() {
        window.location.href = midtrans_ajax.home_url + 'cart';
    });

    $('body').on('click', '.checkout-btn', function() {
        window.location.href = midtrans_ajax.home_url + 'checkout';
    });

    $('body').on('click', '.continue-shopping-btn', function() {
        window.location.href = midtrans_ajax.home_url;
    });

    // ===== INITIALIZE EVERYTHING =====
    initModernCart();
});