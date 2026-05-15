document.addEventListener("DOMContentLoaded", function () {
    const content = document.getElementById("deliveryManagerContent");
    const menuLinks = document.querySelectorAll(".delivery-manager-menu a");
    const ajaxLinks = document.querySelectorAll("[data-delivery-page]");

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
        const form = document.getElementById("deliverySettingsForm");
        const message = document.getElementById("deliverySettingsMessage");
        const imageInput = document.getElementById("deliveryProfileImageInput");
        const logoPreview = document.getElementById("deliveryProfileLogoPreview");

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
                    showMessage(message, data.message || "Settings updated.", Boolean(data.success));

                    if (!data.success) {
                        throw new Error(data.message || "Settings update failed.");
                    }

                    const sidebarName = document.getElementById("deliverySidebarName");
                    const sidebarLogo = document.getElementById("deliverySidebarLogo");

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
                    showMessage(message, error.message || "Settings update failed.", false);
                })
                .finally(() => {
                    submitButton.disabled = false;
                });
        });
    }

    function bindAgentEvents() {
        const form = document.getElementById("deliveryAgentForm");
        const message = document.getElementById("deliveryAgentMessage");
        const resetButton = document.getElementById("deliveryAgentReset");
        const agentsLink = document.querySelector("[data-delivery-page*='deliveryAgentsAjax']");

        function resetForm() {
            if (!form) {
                return;
            }

            form.reset();
            document.getElementById("deliveryAgentId").value = "";
            document.getElementById("deliveryAgentActive").value = "1";
        }

        function reloadAgents() {
            loadPage("/E-Commerce-Store/index.php?page=deliveryAgentsAjax", agentsLink);
        }

        if (resetButton) {
            resetButton.addEventListener("click", resetForm);
        }

        document.querySelectorAll("[data-agent-edit]").forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById("deliveryAgentId").value = this.dataset.agentId || "";
                document.getElementById("deliveryAgentName").value = this.dataset.name || "";
                document.getElementById("deliveryAgentPhone").value = this.dataset.phone || "";
                document.getElementById("deliveryAgentVehicle").value = this.dataset.vehicleType || "";
                document.getElementById("deliveryAgentActive").value = this.dataset.active === "1" ? "1" : "";
                form.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });

        document.querySelectorAll("[data-agent-toggle]").forEach(button => {
            button.addEventListener("click", function () {
                const formData = new FormData();
                formData.append("agent_action", "toggle");
                formData.append("agent_id", this.dataset.agentId);

                this.disabled = true;

                fetch("/E-Commerce-Store/index.php?page=deliveryAgentAction", {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || "Agent status update failed.");
                        }

                        reloadAgents();
                    })
                    .catch(error => {
                        showMessage(message, error.message || "Agent status update failed.", false);
                        this.disabled = false;
                    });
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

            fetch("/E-Commerce-Store/index.php?page=deliveryAgentAction", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || "Delivery agent save failed.");
                    }

                    reloadAgents();
                })
                .catch(error => {
                    showMessage(message, error.message || "Delivery agent save failed.", false);
                    submitButton.disabled = false;
                });
        });
    }

    function bindLoadedPage() {
        bindSettingsEvents();
        bindAgentEvents();
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
            loadPage(this.dataset.deliveryPage, this);
        });
    });
});
