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
                form.addEventListener('submit', function () {
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
         * Mobile sidebar toggle
         */
        toggleSidebar: function () {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (sidebar && backdrop) {
                const isOpen = sidebar.classList.contains('open');
                if (isOpen) {
                    sidebar.classList.remove('open');
                    backdrop.classList.remove('active');
                } else {
                    sidebar.classList.add('open');
                    backdrop.classList.add('active');
                }
            }
        },

        closeSidebar: function () {
            const sidebar = document.getElementById('app-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (sidebar) sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('active');
        },

        /**
         * Setup confirmation modal dialogs
         */
        openConfirmModal: function (options) {
            const modal = document.getElementById('confirm-modal');
            if (!modal) {
                if (window.confirm(options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?')) {
                    if (options.formId) {
                        const form = document.getElementById(options.formId);
                        if (form) form.submit();
                    } else if (typeof options.onConfirm === 'function') {
                        options.onConfirm();
                    }
                }
                return;
            }

            const titleEl = document.getElementById('confirm-modal-title');
            const descEl = document.getElementById('confirm-modal-desc');
            const confirmBtn = document.getElementById('confirm-modal-submit');
            const form = document.getElementById('confirm-modal-form');

            if (titleEl && options.title) titleEl.textContent = options.title;
            if (descEl && options.message) descEl.textContent = options.message;

            if (confirmBtn) {
                if (options.confirmText) confirmBtn.textContent = options.confirmText;
                confirmBtn.className = 'btn ' + (options.confirmClass || 'btn-danger');
            }

            if (form && options.action) {
                form.action = options.action;
                const methodInput = form.querySelector('input[name="_method"]');
                if (methodInput && options.method) {
                    methodInput.value = options.method;
                }
            }

            modal.classList.add('open');
        },

        closeConfirmModal: function () {
            const modal = document.getElementById('confirm-modal');
            if (modal) modal.classList.remove('open');
        },

        /**
         * Dismiss alert component
         */
        dismissAlert: function (button) {
            const alert = button.closest('.alert');
            if (alert) {
                alert.style.transition = 'opacity 200ms ease, transform 200ms ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                setTimeout(function () {
                    alert.remove();
                }, 200);
            }
        }
    };

    // Auto initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        window.LaundryDesk.initAntiDoubleSubmit();

        // Close modal on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                window.LaundryDesk.closeConfirmModal();
                window.LaundryDesk.closeSidebar();
            }
        });
    });
})();
