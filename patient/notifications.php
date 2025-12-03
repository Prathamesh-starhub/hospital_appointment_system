<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/notification_handler.php'; // Ensure you created this file from previous steps

Auth::requireRole('patient');
$user_id = Auth::user();

// Handle "Mark All as Read"
if (isset($_POST['mark_all_read'])) {
    NotificationHandler::markAllAsRead($user_id);
    set_flash_message("All notifications marked as read.");
    header("Location: notifications.php");
    exit();
}

// Fetch Notifications (Both read and unread, but show status)
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        /* Slight horizontal padding for small screens */
        .container { padding-left: .75rem; padding-right: .75rem; }

        /* Make header area breathe on small screens */
        .page-header {
            display: flex;
            gap: .75rem;
            align-items: center;
            justify-content: space-between;
        }

        /* Ensure mark-all button and title stack on very small screens */
        @media (max-width: 575.98px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            .page-header .btn {
                width: 100%;
            }
            .list-group { margin-top: .5rem; }
        }

        /* Improve list-group item touch target and layout on phones */
        .list-group-item {
            padding: .75rem 1rem;
            border-radius: .5rem;
            margin-bottom: .5rem;
            display: block;
        }
        .list-group-item .d-flex.w-100 {
            gap: .5rem;
        }

        /* On small screens convert each item to a compact card-like block */
        @media (max-width: 575.98px) {
            .list-group-item .d-flex.w-100 {
                flex-direction: column;
                align-items: flex-start;
            }
            .list-group-item small {
                margin-top: .35rem;
            }
            .list-group-item h5 {
                font-size: 1rem;
                margin: 0;
                display: flex;
                align-items: center;
                gap: .5rem;
            }
            .list-group-item p.mb-1 {
                margin: .45rem 0 .3rem 0;
                font-size: .95rem;
            }
            .list-group-item .text-primary {
                margin-top: .25rem;
                display: inline-flex;
                align-items: center;
                gap: .35rem;
            }
        }

        /* Differentiate read vs unread more clearly */
        .list-group-item.unread {
            background: #ffffff;
            border-left: 4px solid #0d6efd;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .list-group-item.read {
            background: #f8f9fa;
            color: #6c757d;
        }

        /* Small visual improvements */
        .navbar-brand { font-weight: 600; }
        .no-notifs { padding: 2.2rem 1rem; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">Notification Center</span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php display_flash_message(); ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Your Alerts</h4>
                <?php if(count($notifications) > 0): ?>
                    <form method="POST">
                        <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-check-double"></i> Mark all read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="list-group shadow-sm">
                <?php if(count($notifications) > 0): ?>
                    <?php foreach($notifications as $notif): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notif['is_read'] ? 'bg-light text-muted' : ''; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?php 
                                    // Icon based on type
                                    $icon = match($notif['type']) {
                                        'alert' => '<i class="fas fa-exclamation-circle text-danger"></i>',
                                        'success' => '<i class="fas fa-check-circle text-success"></i>',
                                        default => '<i class="fas fa-info-circle text-primary"></i>'
                                    };
                                    echo $icon;
                                    ?>
                                    System Notification
                                </h5>
                                <small><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></small>
                            </div>
                            <p class="mb-1 ms-4"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <?php if(!$notif['is_read']): ?>
                                <small class="text-primary ms-4"><i class="fas fa-circle" style="font-size: 8px;"></i> New</small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-center py-5">
                        <p class="text-muted mb-0">No notifications to display.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>