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
        const modal = document.getElementById("categoryModal");
        const form = document.getElementById("categoryForm");
        const showFormButton = document.getElementById("showCategoryForm");
        const cancelFormButton = document.getElementById("cancelCategoryForm");
        const actionInput = document.getElementById("categoryAction");
        const idInput = document.getElementById("categoryId");
        const nameInput = document.getElementById("categoryName");
        const descriptionInput = document.getElementById("categoryDescription");
        const parentInput = document.getElementById("categoryParent");

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

        if (search) {
            search.addEventListener("input", function () {
                const term = this.value.trim().toLowerCase();
                document.querySelectorAll("[data-category-row]").forEach(row => {
                    row.style.display = row.dataset.search.includes(term) ? "" : "none";
                });
            });
        }

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
