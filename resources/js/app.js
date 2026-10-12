/**
 * LaundryDesk — Core Client Utilities (100% Offline-First)
 * Lightweight Vanilla JS: No external libraries, no CDN
 */

(function () {
    'use strict';

    window.LaundryDesk = {
        /**
         * Format number to Indonesian Rupiah currency string
         * Example: 25000 -> "Rp 25.000"
         */
        formatRupiah: function (amount) {
            const number = parseFloat(amount) || 0;
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(number);
        },

        /**
         * Parse string to numeric float
         */
        parseNumber: function (value) {
            if (typeof value === 'number') return value;
            if (!value) return 0;
            const cleaned = value.toString().replace(/[^0-9,-]+/g, '').replace(',', '.');
            return parseFloat(cleaned) || 0;
        },

        /**
         * Recalculate transaction totals on client for instant feedback
         */
        calculateTotal: function (subtotal, discount, extra) {
            const sub = this.parseNumber(subtotal);
            const disc = Math.max(0, this.parseNumber(discount));
            const ext = Math.max(0, this.parseNumber(extra));
            return Math.max(0, sub - disc + ext);
        },

        /**
         * Initialize anti double-submission on forms
         */
        initAntiDoubleSubmit: function () {
            document.querySelectorAll('form[data-prevent-double-submit]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span>Memproses...</span>';
                        // Re-enable after 8 seconds in case of client-side validation abort
                        setTimeout(function () {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 8000);
                    }
                });
            });
        },

        /**
         * Setup confirmation modal dialogs
         */
        confirmAction: function (message, callback) {
            if (window.confirm(message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?')) {
                if (typeof callback === 'function') callback();
                return true;
            }
            return false;
        }
    };

    // Auto initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        window.LaundryDesk.initAntiDoubleSubmit();
    });
})();
