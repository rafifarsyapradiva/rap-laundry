/**
 * LaundryDesk — Order Form Logic (100% Offline-First)
 * Lightweight Vanilla JS: No external libraries, no CDN
 */

(function () {
    'use strict';

    // State & Catalog References
    let servicesMap = {};
    let rowIndexCounter = 0;

    /**
     * Format number to Indonesian Rupiah currency string
     * Example: 25000 -> "Rp 25.000"
     */
    function formatRupiah(amount) {
        const number = Math.round(parseFloat(amount) || 0);
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }

    /**
     * Clean and parse number from string/input
     */
    function parseNumber(value) {
        if (typeof value === 'number') return value;
        if (!value) return 0;
        const cleaned = value.toString().replace(/[^0-9.,-]+/g, '').replace(',', '.');
        return parseFloat(cleaned) || 0;
    }

    /**
     * Format Date to HTML5 datetime-local string (YYYY-MM-DDTHH:mm)
     */
    function toDateTimeLocalString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    /**
     * Inisialisasi katalog layanan dari window.ORDER_SERVICES
     */
    function initServicesCatalog() {
        if (Array.isArray(window.ORDER_SERVICES)) {
            window.ORDER_SERVICES.forEach(function (service) {
                servicesMap[service.id] = service;
            });
        }
    }

    /**
     * Perbarui kartu pratinjau data pelanggan terpilih
     */
    function updateCustomerInfo() {
        const select = document.getElementById('customer_id');
        const card = document.getElementById('customer-info-card');
        const nameEl = document.getElementById('customer-info-name');
        const codeEl = document.getElementById('customer-info-code');
        const phoneEl = document.getElementById('customer-info-phone');
        const addressEl = document.getElementById('customer-info-address');

        if (!select || !card) return;

        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const name = selectedOption.getAttribute('data-name') || selectedOption.textContent.trim();
            const code = selectedOption.getAttribute('data-code') || '-';
            const phone = selectedOption.getAttribute('data-phone') || '-';
            const address = selectedOption.getAttribute('data-address') || 'Tidak ada catatan alamat.';

            if (nameEl) nameEl.textContent = name;
            if (codeEl) codeEl.textContent = code;
            if (phoneEl) phoneEl.textContent = phone;
            if (addressEl) addressEl.textContent = address;

            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    }

    /**
     * Hitung ulang perkiraan waktu selesai berdasarkan layanan terlama
     */
    function recalculateEstimatedCompletion() {
        const receivedAtInput = document.getElementById('received_at');
        const estimatedInput = document.getElementById('estimated_completion_at');

        if (!receivedAtInput || !estimatedInput) return;

        // Ambil waktu masuk
        let baseDate = receivedAtInput.value ? new Date(receivedAtInput.value) : new Date();
        if (isNaN(baseDate.getTime())) baseDate = new Date();

        let maxHours = 0;
        const rowSelects = document.querySelectorAll('.service-select');
        rowSelects.forEach(function (select) {
            const serviceId = select.value;
            if (serviceId && servicesMap[serviceId]) {
                const hours = parseInt(servicesMap[serviceId].estimated_hours, 10) || 0;
                if (hours > maxHours) maxHours = hours;
            }
        });

        // Gunakan default toko jika layanan belum memiliki estimasi
        if (maxHours === 0) {
            maxHours = window.ORDER_DEFAULT_HOURS || 48;
        }

        const completionDate = new Date(baseDate.getTime() + maxHours * 60 * 60 * 1000);
        estimatedInput.value = toDateTimeLocalString(completionDate);

        // Update indikator teks
        const infoBadge = document.getElementById('estimated-duration-info');
        if (infoBadge) {
            infoBadge.textContent = `Estimasi durasi: ${maxHours} jam (${Math.round(maxHours / 24 * 10) / 10} hari)`;
        }
    }

    /**
     * Hitung ulang seluruh subtotal baris dan grand total
     */
    function recalculateTotals() {
        let subtotal = 0;

        document.querySelectorAll('#order-items-tbody tr').forEach(function (row) {
            const select = row.querySelector('.service-select');
            const qtyInput = row.querySelector('.quantity-input');
            const unitPriceDisplay = row.querySelector('.unit-price-display');
            const subtotalDisplay = row.querySelector('.row-subtotal-display');
            const unitBadge = row.querySelector('.unit-badge');

            if (!select || !qtyInput) return;

            const serviceId = select.value;
            const service = servicesMap[serviceId];

            if (service) {
                // Konfigurasi unit & step
                const isKg = service.billing_unit === 'kg';
                if (unitBadge) {
                    unitBadge.textContent = isKg ? 'Kg' : (service.billing_unit === 'package' ? 'Paket' : 'Item');
                    unitBadge.className = 'badge ' + (isKg ? 'badge-primary' : 'badge-neutral');
                }

                if (isKg) {
                    qtyInput.step = '0.01';
                    qtyInput.min = '0.01';
                    qtyInput.placeholder = 'contoh: 2.50';
                } else {
                    qtyInput.step = '1';
                    qtyInput.min = '1';
                    qtyInput.placeholder = '1';
                }

                const price = parseFloat(service.price) || 0;
                if (unitPriceDisplay) {
                    unitPriceDisplay.textContent = formatRupiah(price);
                }

                const qty = parseNumber(qtyInput.value);
                const rowTotal = Math.round(qty * price);

                if (subtotalDisplay) {
                    subtotalDisplay.textContent = formatRupiah(rowTotal);
                }

                subtotal += rowTotal;
            } else {
                if (unitPriceDisplay) unitPriceDisplay.textContent = 'Rp 0';
                if (subtotalDisplay) subtotalDisplay.textContent = 'Rp 0';
                if (unitBadge) unitBadge.textContent = '-';
            }
        });

        // Tampilkan Subtotal
        const subtotalEl = document.getElementById('summary-subtotal');
        if (subtotalEl) subtotalEl.textContent = formatRupiah(subtotal);

        // Ambil diskon dan biaya tambahan
        const discountInput = document.getElementById('discount_amount');
        const extraInput = document.getElementById('extra_charge');

        const rawDiscount = discountInput ? parseNumber(discountInput.value) : 0;
        const extraCharge = extraInput ? parseNumber(extraInput.value) : 0;

        // Validasi batas diskon kasir
        const isAdmin = !!window.ORDER_IS_ADMIN;
        const maxPercent = window.ORDER_MAX_DISCOUNT_PERCENT || 20;
        const maxCashierDiscount = Math.round((subtotal * maxPercent) / 100);

        const cashierDiscountInfo = document.getElementById('cashier-max-discount-text');
        if (cashierDiscountInfo) {
            cashierDiscountInfo.textContent = formatRupiah(maxCashierDiscount);
        }

        const discountWarning = document.getElementById('discount-warning-msg');
        if (!isAdmin && rawDiscount > maxCashierDiscount && discountWarning) {
            discountWarning.style.display = 'block';
            discountWarning.textContent = `Peringatan: Nominal diskon melebihi batas kasir (${maxPercent}% = ${formatRupiah(maxCashierDiscount)}).`;
        } else if (discountWarning) {
            discountWarning.style.display = 'none';
        }

        const grandTotal = Math.max(0, subtotal - rawDiscount + extraCharge);
        const grandTotalEl = document.getElementById('summary-grand-total');
        if (grandTotalEl) grandTotalEl.textContent = formatRupiah(grandTotal);

        recalculateInitialPayment(grandTotal);
    }

    /**
     * Tambah baris item layanan baru
     */
    function addServiceRow(initialData) {
        const tbody = document.getElementById('order-items-tbody');
        if (!tbody) return;

        const index = rowIndexCounter++;
        const row = document.createElement('tr');
        row.setAttribute('data-row-index', index);

        // Siapkan opsi select layanan
        let optionsHtml = '<option value="">-- Pilih Layanan --</option>';
        if (Array.isArray(window.ORDER_SERVICES)) {
            window.ORDER_SERVICES.forEach(function (s) {
                const isSelected = initialData && initialData.service_id == s.id ? 'selected' : '';
                const unitLabel = s.billing_unit === 'kg' ? 'Kg' : (s.billing_unit === 'package' ? 'Paket' : 'Item');
                optionsHtml += `<option value="${s.id}" ${isSelected} data-unit="${s.billing_unit}" data-price="${s.price}">${s.name} (${formatRupiah(s.price)}/${unitLabel})</option>`;
            });
        }

        const defaultQty = initialData && initialData.quantity !== undefined ? initialData.quantity : '';
        const defaultNotes = initialData && initialData.notes ? initialData.notes : '';

        row.innerHTML = `
            <td style="text-align: center; vertical-align: middle; color: var(--text-muted); font-weight: var(--font-semibold);" class="row-number">
                ${tbody.children.length + 1}
            </td>
            <td style="vertical-align: middle; min-width: 200px;">
                <select name="items[${index}][service_id]" class="form-control form-select service-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td style="text-align: center; vertical-align: middle; width: 90px;">
                <span class="badge badge-neutral unit-badge">-</span>
            </td>
            <td style="vertical-align: middle; width: 140px;">
                <input type="text"
                       name="items[${index}][quantity]"
                       class="form-control quantity-input"
                       value="${defaultQty}"
                       placeholder="1"
                       required
                       style="text-align: right; font-weight: var(--font-semibold);" />
            </td>
            <td style="text-align: right; vertical-align: middle; width: 130px; font-weight: var(--font-medium); color: var(--text-muted);" class="unit-price-display">
                Rp 0
            </td>
            <td style="text-align: right; vertical-align: middle; width: 140px; font-weight: var(--font-bold); color: var(--primary);" class="row-subtotal-display">
                Rp 0
            </td>
            <td style="vertical-align: middle; min-width: 180px;">
                <input type="text"
                       name="items[${index}][notes]"
                       class="form-control"
                       value="${defaultNotes}"
                       maxlength="255"
                       placeholder="Catatan item (opsional)" />
            </td>
            <td style="text-align: center; vertical-align: middle; width: 60px;">
                <button type="button" class="btn btn-secondary btn-icon-only btn-remove-row" title="Hapus baris item" aria-label="Hapus item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        updateRowNumbers();

        // Event listeners untuk baris baru
        const select = row.querySelector('.service-select');
        const qtyInput = row.querySelector('.quantity-input');
        const removeBtn = row.querySelector('.btn-remove-row');

        select.addEventListener('change', function () {
            recalculateTotals();
            recalculateEstimatedCompletion();
        });

        qtyInput.addEventListener('input', function () {
            // Normalisasi koma menjadi titik
            this.value = this.value.replace(',', '.');
            recalculateTotals();
        });

        removeBtn.addEventListener('click', function () {
            if (tbody.children.length > 1) {
                row.remove();
                updateRowNumbers();
                recalculateTotals();
                recalculateEstimatedCompletion();
            } else {
                alert('Pesanan harus memiliki minimal satu item layanan.');
            }
        });

        // Hitung ulang jika baris dibuat dengan data awal
        if (initialData && initialData.service_id) {
            recalculateTotals();
        }
    }

    /**
     * Perbarui nomor urut pada tabel repeater
     */
    function updateRowNumbers() {
        const rows = document.querySelectorAll('#order-items-tbody tr');
        rows.forEach(function (row, idx) {
            const numEl = row.querySelector('.row-number');
            if (numEl) numEl.textContent = idx + 1;
        });
    }

    /**
     * Setup Quick Add Customer Modal via AJAX
     */
    function initQuickCustomerModal() {
        const modal = document.getElementById('modal-quick-customer');
        const btnOpen = document.getElementById('btn-open-quick-customer');
        const btnClose = document.getElementById('btn-close-quick-customer');
        const form = document.getElementById('form-quick-customer');
        const errorContainer = document.getElementById('quick-customer-errors');
        const submitBtn = document.getElementById('btn-submit-quick-customer');

        if (!modal || !btnOpen || !form) return;

        btnOpen.addEventListener('click', function () {
            form.reset();
            if (errorContainer) {
                errorContainer.style.display = 'none';
                errorContainer.innerHTML = '';
            }
            modal.classList.add('open');
            const nameInput = document.getElementById('quick-customer-name');
            if (nameInput) setTimeout(() => nameInput.focus(), 100);
        });

        if (btnClose) {
            btnClose.addEventListener('click', function () {
                modal.classList.remove('open');
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Menyimpan...</span>';
            }

            if (errorContainer) {
                errorContainer.style.display = 'none';
                errorContainer.innerHTML = '';
            }

            const formData = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form.querySelector('input[name="_csrf"]')?.value;

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: formData
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { status: response.status, ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (result.ok && result.data && result.data.customer) {
                    const cust = result.data.customer;

                    // Sisipkan pelanggan baru ke select dropdown
                    const customerSelect = document.getElementById('customer_id');
                    if (customerSelect) {
                        const newOption = document.createElement('option');
                        newOption.value = cust.id;
                        newOption.textContent = `${cust.name} (${cust.customer_code}) - ${cust.formatted_phone || cust.phone}`;
                        newOption.setAttribute('data-name', cust.name);
                        newOption.setAttribute('data-code', cust.customer_code);
                        newOption.setAttribute('data-phone', cust.formatted_phone || cust.phone);
                        newOption.setAttribute('data-address', cust.address || '');
                        newOption.selected = true;

                        customerSelect.appendChild(newOption);
                        customerSelect.value = cust.id;
                        updateCustomerInfo();
                    }

                    modal.classList.remove('open');

                    // Notifikasi visual sukses
                    const flashToast = document.getElementById('order-form-alert');
                    if (flashToast) {
                        flashToast.className = 'alert alert-success';
                        flashToast.innerHTML = `<strong>Berhasil:</strong> Pelanggan baru <b>${cust.name} (${cust.customer_code})</b> berhasil didaftarkan dan langsung terpilih!`;
                        flashToast.style.display = 'block';
                        setTimeout(() => { flashToast.style.display = 'none'; }, 6000);
                    }
                } else {
                    // Tampilkan pesan error validasi
                    if (errorContainer) {
                        let errorMsg = 'Gagal menyimpan data pelanggan.';
                        if (result.data && result.data.errors) {
                            const errList = Object.values(result.data.errors).flat();
                            errorMsg = '<ul style="margin: 0; padding-left: var(--space-4);">' + errList.map(e => `<li>${e}</li>`).join('') + '</ul>';
                        } else if (result.data && result.data.message) {
                            errorMsg = result.data.message;
                        }
                        errorContainer.innerHTML = errorMsg;
                        errorContainer.style.display = 'block';
                    }
                }
            })
            .catch(function (err) {
                if (errorContainer) {
                    errorContainer.innerHTML = 'Terjadi kesalahan jaringan atau server saat menyimpan data pelanggan.';
                    errorContainer.style.display = 'block';
                }
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Simpan Pelanggan</span>';
                }
            });
        });
    }

    /**
     * Inisialisasi tombol pintas estimasi selesai (+1 Hari, +2 Hari, +3 Hari, Reset)
     */
    function initEstimatedCompletionPresets() {
        const receivedAtInput = document.getElementById('received_at');
        const estimatedInput = document.getElementById('estimated_completion_at');

        document.querySelectorAll('.btn-preset-hours').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const addHours = parseInt(this.getAttribute('data-hours'), 10) || 0;
                let baseDate = receivedAtInput && receivedAtInput.value ? new Date(receivedAtInput.value) : new Date();
                if (isNaN(baseDate.getTime())) baseDate = new Date();

                const targetDate = new Date(baseDate.getTime() + addHours * 60 * 60 * 1000);
                if (estimatedInput) {
                    estimatedInput.value = toDateTimeLocalString(targetDate);
                }

                const infoBadge = document.getElementById('estimated-duration-info');
                if (infoBadge) {
                    infoBadge.textContent = `Pilihan manual: +${addHours} jam (${addHours / 24} hari)`;
                }
            });
        });

        const btnResetEstimate = document.getElementById('btn-reset-service-estimate');
        if (btnResetEstimate) {
            btnResetEstimate.addEventListener('click', function () {
                recalculateEstimatedCompletion();
            });
        }
    }

    let currentGrandTotal = 0;

    /**
     * Hitung ulang ringkasan panel pembayaran awal (DP/Pelunasan)
     */
    function recalculateInitialPayment(grandTotal) {
        currentGrandTotal = grandTotal;
        const container = document.getElementById('initial-payment-container');
        if (!container) return;

        const totalDisplay = document.getElementById('init-panel-total');
        if (totalDisplay) totalDisplay.textContent = formatRupiah(grandTotal);

        const amountInput = document.getElementById('initial_payment_amount');
        const paidDisplay = document.getElementById('init-panel-paid');
        const balanceDisplay = document.getElementById('init-panel-balance');
        const overpayMsg = document.getElementById('initial-pay-overpay-msg');

        const amount = amountInput ? parseNumber(amountInput.value) : 0;

        if (paidDisplay) paidDisplay.textContent = formatRupiah(amount);

        if (amount > grandTotal) {
            if (overpayMsg) overpayMsg.style.display = 'block';
            if (balanceDisplay) balanceDisplay.textContent = formatRupiah(0);
        } else {
            if (overpayMsg) overpayMsg.style.display = 'none';
            const remaining = Math.max(0, grandTotal - amount);
            if (balanceDisplay) balanceDisplay.textContent = formatRupiah(remaining);
        }

        updateInitialCashChange();
    }

    /**
     * Hitung kembalian uang tunai pembayaran awal
     */
    function updateInitialCashChange() {
        const methodSelect = document.getElementById('initial_payment_method');
        const cashGroup = document.getElementById('initial-cash-tendered-group');
        const cashInput = document.getElementById('initial_payment_cash_tendered');
        const changeDisplay = document.getElementById('initial-cash-change-display');
        const amountInput = document.getElementById('initial_payment_amount');

        if (!methodSelect) return;

        const isCash = methodSelect.value === 'tunai';
        if (cashGroup) {
            cashGroup.style.display = isCash ? 'block' : 'none';
        }

        if (isCash && cashInput && changeDisplay && amountInput) {
            const amount = parseNumber(amountInput.value);
            const tendered = parseNumber(cashInput.value);
            const change = Math.max(0, tendered - amount);
            changeDisplay.textContent = formatRupiah(change);
        }
    }

    /**
     * Inisialisasi modul pembayaran awal (DP / Pelunasan)
     */
    function initInitialPaymentModule() {
        const toggle = document.getElementById('toggle-initial-payment');
        const container = document.getElementById('initial-payment-container');
        if (!toggle || !container) return;

        function updateToggleState() {
            const isChecked = toggle.checked;
            container.style.display = isChecked ? 'block' : 'none';
            const inputs = container.querySelectorAll('input, select');
            inputs.forEach(function (inp) {
                inp.disabled = !isChecked;
            });
            if (!isChecked) {
                const amountInput = document.getElementById('initial_payment_amount');
                if (amountInput) amountInput.value = '';
            }
            recalculateInitialPayment(currentGrandTotal);
        }

        toggle.addEventListener('change', updateToggleState);
        updateToggleState();

        const amountInput = document.getElementById('initial_payment_amount');
        if (amountInput) {
            amountInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                recalculateInitialPayment(currentGrandTotal);
            });
        }

        const methodSelect = document.getElementById('initial_payment_method');
        if (methodSelect) {
            methodSelect.addEventListener('change', function () {
                updateInitialCashChange();
            });
        }

        const cashInput = document.getElementById('initial_payment_cash_tendered');
        if (cashInput) {
            cashInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                updateInitialCashChange();
            });
        }

        // Tombol cepat Lunas (100%)
        const btnPayFull = document.getElementById('btn-pay-full');
        if (btnPayFull && amountInput) {
            btnPayFull.addEventListener('click', function () {
                amountInput.value = currentGrandTotal > 0 ? currentGrandTotal : 0;
                recalculateInitialPayment(currentGrandTotal);
            });
        }

        // Tombol cepat DP 50%
        const btnPayDp50 = document.getElementById('btn-pay-dp-50');
        if (btnPayDp50 && amountInput) {
            btnPayDp50.addEventListener('click', function () {
                amountInput.value = currentGrandTotal > 0 ? Math.round(currentGrandTotal / 2) : 0;
                recalculateInitialPayment(currentGrandTotal);
            });
        }

        // Tombol cepat nominal uang tunai
        document.querySelectorAll('.btn-init-quick-cash').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const addVal = this.getAttribute('data-add');
                const currentAmount = amountInput ? parseNumber(amountInput.value) : 0;
                if (!cashInput) return;

                if (addVal === 'exact') {
                    cashInput.value = currentAmount;
                } else {
                    const denom = parseInt(addVal, 10) || 0;
                    cashInput.value = denom;
                }
                updateInitialCashChange();
            });
        });
    }

    // Eksekusi utama saat DOM siap
    document.addEventListener('DOMContentLoaded', function () {
        initServicesCatalog();

        // Listener perubahan pelanggan
        const customerSelect = document.getElementById('customer_id');
        if (customerSelect) {
            customerSelect.addEventListener('change', updateCustomerInfo);
            updateCustomerInfo(); // Initial state
        }

        // Listener tombol tambah baris item
        const btnAddItem = document.getElementById('btn-add-service-row');
        if (btnAddItem) {
            btnAddItem.addEventListener('click', function () {
                addServiceRow();
            });
        }

        // Listener diskon & biaya tambahan
        const discountInput = document.getElementById('discount_amount');
        if (discountInput) {
            discountInput.addEventListener('input', function () {
                this.value = this.value.replace(',', '.');
                recalculateTotals();
            });
        }

        const extraInput = document.getElementById('extra_charge');
        if (extraInput) {
            extraInput.addEventListener('input', function () {
                this.value = this.value.replace(',', '.');
                recalculateTotals();
            });
        }

        const receivedAtInput = document.getElementById('received_at');
        if (receivedAtInput) {
            receivedAtInput.addEventListener('change', recalculateEstimatedCompletion);
        }

        // Inisialisasi modal, presets, & pembayaran awal
        initQuickCustomerModal();
        initEstimatedCompletionPresets();
        initInitialPaymentModule();

        // Render baris awal (dari old data jika validasi gagal, atau 1 baris kosong)
        if (Array.isArray(window.ORDER_OLD_ITEMS) && window.ORDER_OLD_ITEMS.length > 0) {
            window.ORDER_OLD_ITEMS.forEach(function (item) {
                addServiceRow(item);
            });
        } else {
            addServiceRow();
        }

        recalculateTotals();
    });
})();
