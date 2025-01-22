document.addEventListener("DOMContentLoaded", function () {
    // Select all notifications
    const notifications = document.querySelectorAll(".alert");

    // Set a timeout to hide and remove notifications after 5 seconds
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.classList.add("fade-out"); // Add a fade-out class
            setTimeout(() => notification.remove(), 1000); // Remove after fade-out
        }, 5000); // Wait 5 seconds before starting fade-out
    });
});