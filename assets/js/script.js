(function() {
    'use strict';

    function buildExtraDetails(anchor) {
        var extraLines = [];
        var productForm = anchor.closest('form.cart');

        if (!productForm) {
            return '';
        }

        var qtyInput = productForm.querySelector('input.qty');
        if (qtyInput && qtyInput.value && qtyInput.value !== '1') {
            extraLines.push('Quantity: ' + qtyInput.value);
        }

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

    function withExtraInWaUrl(url, extraText) {
        if (!extraText) {
            return url;
        }

        try {
            // Find the text= parameter and decode it manually
            var textMatch = url.match(/[?&]text=([^&]*)/);
            if (!textMatch) {
                return url;
            }

            var existingEncoded = textMatch[1];
            var existingText = decodeURIComponent(existingEncoded.replace(/\+/g, ' '));

            // Append extra text with a newline separator
            if (existingText && existingText.charAt(existingText.length - 1) !== '\n') {
                existingText = existingText + '\n';
            }
            existingText = existingText + extraText;

            // Re-encode using encodeURIComponent (which uses %20 for spaces, %0A for newlines)
            var newEncoded = encodeURIComponent(existingText);

            // Replace the text parameter in the original URL
            return url.replace(/([?&]text=)[^&]*/, '$1' + newEncoded);
        } catch (e) {
            return url;
        }
    }

    document.addEventListener('click', function(event) {
        var button = event.target.closest('.wape-btn[data-wape-dynamic="1"]');
        if (!button) {
            return;
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
    });
})();