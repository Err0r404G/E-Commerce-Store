function formatMoney(value) {
    return '$' + Number(value || 0).toFixed(2);
}

const couponButton = document.getElementById('apply-coupon');
if (couponButton) {
    couponButton.addEventListener('click', function () {
        const code = document.getElementById('coupon-code').value.trim();
        const subtotal = Number(document.getElementById('subtotal').dataset.value || 0);
        const message = document.getElementById('coupon-message');

        if (!code) {
            message.textContent = 'Enter a coupon code first.';
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/E-Commerce-Store/api/customer_coupon_check.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            const response = JSON.parse(xhr.responseText || '{}');
            if (!response.valid) {
                message.textContent = response.message || 'Coupon is not valid.';
                document.getElementById('discount').textContent = formatMoney(0);
                updateCheckoutTotal();
                return;
            }

            const discount = subtotal * (Number(response.discount_pct) / 100);
            document.getElementById('discount').dataset.value = discount;
            document.getElementById('discount').textContent = formatMoney(discount);
            message.textContent = response.message;
            updateCheckoutTotal();
        };
        xhr.send('code=' + encodeURIComponent(code));
    });
}

const zoneSelect = document.getElementById('zone-select');
if (zoneSelect) {
    zoneSelect.addEventListener('change', function () {
        const selected = zoneSelect.options[zoneSelect.selectedIndex];
        const fee = Number(selected.dataset.fee || 0);
        const days = selected.dataset.days || '3';
        document.getElementById('delivery-fee').value = fee;
        document.getElementById('delivery-display').textContent = formatMoney(fee);
        document.getElementById('delivery-window').textContent = 'Estimated delivery window: ' + days + ' day(s).';
        updateCheckoutTotal();
    });
}

const savedAddressSelect = document.getElementById('saved-address-select');
if (savedAddressSelect) {
    savedAddressSelect.addEventListener('change', function () {
        const selected = savedAddressSelect.options[savedAddressSelect.selectedIndex];
        const target = document.getElementById('shipping-address');
        if (target && selected.dataset.address) {
            target.value = selected.dataset.address;
        }
    });
}

function updateCheckoutTotal() {
    const subtotalNode = document.getElementById('subtotal');
    if (!subtotalNode) {
        return;
    }
    const subtotal = Number(subtotalNode.dataset.value || 0);
    const discount = Number((document.getElementById('discount') || {}).dataset?.value || 0);
    const delivery = Number(document.getElementById('delivery-fee')?.value || 0);
    document.getElementById('checkout-total').textContent = formatMoney(Math.max(0, subtotal - discount + delivery));
}

document.querySelectorAll('.live-order-status, .status-line').forEach(function (node) {
    const orderId = node.dataset.orderId;
    if (!orderId) {
        return;
    }

    function pollStatus() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/E-Commerce-Store/api/customer_order_status.php?order_id=' + encodeURIComponent(orderId));
        xhr.onload = function () {
            const response = JSON.parse(xhr.responseText || '{}');
            if (response.ok && response.status_label) {
                document.querySelectorAll('.live-order-status, #live-status').forEach(function (target) {
                    target.textContent = response.status_label;
                });
            }
        };
        xhr.send();
    }

    pollStatus();
    setInterval(pollStatus, 10000);
});
