document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll("[data-page]");
    const content = document.getElementById("adminContent");

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

                        const activeLink = document.querySelector("[data-page].active");
                        if (activeLink) {
                            loadPage(activeLink.getAttribute("data-page"), activeLink);
                        }
                    })
                    .catch(error => {
                        alert(error.message || "Vendor approval action failed.");
                        this.disabled = false;
                    });
            });
        });
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
            .then(data => {
                content.innerHTML = data;

                links.forEach(item => item.classList.remove("active"));
                activeLink.classList.add("active");
                bindVendorApprovalEvents();
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
