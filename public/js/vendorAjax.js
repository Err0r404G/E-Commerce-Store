document.addEventListener("DOMContentLoaded", function () {
    const content = document.getElementById("vendorContent");
    const menuLinks = document.querySelectorAll(".vendor-menu a");
    const ajaxLinks = document.querySelectorAll("[data-vendor-page]");

    if (!content) {
        return;
    }

    function showMessage(element, message, isSuccess) {
        if (!element) {
            return;
        }

        element.hidden = false;
        element.textContent = message;
        element.classList.toggle("auth-message-success", isSuccess);
        element.classList.toggle("auth-message-error", !isSuccess);
    }

    function bindSettingsEvents() {
        const form = document.getElementById("vendorSettingsForm");
        const message = document.getElementById("vendorSettingsMessage");
        const imageInput = document.getElementById("vendorProfileImageInput");
        const logoPreview = document.getElementById("vendorProfileLogoPreview");

        if (imageInput && logoPreview) {
            imageInput.addEventListener("change", function () {
                const file = imageInput.files[0];

                if (!file) {
                    return;
                }

                const imageUrl = URL.createObjectURL(file);
                logoPreview.innerHTML = "";

                const image = document.createElement("img");
                image.src = imageUrl;
                image.alt = "";
                image.onload = () => URL.revokeObjectURL(imageUrl);
                logoPreview.appendChild(image);
            });
        }

        if (!form) {
            return;
        }

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const submitButton = form.querySelector("[type='submit']");
            const formData = new FormData(form);

            submitButton.disabled = true;

            fetch(form.action, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then(response => response.json())
                .then(data => {
                    showMessage(message, data.message || "Profile updated.", Boolean(data.success));

                    if (!data.success) {
                        throw new Error(data.message || "Profile update failed.");
                    }

                    const sidebarName = document.getElementById("vendorSidebarName");
                    const sidebarLogo = document.getElementById("vendorSidebarLogo");

                    if (sidebarName && data.name) {
                        sidebarName.textContent = data.name;
                    }

                    if (sidebarLogo && data.profile_pic) {
                        sidebarLogo.innerHTML = "";
                        const image = document.createElement("img");
                        image.src = `/E-Commerce-Store/${data.profile_pic}`;
                        image.alt = "";
                        sidebarLogo.appendChild(image);
                    }

                    form.querySelectorAll("input[type='password']").forEach(input => {
                        input.value = "";
                    });
                })
                .catch(error => {
                    showMessage(message, error.message || "Profile update failed.", false);
                })
                .finally(() => {
                    submitButton.disabled = false;
                });
        });
    }

    function bindInventoryEvents() {
        const form = document.getElementById("vendorProductForm");
        const resetButton = document.getElementById("vendorProductReset");
        const search = document.getElementById("vendorInventorySearch");
        const additionalImages = document.getElementById("vendorAdditionalImages");

        function resetForm() {
            if (!form) {
                return;
            }

            form.reset();
            document.getElementById("vendorProductId").value = "";
            document.getElementById("vendorProductAvailable").checked = true;
        }

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();

                document.querySelectorAll("[data-vendor-product-row]").forEach(row => {
                    row.style.display = row.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

        if (resetButton) {
            resetButton.addEventListener("click", resetForm);
        }

        if (additionalImages) {
            additionalImages.addEventListener("change", function () {
                if (this.files.length > 4) {
                    alert("You can upload up to 4 additional images.");
                    this.value = "";
                }
            });
        }

        document.querySelectorAll("[data-product-edit]").forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById("vendorProductId").value = this.dataset.productId || "";
                document.getElementById("vendorProductName").value = this.dataset.name || "";
                document.getElementById("vendorProductDescription").value = this.dataset.description || "";
                document.getElementById("vendorProductCategory").value = this.dataset.categoryId || "";
                document.getElementById("vendorProductPrice").value = this.dataset.price || "";
                document.getElementById("vendorProductStock").value = this.dataset.stock || "";
                document.getElementById("vendorProductAvailable").checked = this.dataset.available === "1";
                form.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });

        document.querySelectorAll("[data-product-delete]").forEach(button => {
            button.addEventListener("click", function () {
                if (!confirm("Delete this product?")) {
                    return;
                }

                const formData = new FormData();
                formData.append("product_action", "delete");
                formData.append("product_id", this.dataset.productId);

                fetch("/E-Commerce-Store/index.php?page=vendorProductAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Delete failed.");
                        }

                        loadPage("/E-Commerce-Store/index.php?page=vendorInventoryAjax", document.querySelector("[data-vendor-page*='vendorInventoryAjax']"));
                    })
                    .catch(error => alert(error.message || "Delete failed."));
            });
        });

        if (!form) {
            return;
        }

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const submitButton = form.querySelector("[type='submit']");
            const formData = new FormData(form);

            submitButton.disabled = true;

            fetch("/E-Commerce-Store/index.php?page=vendorProductAction", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || "Product save failed.");
                    }

                    loadPage("/E-Commerce-Store/index.php?page=vendorInventoryAjax", document.querySelector("[data-vendor-page*='vendorInventoryAjax']"));
                })
                .catch(error => {
                    alert(error.message || "Product save failed.");
                    submitButton.disabled = false;
                });
        });
    }

    function bindCouponEvents() {
        const form = document.getElementById("vendorCouponForm");
        const resetButton = document.getElementById("vendorCouponReset");

        function resetForm() {
            if (!form) {
                return;
            }

            form.reset();
            document.getElementById("vendorCouponId").value = "";
            document.getElementById("vendorCouponActive").checked = true;
        }

        if (resetButton) {
            resetButton.addEventListener("click", resetForm);
        }

        document.querySelectorAll("[data-coupon-edit]").forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById("vendorCouponId").value = this.dataset.couponId || "";
                document.getElementById("vendorCouponCode").value = this.dataset.code || "";
                document.getElementById("vendorCouponDiscount").value = this.dataset.discount || "";
                document.getElementById("vendorCouponMaxUses").value = this.dataset.maxUses || "";
                document.getElementById("vendorCouponValidUntil").value = this.dataset.validUntil || "";
                document.getElementById("vendorCouponActive").checked = this.dataset.active === "1";
                form.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });

        document.querySelectorAll("[data-coupon-toggle]").forEach(button => {
            button.addEventListener("click", function () {
                const formData = new FormData();
                formData.append("coupon_action", "toggle");
                formData.append("coupon_id", this.dataset.couponId);

                fetch("/E-Commerce-Store/index.php?page=vendorCouponAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Coupon update failed.");
                        }

                        loadPage("/E-Commerce-Store/index.php?page=vendorCouponsAjax", document.querySelector("[data-vendor-page*='vendorCouponsAjax']"));
                    })
                    .catch(error => alert(error.message || "Coupon update failed."));
            });
        });

        if (!form) {
            return;
        }

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const submitButton = form.querySelector("[type='submit']");
            const formData = new FormData(form);

            submitButton.disabled = true;

            fetch("/E-Commerce-Store/index.php?page=vendorCouponAction", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || "Coupon save failed.");
                    }

                    loadPage("/E-Commerce-Store/index.php?page=vendorCouponsAjax", document.querySelector("[data-vendor-page*='vendorCouponsAjax']"));
                })
                .catch(error => {
                    alert(error.message || "Coupon save failed.");
                    submitButton.disabled = false;
                });
        });
    }

    function bindOrderEvents() {
        const incomingOrdersButton = document.querySelector("[data-incoming-orders]");
        const statusFilter = document.getElementById("vendorOrderStatusFilter");
        const search = document.getElementById("vendorOrderSearch");
        const ordersLink = document.querySelector("[data-vendor-page*='vendorOrdersAjax']");

        function getOrdersUrl() {
            const status = statusFilter ? statusFilter.value : "";
            const url = new URL("/E-Commerce-Store/index.php", window.location.origin);

            url.searchParams.set("page", "vendorOrdersAjax");

            if (status !== "") {
                url.searchParams.set("status", status);
            }

            return url.pathname + url.search;
        }

        function reloadOrders() {
            loadPage(getOrdersUrl(), ordersLink);
        }

        function filterOrders() {
            const term = search ? search.value.trim().toLowerCase() : "";

            document.querySelectorAll("[data-vendor-order-row]").forEach(row => {
                row.style.display = term === "" || row.dataset.search.includes(term) ? "" : "none";
            });
        }

        if (incomingOrdersButton) {
            incomingOrdersButton.addEventListener("click", function () {
                if (statusFilter) {
                    statusFilter.value = "";
                }

                reloadOrders();
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener("change", reloadOrders);
        }

        if (search) {
            search.addEventListener("input", filterOrders);
        }

        document.querySelectorAll("[data-order-confirm]").forEach(button => {
            button.addEventListener("click", function () {
                const formData = new FormData();
                formData.append("order_action", "confirm");
                formData.append("order_item_id", this.dataset.orderItemId);

                this.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=vendorOrderAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Order update failed.");
                        }

                        loadPage(getOrdersUrl(), ordersLink);
                    })
                    .catch(error => {
                        alert(error.message || "Order update failed.");
                        this.disabled = false;
                    });
            });
        });

        document.querySelectorAll("[data-order-ship-form]").forEach(form => {
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const submitButton = form.querySelector("[type='submit']");
                const formData = new FormData(form);
                formData.append("order_action", "ship");

                submitButton.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=vendorOrderAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Order ship failed.");
                        }

                        loadPage(getOrdersUrl(), ordersLink);
                    })
                    .catch(error => {
                        alert(error.message || "Order ship failed.");
                        submitButton.disabled = false;
                    });
            });
        });
    }

    function bindReviewEvents() {
        const search = document.getElementById("vendorReviewSearch");

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();

                document.querySelectorAll("[data-vendor-review-row]").forEach(row => {
                    row.style.display = row.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

        document.querySelectorAll("[data-review-reply-form]").forEach(form => {
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const submitButton = form.querySelector("[type='submit']");
                const formData = new FormData(form);

                submitButton.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=vendorReviewAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Reply save failed.");
                        }

                        loadPage("/E-Commerce-Store/index.php?page=vendorReviewsAjax", document.querySelector("[data-vendor-page*='vendorReviewsAjax']"));
                    })
                    .catch(error => {
                        alert(error.message || "Reply save failed.");
                        submitButton.disabled = false;
                    });
            });
        });
    }

    function bindReturnEvents() {
        const search = document.getElementById("vendorReturnSearch");
        const returnsLink = document.querySelector("[data-vendor-page*='vendorReturnsAjax']");

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();

                document.querySelectorAll("[data-vendor-return-card]").forEach(card => {
                    card.style.display = term === "" || card.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

        document.querySelectorAll("[data-return-action-form]").forEach(form => {
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const submitter = e.submitter;
                const formData = new FormData(form);
                formData.append("return_action", submitter ? submitter.value : "");

                if (submitter) {
                    submitter.disabled = true;
                }

                fetch("/E-Commerce-Store/index.php?page=vendorReturnAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Return request update failed.");
                        }

                        loadPage("/E-Commerce-Store/index.php?page=vendorReturnsAjax", returnsLink);
                    })
                    .catch(error => {
                        alert(error.message || "Return request update failed.");

                        if (submitter) {
                            submitter.disabled = false;
                        }
                    });
            });
        });
    }

    function bindAnalyticsEvents() {
        const periodSelect = document.getElementById("vendorAnalyticsPeriod");

        if (!periodSelect) {
            return;
        }

        periodSelect.addEventListener("change", function () {
            document.querySelectorAll("[data-analytics-period]").forEach(panel => {
                panel.hidden = panel.dataset.analyticsPeriod !== this.value;
            });
        });
    }

    function bindEarningsEvents() {
        const periodSelect = document.getElementById("vendorEarningsPeriod");

        if (!periodSelect) {
            return;
        }

        periodSelect.addEventListener("change", function () {
            document.querySelectorAll("[data-earnings-period]").forEach(panel => {
                panel.hidden = panel.dataset.earningsPeriod !== this.value;
            });
        });
    }

    function bindLoadedPage() {
        bindInventoryEvents();
        bindSettingsEvents();
        bindCouponEvents();
        bindOrderEvents();
        bindReturnEvents();
        bindReviewEvents();
        bindAnalyticsEvents();
        bindEarningsEvents();
    }

    function loadPage(pageUrl, activeLink) {
        content.innerHTML = "<div class=\"admin-loading\">Loading...</div>";

        fetch(pageUrl, { credentials: "same-origin" })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Page failed to load.");
                }

                return response.text();
            })
            .then(html => {
                content.innerHTML = html;

                menuLinks.forEach(link => link.classList.remove("active"));
                if (activeLink) {
                    activeLink.classList.add("active");
                }

                bindLoadedPage();
            })
            .catch(error => {
                content.innerHTML = "<p class=\"admin-error\">Page failed to load.</p>";
                console.log(error);
            });
    }

    ajaxLinks.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            loadPage(this.dataset.vendorPage, this);
        });
    });
});
