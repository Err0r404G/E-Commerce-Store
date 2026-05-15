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
                const formData = new FormData();
                formData.append("vendor_id", this.dataset.vendorId);
                formData.append("action", this.dataset.approvalAction);

                this.disabled = true;

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

                        loadActivePage();
                    })
                    .catch(error => {
                        alert(error.message || "Vendor approval action failed.");
                        this.disabled = false;
                    });
            });
        });
    }

    function bindCategoryManagementEvents() {
        const search = document.getElementById("categorySearch");
        const productFilter = document.getElementById("categoryProductFilter");
        const productsCard = document.getElementById("categoryProductsCard");
        const productsMeta = document.getElementById("categoryProductsMeta");
        const productsEmpty = document.getElementById("categoryProductsEmpty");
        const pagination = document.getElementById("categoryPagination");
        const paginationInfo = document.getElementById("categoryPaginationInfo");
        const pageNumbers = document.getElementById("categoryPageNumbers");
        const prevPageButton = document.querySelector("[data-category-page-prev]");
        const nextPageButton = document.querySelector("[data-category-page-next]");
        const modal = document.getElementById("categoryModal");
        const form = document.getElementById("categoryForm");
        const showFormButton = document.getElementById("showCategoryForm");
        const cancelFormButton = document.getElementById("cancelCategoryForm");
        const actionInput = document.getElementById("categoryAction");
        const idInput = document.getElementById("categoryId");
        const nameInput = document.getElementById("categoryName");
        const descriptionInput = document.getElementById("categoryDescription");
        const parentInput = document.getElementById("categoryParent");
        const rowsPerPage = 5;
        let currentCategoryPage = 1;
        let filteredCategoryRows = Array.from(document.querySelectorAll("[data-category-row]"));

        function resetForm() {
            if (!form) {
                return;
            }

            form.reset();
            actionInput.value = "add";
            idInput.value = "";
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

        function showCategoryProducts() {
            if (!productFilter || !productsCard) {
                return;
            }

            const categoryId = productFilter.value;
            const categoryName = productFilter.options[productFilter.selectedIndex].text.trim();
            let visibleProducts = 0;

            document.querySelectorAll("[data-product-category]").forEach(product => {
                const isMatch = categoryId !== "" && product.dataset.productCategory === categoryId;
                product.hidden = !isMatch;

                if (isMatch) {
                    visibleProducts++;
                }
            });

            productsCard.hidden = categoryId === "";

            if (productsMeta && categoryId !== "") {
                productsMeta.textContent = `${visibleProducts} product${visibleProducts === 1 ? "" : "s"} found in ${categoryName}.`;
            }

            if (productsEmpty) {
                productsEmpty.hidden = categoryId === "" || visibleProducts > 0;
            }
        }

        if (search) {
            search.addEventListener("input", filterCategoryRows);
        }

        if (productFilter) {
            productFilter.addEventListener("change", showCategoryProducts);
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

                actionInput.value = "update";
                idInput.value = this.dataset.categoryId || "";
                nameInput.value = this.dataset.categoryName || "";
                descriptionInput.value = this.dataset.categoryDescription || "";
                parentInput.value = this.dataset.parentId || "";
                openForm();
            });
        });

        document.querySelectorAll("[data-category-delete]").forEach(button => {
            button.addEventListener("click", function () {
                if (!confirm("Delete this category?")) {
                    return;
                }

                const formData = new FormData();
                formData.append("category_action", "delete");
                formData.append("category_id", this.dataset.categoryId);

                this.disabled = true;

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

                        loadActivePage();
                    })
                    .catch(error => {
                        alert(error.message || "Category delete failed.");
                        this.disabled = false;
                    });
            });
        });

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
                        alert(error.message || "Category save failed.");
                        submitButton.disabled = false;
                    });
            });
        }
    }

    document.addEventListener("keydown", function (e) {
        const modal = document.getElementById("categoryModal");

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
