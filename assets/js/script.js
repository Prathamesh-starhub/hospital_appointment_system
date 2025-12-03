/**
 * MediCare Hospital System - Global Scripts
 * Handles UI interactions, AJAX polling, and utility functions.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Initialize Global UI Components
    initFlashMessages();
    initTooltips();
    
    // 2. Start Notification Poller (Only if user is logged in)
    // We check if a notification badge container exists to know if we should poll
    if (document.querySelector('.notification-badge') || document.getElementById('notification-count')) {
        startNotificationPoller();
    }

    // 3. Confirm Delete/Cancel Actions
    initConfirmations();
});

/**
 * Auto-dismiss Flash Messages after 5 seconds
 */
function initFlashMessages() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            // Bootstrap 5 native dismiss method
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Initialize Bootstrap Tooltips
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Intercept clicks on buttons with 'btn-danger' or 'confirm-action' class
 * Adds a simple browser confirmation dialog.
 */
function initConfirmations() {
    const dangerButtons = document.querySelectorAll('.btn-danger, .confirm-action');
    
    dangerButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // If it's inside a form, or a link
            const isSubmit = btn.type === 'submit';
            const isLink = btn.tagName === 'A';

            if (isSubmit || isLink) {
                const message = btn.getAttribute('data-confirm') || 'Are you sure you want to perform this action? This cannot be undone.';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            }
        });
    });
}

/**
 * Poll the server for new notifications every 30 seconds
 */
function startNotificationPoller() {
    const pollInterval = 30000; // 30 seconds
    const apiUrl = '/hospital_appointment_system/api/get_notifications.php'; // Adjust path if needed

    // Initial check
    fetchNotifications(apiUrl);

    // Interval check
    setInterval(() => {
        fetchNotifications(apiUrl);
    }, pollInterval);
}

function fetchNotifications(url) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            updateNotificationUI(data.count);
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function updateNotificationUI(count) {
    // Select elements (Assumes you add id="notification-badge" to your navbar badge)
    const badge = document.getElementById('notification-badge');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('d-none');
            badge.classList.add('bg-danger'); // Ensure it's red
        } else {
            badge.classList.add('d-none');
        }
    }
}

/**
 * Generic Helper to print a specific div (Usage: Print Medical Report)
 * @param {string} divId - The ID of the div to print
 */
function printDiv(divId) {
    const printContents = document.getElementById(divId).innerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Re-bind events if necessary (Reloading is safer)
    window.location.reload();
}

/**
 * Format Date helper
 * @param {string} dateString - YYYY-MM-DD
 * @returns {string} - Formatted date
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}
```

### Integration Instructions

1.  **Link CSS**: Add this line to the `<head>` section of **every** file (Dashboard, Login, Landing, etc.), usually after the Bootstrap CSS link:
    ```html
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Or 'assets/css/style.css' if in root -->
    ```

2.  **Link JS**: Add this line right before the closing `</body>` tag of **every** file, after the Bootstrap JS script:
    ```html
    <script src="../assets/js/script.js"></script>
    ```

3.  **Notification Badge (Navbar)**:
    In your navbar (in `dashboard.php` files), update the "Notifications" link to include the ID required by the JavaScript:
    ```html
    <a href="notifications.php" class="nav-link position-relative">
        Notifications
        <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
            0
            <span class="visually-hidden">unread messages</span>
        </span>
    </a>