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

                bindSettingsEvents();
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
