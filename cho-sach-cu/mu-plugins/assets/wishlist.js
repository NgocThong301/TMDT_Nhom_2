/**
 * CSC Wishlist JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Add to wishlist
        $(document).on('click', '.csc-wishlist-btn, .csc-wishlist-btn-single', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var productId = $btn.data('product-id');
            
            if ($btn.hasClass('active')) {
                // Remove from wishlist
                $.ajax({
                    url: cscWishlist.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'csc_remove_from_wishlist',
                        product_id: productId,
                        nonce: cscWishlist.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.removeClass('active');
                            $btn.find('i').removeClass('fas fa-heart').addClass('far fa-heart');
                            var $span = $btn.find('span');
                            if ($span.length) $span.text('Thêm vào yêu thích');
                            $btn.attr('title', 'Thêm vào yêu thích');
                            $btn.attr('aria-label', 'Thêm vào yêu thích');
                            
                            // Show notification
                            showNotification(cscWishlist.textRemoved, 'success');
                        }
                    }
                });
            } else {
                // Add to wishlist
                $.ajax({
                    url: cscWishlist.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'csc_add_to_wishlist',
                        product_id: productId,
                        nonce: cscWishlist.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.addClass('active');
                            $btn.find('i').removeClass('far fa-heart').addClass('fas fa-heart');
                            var $span = $btn.find('span');
                            if ($span.length) $span.text('Xóa khỏi yêu thích');
                            $btn.attr('title', 'Xóa khỏi yêu thích');
                            $btn.attr('aria-label', 'Xóa khỏi yêu thích');
                            
                            // Show notification
                            showNotification(cscWishlist.textAdded, 'success');
                        }
                    }
                });
            }
        });
        
        // Remove from wishlist page
        $(document).on('click', '.csc-wishlist-remove', function() {
            var productId = $(this).data('product-id');
            var $item = $(this).closest('.csc-wishlist-item');
            
            $.ajax({
                url: cscWishlist.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'csc_remove_from_wishlist',
                    product_id: productId,
                    nonce: cscWishlist.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if list is empty
                            if ($('.csc-wishlist-item').length === 0) {
                                location.reload();
                            }
                        });
                        
                        showNotification('Đã xóa sản phẩm khỏi danh sách yêu thích', 'success');
                    }
                }
            });
        });
        
        // Notification function
        function showNotification(message, type) {
            // Remove existing notifications
            $('.csc-notification').remove();
            
            var $notification = $('<div class="csc-notification ' + type + '">' + message + '</div>');
            
            $('body').append($notification);
            
            // Animate in
            $notification.animate({
                top: '20px',
                opacity: 1
            }, 300);
            
            // Auto remove after 3 seconds
            setTimeout(function() {
                $notification.animate({
                    top: '-50px',
                    opacity: 0
                }, 300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    });
    
    // Add notification styles
    $('<style>')
        .text(`
            .csc-notification {
                position: fixed;
                top: -50px;
                right: 20px;
                padding: 15px 25px;
                border-radius: 8px;
                color: white;
                font-size: 14px;
                z-index: 99999;
                opacity: 0;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .csc-notification.success {
                background: #00a32a;
            }
            .csc-notification.error {
                background: #d63638;
            }
        `)
        .appendTo('head');
    
})(jQuery);
