(function() {
    'use strict';

    // ==================== Feature Detection ====================

    /**
     * Check if user prefers dark mode
     */
    function isDarkMode() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return true;
        }
        return false;
    }

    /**
     * Check if device is mobile
     */
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // ==================== Dark Mode Support ====================

    /**
     * Apply dark mode CSS variables if needed
     */
    function initializeDarkMode() {
        var htmlElement = document.documentElement;
        var darkModeButtons = document.querySelectorAll('.wape-btn--dark-aware');

        if (isDarkMode()) {
            htmlElement.setAttribute('data-wape-dark-mode', 'true');
            darkModeButtons.forEach(function(btn) {
                btn.classList.add('wape-dark-mode-active');
            });
        }

        // Listen for changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addListener(function(e) {
                if (e.matches) {
                    htmlElement.setAttribute('data-wape-dark-mode', 'true');
                    darkModeButtons.forEach(function(btn) {
                        btn.classList.add('wape-dark-mode-active');
                    });
                } else {
                    htmlElement.removeAttribute('data-wape-dark-mode');
                    darkModeButtons.forEach(function(btn) {
                        btn.classList.remove('wape-dark-mode-active');
                    });
                }
            });
        }
    }

    // ==================== Product Variants Support ====================

    /**
     * Build extra details including quantity and variants
     */
    function buildExtraDetails(anchor) {
        var extraLines = [];
        var productForm = anchor.closest('form.cart');

        if (!productForm) {
            return '';
        }

        // Add quantity if present and > 1
        var qtyInput = productForm.querySelector('input.qty');
        if (qtyInput && qtyInput.value && qtyInput.value !== '1') {
            extraLines.push('Quantity: ' + qtyInput.value);
        }

        // Add product variants/attributes if present
        var variationSelects = productForm.querySelectorAll('select[name^="attribute_"]');
        variationSelects.forEach(function(select) {
            var label = select.getAttribute('name') || '';
            label = label.replace('attribute_', '').replace(/_/g, ' ');
            if (!select.value) {
                return;
            }

            var selectedOption = select.options[select.selectedIndex];
            var valueText = selectedOption ? selectedOption.textContent.trim() : select.value;
            if (valueText) {
                extraLines.push(label + ': ' + valueText);
            }
        });

        return extraLines.join('\n');
    }

    // ==================== URL Building ====================

    /**
     * Append extra text to WhatsApp message URL
     */
    function withExtraInWaUrl(url, extraText) {
        if (!extraText) {
            return url;
        }

        try {
            var textMatch = url.match(/[?&]text=([^&]*)/);
            if (!textMatch) {
                return url;
            }

            var existingEncoded = textMatch[1];
            var existingText = decodeURIComponent(existingEncoded.replace(/\+/g, ' '));

            if (existingText && existingText.charAt(existingText.length - 1) !== '\n') {
                existingText = existingText + '\n';
            }
            existingText = existingText + extraText;

            var newEncoded = encodeURIComponent(existingText);
            return url.replace(/([?&]text=)[^&]*/, '$1' + newEncoded);
        } catch (e) {
            console.error('WAPE: Error updating WhatsApp URL', e);
            return url;
        }
    }

    // ==================== Device Visibility ====================

    /**
     * Check if button should be visible on current device
     */
    function shouldShowButton(button) {
        var isDeviceMobile = isMobile();
        var hasHideMobileClass = button.parentElement.classList.contains('wape-hide-mobile');
        var hasHideDesktopClass = button.parentElement.classList.contains('wape-hide-desktop');

        if (isDeviceMobile && hasHideMobileClass) {
            return false;
        }

        if (!isDeviceMobile && hasHideDesktopClass) {
            return false;
        }

        return true;
    }

    // ==================== Event Listeners ====================

    /**
     * Handle dynamic button clicks with extra details
     */
    function handleButtonClick(event) {
        var button = event.target.closest('.wape-btn[data-wape-dynamic="1"]');
        if (!button) {
            return;
        }

        if (!shouldShowButton(button)) {
            event.preventDefault();
            return false;
        }

        var href = button.getAttribute('href');
        if (!href || href.indexOf('wa.me') === -1) {
            return;
        }

        var extraText = buildExtraDetails(button);
        if (!extraText) {
            return;
        }

        button.setAttribute('href', withExtraInWaUrl(href, extraText));
    }

    /**
     * Handle WooCommerce variation changes
     */
    function handleVariationChange() {
        var variationForm = document.querySelector('form.variations_form');
        if (!variationForm) {
            return;
        }

        variationForm.addEventListener('change', function() {
            var waButtons = document.querySelectorAll('.wape-btn[data-wape-dynamic="1"]');
            waButtons.forEach(function(btn) {
                var href = btn.getAttribute('href');
                if (href && href.indexOf('wa.me') !== -1) {
                    btn.setAttribute('href', href);
                }
            });
        });
    }

    // ==================== Initialize ====================

    /**
     * Initialize all features
     */
    function init() {
        // Initialize dark mode support
        initializeDarkMode();

        // Set up event listeners
        document.addEventListener('click', handleButtonClick);

        // Set up variation change listeners
        handleVariationChange();

        // Log initialization if debug mode
        if (window.wapeDebug) {
            console.log('WAPE: Initialized successfully');
            console.log('WAPE: Dark mode:', isDarkMode());
            console.log('WAPE: Mobile device:', isMobile());
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();