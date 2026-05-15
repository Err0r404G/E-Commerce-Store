document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll("[data-page]");
    const menuLinks = document.querySelectorAll(".admin-menu a");
    const content = document.getElementById("adminContent");

    function loadActivePage() {
        const activeLink = document.querySelector("[data-page].active");

        if (activeLink) {
            loadPage(activeLink.getAttribute("data-page"), activeLink);
        }
    }

    function bindVendorApprovalEvents() {
        const search = document.getElementById("vendorApprovalSearch");
        const modal = document.getElementById("sellerActionModal");
        const modalTitle = document.getElementById("sellerActionTitle");
        const modalText = document.getElementById("sellerActionText");
        const actionForm = document.getElementById("sellerActionForm");
        const actionVendorId = document.getElementById("sellerActionVendorId");
        const actionType = document.getElementById("sellerActionType");
        const actionReason = document.getElementById("sellerActionReason");
        const cancelAction = document.getElementById("cancelSellerAction");

        function closeSellerActionModal() {
            if (modal) {
                modal.hidden = true;
            }

            document.body.classList.remove("modal-open");

            if (actionForm) {
                actionForm.reset();
            }
        }

        function openSellerActionModal(button) {
            const action = button.dataset.approvalAction;
            const labels = {
                reject: {
                    title: "Reject Seller",
                    text: "Add the reason for rejecting this seller registration."
                },
                suspend: {
                    title: "Suspend Seller",
                    text: "Add the reason for suspending this seller account."
                }
            };

            if (!modal || !actionVendorId || !actionType || !actionReason) {
                return;
            }

            actionVendorId.value = button.dataset.vendorId;
            actionType.value = action;
            modalTitle.textContent = labels[action]?.title || "Seller Action";
            modalText.textContent = labels[action]?.text || "Add a reason before continuing.";
            modal.hidden = false;
            document.body.classList.add("modal-open");
            actionReason.focus();
        }

        function submitSellerAction(vendorId, action, reason, sourceButton) {
            const formData = new FormData();
            formData.append("vendor_id", vendorId);
            formData.append("action", action);
            formData.append("reason", reason || "");

            if (sourceButton) {
                sourceButton.disabled = true;
            }

            fetch("/E-Commerce-Store/index.php?page=vendorApprovalAction", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || "Action failed.");
                    }

                    closeSellerActionModal();
                    loadActivePage();
                })
                .catch(error => {
                    alert(error.message || "Seller action failed.");
                    if (sourceButton) {
                        sourceButton.disabled = false;
                    }
                });
        }

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();
                document.querySelectorAll("[data-vendor-row]").forEach(row => {
                    row.style.display = row.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

        document.querySelectorAll("[data-approval-action]").forEach(button => {
            button.addEventListener("click", function () {
                if (this.dataset.requiresReason === "true") {
                    openSellerActionModal(this);
                    return;
                }

                submitSellerAction(this.dataset.vendorId, this.dataset.approvalAction, "", this);
            });
        });

        if (cancelAction) {
            cancelAction.addEventListener("click", closeSellerActionModal);
        }

        if (modal) {
            modal.addEventListener("click", function (e) {
                if (e.target === modal) {
                    closeSellerActionModal();
                }
            });
        }

        if (actionForm) {
            actionForm.addEventListener("submit", function (e) {
                e.preventDefault();
                const reason = actionReason.value.trim();

                if (reason === "") {
                    actionReason.focus();
                    return;
                }

                const submitButton = actionForm.querySelector("[type='submit']");
                submitSellerAction(actionVendorId.value, actionType.value, reason, submitButton);
            });
        }
    }

    function bindCategoryManagementEvents() {
        const search = document.getElementById("categorySearch");
        const pagination = document.getElementById("categoryPagination");
        const paginationInfo = document.getElementById("categoryPaginationInfo");
        const pageNumbers = document.getElementById("categoryPageNumbers");
        const prevPageButton = document.querySelector("[data-category-page-prev]");
        const nextPageButton = document.querySelector("[data-category-page-next]");
        const modal = document.getElementById("categoryModal");
        const modalTitle = document.getElementById("categoryModalTitle");
        const modalText = document.getElementById("categoryModalText");
        const deleteModal = document.getElementById("categoryDeleteModal");
        const deleteIdInput = document.getElementById("categoryDeleteId");
        const cancelDeleteButton = document.getElementById("cancelCategoryDelete");
        const confirmDeleteButton = document.getElementById("confirmCategoryDelete");
        const form = document.getElementById("categoryForm");
        const showFormButton = document.getElementById("showCategoryForm");
        const cancelFormButton = document.getElementById("cancelCategoryForm");
        const actionInput = document.getElementById("categoryAction");
        const idInput = document.getElementById("categoryId");
        const nameInput = document.getElementById("categoryName");
        const descriptionInput = document.getElementById("categoryDescription");
        const parentInput = document.getElementById("categoryParent");
        const feedback = document.getElementById("categoryFeedback");
        const submitButton = document.getElementById("categorySubmitButton");
        const rowsPerPage = 5;
        let currentCategoryPage = 1;
        let filteredCategoryRows = Array.from(document.querySelectorAll("[data-category-row]"));
        let pendingDeleteButton = null;

        function setCategoryFeedback(message, type = "error") {
            if (!feedback) {
                return;
            }

            feedback.textContent = message;
            feedback.className = `category-feedback ${type}`;
            feedback.hidden = message === "";
        }

        function setModalMode(title, text, buttonText) {
            if (modalTitle) {
                modalTitle.textContent = title;
            }

            if (modalText) {
                modalText.textContent = text;
            }

            if (submitButton) {
                const label = submitButton.querySelector("span");
                if (label) {
                    label.textContent = buttonText;
                }
            }
        }

        function resetForm() {
            if (!form) {
                return;
            }

            form.reset();
            actionInput.value = "add";
            idInput.value = "";
            parentInput.value = "";
            setModalMode("Add New Category", "Define a new product classification for your store's hierarchy.", "Create Category");
            if (modal) {
                modal.hidden = true;
            }
            document.body.classList.remove("modal-open");
        }

        function openForm() {
            if (!form || !modal) {
                return;
            }

            modal.hidden = false;
            document.body.classList.add("modal-open");
            nameInput.focus();
        }

        function closeDeleteModal() {
            if (deleteModal) {
                deleteModal.hidden = true;
            }

            document.body.classList.remove("modal-open");
            pendingDeleteButton = null;
            if (deleteIdInput) {
                deleteIdInput.value = "";
            }
        }

        function openDeleteModal(button) {
            if (!deleteModal || !deleteIdInput) {
                return;
            }

            pendingDeleteButton = button;
            deleteIdInput.value = button.dataset.categoryId || "";
            deleteModal.hidden = false;
            document.body.classList.add("modal-open");
            if (confirmDeleteButton) {
                confirmDeleteButton.focus();
            }
        }

        function filterCategoryRows() {
            const term = search ? search.value.trim().toLowerCase() : "";

            filteredCategoryRows = Array.from(document.querySelectorAll("[data-category-row]"))
                .filter(row => row.dataset.search.includes(term));
            currentCategoryPage = 1;
            renderCategoryPage();
        }

        function renderCategoryPage() {
            const rows = Array.from(document.querySelectorAll("[data-category-row]"));
            const totalRows = filteredCategoryRows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));

            if (currentCategoryPage > totalPages) {
                currentCategoryPage = totalPages;
            }

            const start = (currentCategoryPage - 1) * rowsPerPage;
            const visibleRows = filteredCategoryRows.slice(start, start + rowsPerPage);

            rows.forEach(row => {
                row.style.display = visibleRows.includes(row) ? "" : "none";
            });

            if (paginationInfo) {
                const visibleCount = totalRows === 0 ? 0 : Math.min(start + rowsPerPage, totalRows);
                paginationInfo.textContent = `Showing ${visibleCount} of ${totalRows} categor${totalRows === 1 ? "y" : "ies"}`;
            }

            if (pagination) {
                pagination.hidden = totalRows <= rowsPerPage;
            }

            if (prevPageButton) {
                prevPageButton.disabled = currentCategoryPage <= 1;
            }

            if (nextPageButton) {
                nextPageButton.disabled = currentCategoryPage >= totalPages;
            }

            if (pageNumbers) {
                pageNumbers.innerHTML = "";

                for (let page = 1; page <= totalPages; page++) {
                    const button = document.createElement("button");
                    button.type = "button";
                    button.textContent = page;
                    button.className = page === currentCategoryPage ? "active" : "";
                    button.addEventListener("click", function () {
                        currentCategoryPage = page;
                        renderCategoryPage();
                    });
                    pageNumbers.appendChild(button);
                }
            }
        }

        if (search) {
            search.addEventListener("input", filterCategoryRows);
        }

        if (prevPageButton) {
            prevPageButton.addEventListener("click", function () {
                if (currentCategoryPage > 1) {
                    currentCategoryPage--;
                    renderCategoryPage();
                }
            });
        }

        if (nextPageButton) {
            nextPageButton.addEventListener("click", function () {
                const totalPages = Math.max(1, Math.ceil(filteredCategoryRows.length / rowsPerPage));

                if (currentCategoryPage < totalPages) {
                    currentCategoryPage++;
                    renderCategoryPage();
                }
            });
        }

        renderCategoryPage();

        if (showFormButton && form) {
            showFormButton.addEventListener("click", function () {
                setCategoryFeedback("");
                resetForm();
                openForm();
            });
        }

        if (cancelFormButton) {
            cancelFormButton.addEventListener("click", resetForm);
        }

        if (modal) {
            modal.addEventListener("click", function (e) {
                if (e.target === modal) {
                    resetForm();
                }
            });
        }

        document.querySelectorAll("[data-category-edit]").forEach(button => {
            button.addEventListener("click", function () {
                if (!form) {
                    return;
                }

                setCategoryFeedback("");
                actionInput.value = "update";
                idInput.value = this.dataset.categoryId || "";
                nameInput.value = this.dataset.categoryName || "";
                descriptionInput.value = this.dataset.categoryDescription || "";
                parentInput.value = this.dataset.parentId || "";
                setModalMode(
                    this.dataset.parentId ? "Rename Subcategory" : "Rename Category",
                    "Update the name or description without changing assigned products.",
                    "Save Changes"
                );
                openForm();
            });
        });

        document.querySelectorAll("[data-category-add-child]").forEach(button => {
            button.addEventListener("click", function () {
                if (!form) {
                    return;
                }

                setCategoryFeedback("");
                form.reset();
                actionInput.value = "add";
                idInput.value = "";
                parentInput.value = this.dataset.parentId || "";
                setModalMode(
                    "Add Subcategory",
                    `Create a subcategory under ${this.dataset.parentName || "this category"}.`,
                    "Create Subcategory"
                );
                openForm();
            });
        });

        document.querySelectorAll("[data-category-delete]").forEach(button => {
            button.addEventListener("click", function () {
                const productCount = Number(this.dataset.productCount || 0);
                const childCount = Number(this.dataset.childCount || 0);

                if (productCount > 0) {
                    setCategoryFeedback("Delete blocked: this category has products assigned to it.");
                    return;
                }

                if (childCount > 0) {
                    setCategoryFeedback("Delete blocked: remove or rename its subcategories first.");
                    return;
                }

                setCategoryFeedback("");
                openDeleteModal(this);
            });
        });

        if (cancelDeleteButton) {
            cancelDeleteButton.addEventListener("click", closeDeleteModal);
        }

        if (deleteModal) {
            deleteModal.addEventListener("click", function (e) {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        }

        if (confirmDeleteButton) {
            confirmDeleteButton.addEventListener("click", function () {
                const formData = new FormData();
                formData.append("category_action", "delete");
                formData.append("category_id", deleteIdInput ? deleteIdInput.value : "");

                this.disabled = true;
                if (pendingDeleteButton) {
                    pendingDeleteButton.disabled = true;
                }

                fetch("/E-Commerce-Store/index.php?page=categoryAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Delete failed.");
                        }

                        closeDeleteModal();
                        loadActivePage();
                    })
                    .catch(error => {
                        setCategoryFeedback(error.message || "Category delete failed.");
                        this.disabled = false;
                        if (pendingDeleteButton) {
                            pendingDeleteButton.disabled = false;
                        }
                        closeDeleteModal();
                    });
            });
        }

        if (form) {
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const submitButton = form.querySelector("[type='submit']");
                const formData = new FormData(form);

                submitButton.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=categoryAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Category save failed.");
                        }

                        loadActivePage();
                    })
                    .catch(error => {
                        setCategoryFeedback(error.message || "Category save failed.");
                        submitButton.disabled = false;
                    });
            });
        }
    }

    function bindDisputeEvents() {
        const search = document.getElementById("disputeSearch");
        const rows = document.querySelectorAll("[data-dispute-row]");
        const actionForm = document.getElementById("disputeActionForm");
        const selectedId = document.getElementById("selectedDisputeId");
        const selectedStatus = document.getElementById("selectedDisputeStatus");
        const selectedTitle = document.getElementById("selectedDisputeTitle");
        const selectedDescription = document.getElementById("selectedDisputeDescription");
        const selectedCustomer = document.getElementById("selectedDisputeCustomer");
        const selectedSeller = document.getElementById("selectedDisputeSeller");
        const selectedDate = document.getElementById("selectedDisputeDate");
        const selectedTotal = document.getElementById("selectedDisputeTotal");
        const selectedNote = document.getElementById("selectedDisputeNote");
        const selectedNoteInput = document.getElementById("selectedDisputeNoteInput");
        const feedback = document.getElementById("disputeActionFeedback");
        let submitAction = "resolve";

        function setFeedback(message, isError = false) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || "";
            feedback.classList.toggle("error", Boolean(isError));
        }

        function selectRow(row) {
            if (!row) {
                return;
            }

            rows.forEach(item => item.classList.remove("active"));
            row.classList.add("active");

            selectedId.value = row.dataset.disputeId || "";
            selectedStatus.textContent = row.dataset.status || "Selected case";
            selectedTitle.textContent = `Case #${row.dataset.disputeId || ""} · Order #${row.dataset.orderId || ""}`;
            selectedDescription.textContent = row.dataset.description || "No dispute description provided.";
            selectedCustomer.textContent = row.dataset.customer || "Unknown customer";
            selectedSeller.textContent = row.dataset.seller || "Unknown seller";
            selectedDate.textContent = row.dataset.created || "N/A";
            selectedTotal.textContent = `BDT ${row.dataset.orderTotal || "0.00"}`;
            selectedNote.textContent = row.dataset.adminNote || "No resolution note yet.";
            selectedNoteInput.value = row.dataset.adminNote || "";
            setFeedback("");
        }

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();

                rows.forEach(row => {
                    row.style.display = row.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

        rows.forEach(row => {
            row.addEventListener("click", function () {
                selectRow(this);
            });
        });

        if (rows.length > 0) {
            selectRow(rows[0]);
        }

        document.querySelectorAll("[data-dispute-submit-action]").forEach(button => {
            button.addEventListener("click", function () {
                submitAction = this.dataset.disputeSubmitAction;
            });
        });

        if (actionForm) {
            actionForm.addEventListener("submit", function (e) {
                e.preventDefault();

                if (!selectedId.value) {
                    setFeedback("Select a dispute first.", true);
                    return;
                }

                if (submitAction === "resolve" && selectedNoteInput.value.trim().length < 5) {
                    setFeedback("Write a resolution note before closing this dispute.", true);
                    selectedNoteInput.focus();
                    return;
                }

                const formData = new FormData(actionForm);
                formData.append("action", submitAction);
                setFeedback("Saving...");
                actionForm.querySelectorAll("button").forEach(button => {
                    button.disabled = true;
                });

                fetch("/E-Commerce-Store/index.php?page=disputeAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Dispute action failed.");
                        }

                        loadActivePage();
                    })
                    .catch(error => {
                        setFeedback(error.message || "Dispute action failed.", true);
                        actionForm.querySelectorAll("button").forEach(button => {
                            button.disabled = false;
                        });
                    });
            });
        }
    }

    function bindAccountManagementEvents() {
        const search = document.getElementById("accountSearch");
        const feedback = document.getElementById("accountFeedback");
        const deliveryModal = document.getElementById("deliveryManagerModal");
        const deliveryForm = document.getElementById("deliveryManagerForm");
        const showDeliveryForm = document.getElementById("showDeliveryManagerForm");
        const cancelDeliveryForm = document.getElementById("cancelDeliveryManagerForm");

        function setAccountFeedback(message, type = "error") {
            if (!feedback) {
                return;
            }

            feedback.textContent = message;
            feedback.className = `category-feedback ${type}`;
            feedback.hidden = message === "";
        }

        function closeDeliveryManagerModal() {
            if (deliveryModal) {
                deliveryModal.hidden = true;
            }

            document.body.classList.remove("modal-open");

            if (deliveryForm) {
                deliveryForm.reset();
            }
        }

        function openDeliveryManagerModal() {
            if (!deliveryModal || !deliveryForm) {
                return;
            }

            setAccountFeedback("");
            deliveryModal.hidden = false;
            document.body.classList.add("modal-open");
            const firstInput = deliveryForm.querySelector("input");
            if (firstInput) {
                firstInput.focus();
            }
        }

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();

                document.querySelectorAll("[data-account-row]").forEach(row => {
                    row.style.display = row.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

        document.querySelectorAll("[data-account-action]").forEach(button => {
            button.addEventListener("click", function () {
                const formData = new FormData();
                formData.append("user_id", this.dataset.accountId);
                formData.append("role", this.dataset.accountRole);
                formData.append("action", this.dataset.accountAction);

                setAccountFeedback("");
                this.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=adminAccountAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Account action failed.");
                        }

                        loadActivePage();
                    })
                    .catch(error => {
                        setAccountFeedback(error.message || "Account action failed.");
                        this.disabled = false;
                    });
            });
        });

        if (showDeliveryForm) {
            showDeliveryForm.addEventListener("click", openDeliveryManagerModal);
        }

        if (cancelDeliveryForm) {
            cancelDeliveryForm.addEventListener("click", closeDeliveryManagerModal);
        }

        if (deliveryModal) {
            deliveryModal.addEventListener("click", function (e) {
                if (e.target === deliveryModal) {
                    closeDeliveryManagerModal();
                }
            });
        }

        if (deliveryForm) {
            deliveryForm.addEventListener("submit", function (e) {
                e.preventDefault();

                const submitButton = deliveryForm.querySelector("[type='submit']");
                const formData = new FormData(deliveryForm);

                setAccountFeedback("");
                submitButton.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=createDeliveryManagerAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Delivery manager could not be created.");
                        }

                        closeDeliveryManagerModal();
                        loadActivePage();
                    })
                    .catch(error => {
                        setAccountFeedback(error.message || "Delivery manager could not be created.");
                        submitButton.disabled = false;
                    });
            });
        }
    }

    function bindProductManagementEvents() {
        const search = document.getElementById("productSearch");
        const categoryFilter = document.getElementById("productCategoryFilter");
        const sellerFilter = document.getElementById("productSellerFilter");
        const feedback = document.getElementById("productFeedback");
        const countText = document.getElementById("productCountText");

        function setProductFeedback(message, type = "error") {
            if (!feedback) {
                return;
            }

            feedback.textContent = message;
            feedback.className = `category-feedback ${type}`;
            feedback.hidden = message === "";
        }

        function filterProducts() {
            const term = search ? search.value.trim().toLowerCase() : "";
            const categoryId = categoryFilter ? categoryFilter.value : "";
            const sellerId = sellerFilter ? sellerFilter.value : "";
            let visibleCount = 0;

            document.querySelectorAll("[data-product-row]").forEach(row => {
                const matchesSearch = row.dataset.search.includes(term);
                const matchesCategory = categoryId === "" || row.dataset.categoryId === categoryId;
                const matchesSeller = sellerId === "" || row.dataset.sellerId === sellerId;
                const isVisible = matchesSearch && matchesCategory && matchesSeller;

                row.style.display = isVisible ? "" : "none";
                if (isVisible) {
                    visibleCount++;
                }
            });

            if (countText) {
                countText.textContent = `Showing ${visibleCount} product${visibleCount === 1 ? "" : "s"}`;
            }
        }

        [search, categoryFilter, sellerFilter].forEach(control => {
            if (control) {
                control.addEventListener("input", filterProducts);
                control.addEventListener("change", filterProducts);
            }
        });

        document.querySelectorAll("[data-product-status-action]").forEach(button => {
            button.addEventListener("click", function () {
                const formData = new FormData();
                formData.append("action", this.dataset.productStatusAction || "");
                formData.append("product_id", this.dataset.productId || "");

                setProductFeedback("");
                this.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=adminProductAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Product listing could not be removed.");
                        }

                        loadActivePage();
                    })
                    .catch(error => {
                        setProductFeedback(error.message || "Product status could not be updated.");
                        this.disabled = false;
                    });
            });
        });

        filterProducts();
    }

    function bindOrderManagementEvents() {
        const search = document.getElementById("orderSearch");
        const statusFilter = document.getElementById("orderStatusFilter");
        const sellerFilter = document.getElementById("orderSellerFilter");
        const customerFilter = document.getElementById("orderCustomerFilter");
        const countText = document.getElementById("orderCountText");

        function filterOrders() {
            const term = search ? search.value.trim().toLowerCase() : "";
            const status = statusFilter ? statusFilter.value : "";
            const sellerId = sellerFilter ? sellerFilter.value : "";
            const customerId = customerFilter ? customerFilter.value : "";
            let visibleCount = 0;

            document.querySelectorAll("[data-order-row]").forEach(row => {
                const matchesSearch = row.dataset.search.includes(term);
                const matchesStatus = status === "" || row.dataset.status === status;
                const matchesSeller = sellerId === "" || row.dataset.sellerIds.includes(`,${sellerId},`);
                const matchesCustomer = customerId === "" || row.dataset.customerId === customerId;
                const isVisible = matchesSearch && matchesStatus && matchesSeller && matchesCustomer;

                row.style.display = isVisible ? "" : "none";
                if (isVisible) {
                    visibleCount++;
                }
            });

            if (countText) {
                countText.textContent = `Showing ${visibleCount} order${visibleCount === 1 ? "" : "s"}`;
            }
        }

        [search, statusFilter, sellerFilter, customerFilter].forEach(control => {
            if (control) {
                control.addEventListener("input", filterOrders);
                control.addEventListener("change", filterOrders);
            }
        });

        filterOrders();
    }

    document.addEventListener("keydown", function (e) {
        const modal = document.getElementById("categoryModal");
        const sellerModal = document.getElementById("sellerActionModal");
        const categoryDeleteModal = document.getElementById("categoryDeleteModal");
        const deliveryModal = document.getElementById("deliveryManagerModal");

        if (e.key === "Escape" && sellerModal && !sellerModal.hidden) {
            sellerModal.hidden = true;
            document.body.classList.remove("modal-open");
        }

        if (e.key === "Escape" && categoryDeleteModal && !categoryDeleteModal.hidden) {
            categoryDeleteModal.hidden = true;
            document.body.classList.remove("modal-open");
        }

        if (e.key === "Escape" && deliveryModal && !deliveryModal.hidden) {
            deliveryModal.hidden = true;
            document.body.classList.remove("modal-open");
        }

        if (e.key === "Escape" && modal && !modal.hidden) {
            modal.hidden = true;
            document.body.classList.remove("modal-open");
        }
    });

    function loadPage(pageUrl, activeLink) {
        content.innerHTML = "<div class=\"admin-loading\">Loading...</div>";

        fetch(pageUrl, { credentials: "same-origin" })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Page failed to load.");
                }

                return response.text();
            })
            .then(data => {
                content.innerHTML = data;

                menuLinks.forEach(item => item.classList.remove("active"));
                activeLink.classList.add("active");
                bindVendorApprovalEvents();
                bindCategoryManagementEvents();
                bindAccountManagementEvents();
                bindProductManagementEvents();
                bindOrderManagementEvents();
                bindDisputeEvents();
            })
            .catch(error => {
                content.innerHTML = "<p class=\"admin-error\">Page failed to load.</p>";
                console.log(error);
            });
    }

    links.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();

            const pageUrl = this.getAttribute("data-page");
            loadPage(pageUrl, this);
        });
    });
});
