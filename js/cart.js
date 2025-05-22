  function updateSelectedTotal() {
        let total = 0;
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            total += parseFloat(cb.getAttribute('data-price'));
        });
        document.getElementById('grandTotal').textContent = total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    document.querySelectorAll('.select-all-checkbox').forEach(selectAll => {
        selectAll.addEventListener('change', function() {
            const table = this.closest('.order-card').querySelector('table');
            if (!table) return;
            table.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelectedTotal();
        });
    });

    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            updateSelectedTotal();
            updatePlaceOrderBtn();
            const table = this.closest('table');
            if (!table) return;
            const all = table.querySelectorAll('.item-checkbox');
            const allChecked = Array.from(all).every(x => x.checked);
            const selectAll = table.closest('.order-card').querySelector('.select-all-checkbox');
            if (selectAll) selectAll.checked = allChecked;
        });
    });

    function removeFromCart(orderId, productId, size, btn) {
        if (!confirm('Are you sure you want to remove this item from your cart?')) return;
        fetch('remove_from_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `order_id=${encodeURIComponent(orderId)}&product_id=${encodeURIComponent(productId)}&size=${encodeURIComponent(size)}`
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim().toLowerCase().includes('success')) {
                const row = btn.closest('tr');
                row.parentNode.removeChild(row);
                updateSelectedTotal();
                updatePlaceOrderBtn();
            } else {
                alert('Failed to remove item.');
            }
        })
        .catch(() => alert('Error removing item.'));
    }

    document.querySelectorAll('.qty-controls').forEach(function(ctrl) {
        const minusBtn = ctrl.querySelector('.qty-minus');
        const plusBtn = ctrl.querySelector('.qty-plus');
        const input = ctrl.querySelector('.qty-input');
        const row = ctrl.closest('tr');
        const orderId = row.getAttribute('data-order-id');
        const productId = row.getAttribute('data-product-id');
        const size = row.getAttribute('data-size');

        function updateQuantity(newQty) {
            if (newQty < 1) return;
            fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `order_id=${encodeURIComponent(orderId)}&product_id=${encodeURIComponent(productId)}&size=${encodeURIComponent(size)}&quantity=${encodeURIComponent(newQty)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = data.quantity;
                    const price = parseFloat(row.querySelector('.price-cell').textContent.replace(/[^\d.]/g, ''));
                    row.querySelector('.subtotal-cell').textContent = '₱' + (price * data.quantity).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                    row.querySelector('.item-checkbox').setAttribute('data-price', (price * data.quantity));
                    updateSelectedTotal();
                    updatePlaceOrderBtn();
                    recalcGrandTotal();
                } else {
                    alert('Failed to update quantity.');
                }
            })
            .catch(() => alert('Error updating quantity.'));
        }

        minusBtn.addEventListener('click', function() {
            let val = parseInt(input.value, 10);
            if (val > 1) {
                val--;
                updateQuantity(val);
            }
        });
        plusBtn.addEventListener('click', function() {
            let val = parseInt(input.value, 10);
            val++;
            updateQuantity(val);
        });
        input.addEventListener('change', function() {
            let val = parseInt(input.value, 10);
            if (isNaN(val) || val < 1) val = 1;
            updateQuantity(val);
        });
    });

    function recalcGrandTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal-cell').forEach(cell => {
            total += parseFloat(cell.textContent.replace(/[^\d.]/g, '')) || 0;
        });
        document.getElementById('grandTotal').textContent = total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    const selectAllGlobal = document.getElementById('selectAllGlobal');
    selectAllGlobal.addEventListener('change', function() {
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.checked = selectAllGlobal.checked;
        });
        updateSelectedTotal();
        updatePlaceOrderBtn();
    });

    function updatePlaceOrderBtn() {
        const btn = document.getElementById('placeOrderBtn');
        const checked = document.querySelectorAll('.item-checkbox:checked').length > 0;
        btn.disabled = !checked;
    }
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            updatePlaceOrderBtn();
        });
    });

    updatePlaceOrderBtn();

    const addressModal = document.getElementById('addressModal');
    const closeAddressModal = document.getElementById('closeAddressModal');
    const addressForm = document.getElementById('addressForm');

    placeOrderBtn.addEventListener('click', function() {
        addressModal.style.display = 'flex';
        document.getElementById('address').focus();
    });

    closeAddressModal.addEventListener('click', function() {
        addressModal.style.display = 'none';
    });

    addressModal.addEventListener('click', function(e) {
        if (e.target === addressModal) addressModal.style.display = 'none';
    });

    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const address = document.getElementById('address').value.trim();
        const contact = document.getElementById('contact').value.trim();
        if (!address || !contact) {
            alert('Please fill in all fields.');
            return;
        }
        const selected = [];
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            const row = cb.closest('tr');
            selected.push({
                order_id: row.getAttribute('data-order-id'),
                product_id: row.getAttribute('data-product-id'),
                size: row.getAttribute('data-size')
            });
        });
        if (selected.length === 0) {
            alert('No items selected.');
            return;
        }
        const formData = new FormData();
        formData.append('address', address);
        formData.append('contact', contact);
        selected.forEach((item, idx) => {
            formData.append(`items[${idx}][order_id]`, item.order_id);
            formData.append(`items[${idx}][product_id]`, item.product_id);
            formData.append(`items[${idx}][size]`, item.size);
        });
        fetch('checkout.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim().toLowerCase().includes('success')) {
                alert('Order placed successfully!');
                window.location.reload();
            } else {
                alert('Failed to place order: ' + data);
            }
        })
        .catch(() => alert('Error placing order.'))
        .finally(() => {
            addressModal.style.display = 'none';
        });
    });