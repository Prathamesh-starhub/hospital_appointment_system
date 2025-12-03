<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();

// Redirect if already logged in as doctor
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'doctor') {
    redirect('doctor/dashboard.php');
}

$db = Database::getInstance()->getConnection();
$error = '';

// Fetch Specializations for Dropdown
try {
    $specsStmt = $db->query("SELECT DISTINCT specialization FROM doctors WHERE status='active' ORDER BY specialization ASC");
    $specs = $specsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Log error in production; keep UI friendly
    $specs = [];
}

// Handle Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and clean inputs
    $specialization = isset($_POST['specialization']) ? trim($_POST['specialization']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : ''; // e.g. "Dr. John Smith"
    $doc_password = isset($_POST['doc_password']) ? $_POST['doc_password'] : ''; // plain-text password field

    if ($specialization === '' || $full_name === '' || $doc_password === '') {
        $error = "Please fill in all fields.";
    } else {
        // Find doctor row by specialization + full_name (active)
        // We select doctor-specific password (doc_password) and the linked user row (if any) to create session
        $sql = "SELECT d.doctor_id, d.user_id AS doctor_user_id, d.full_name, d.specialization, d.doc_password,
                       u.user_id AS user_user_id, u.username AS username, u.role AS role
                FROM doctors d
                LEFT JOIN users u ON d.user_id = u.user_id
                WHERE d.specialization = ? AND d.full_name = ? AND d.status = 'active'
                LIMIT 1";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([$specialization, $full_name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $row = false;
        }

        // Compare plain-text passwords (as requested). Use === for exact match.
        if ($row && isset($row['doc_password']) && $doc_password === $row['doc_password']) {
            // Successful login
            // Use the users table user_id if available; otherwise fall back to doctor-specific id
            $session_user_id = $row['user_user_id'] ? $row['user_user_id'] : ('doctor_' . $row['doctor_id']);
            $session_username = $row['username'] ? $row['username'] : $row['full_name'];
            $session_role = $row['role'] ? $row['role'] : 'doctor';

            // Set session variables
            $_SESSION['user_id'] = $session_user_id;
            $_SESSION['username'] = $session_username;
            $_SESSION['role'] = $session_role;
            $_SESSION['doctor_id'] = $row['doctor_id'];
            $_SESSION['last_activity'] = time();

            // Update last_login in users table if linked
            if ($row['user_user_id']) {
                try {
                    $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $update->execute([$row['user_user_id']]);
                } catch (PDOException $e) {
                    // ignore update failure for now
                }
            }

            redirect('doctor/dashboard.php');
            exit;
        } else {
            $error = "Invalid credentials. Check your specialization, full name and password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctor Login - <?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Portal'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 <style>
        :root {
            --card-max-width: 460px;
            --bg: #f0f2f5;
            --card-radius: 12px;
        }

        html, body {
            height: 100%;
        }

        body {
            background-color: var(--bg);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        .login-card {
            width: 100%;
            max-width: var(--card-max-width);
            padding: 2.25rem;
            border-radius: var(--card-radius);
            box-shadow: 0 6px 20px rgba(16,24,40,0.08);
            background: #fff;
            box-sizing: border-box;
        }

        .login-heading { margin-bottom: .5rem; }
        .login-sub { margin-bottom: 1.25rem; color: #6c757d; }

        .form-label { font-weight: 600; font-size: .95rem; }

        .form-control, .form-select {
            height: 48px;
            padding: .55rem .65rem;
            font-size: .98rem;
            border-radius: .5rem;
        }

        .btn-primary {
            border-radius: .6rem;
            font-weight: 600;
        }

        .alert { font-size: .95rem; padding: .6rem .75rem; border-radius: .5rem; }

        .meta-links { font-size: .95rem; color: #6c757d; }

        /* Mobile specific tweaks */
        @media (max-width: 575.98px) {
            :root { --card-max-width: 100%; }
            body { padding: .75rem; }

            .login-card {
                padding: 1rem 1rem 1.25rem;
                box-shadow: 0 3px 10px rgba(16,24,40,0.06);
            }

            .login-heading { font-size: 1.05rem; }
            .login-sub { font-size: .92rem; margin-bottom: .9rem; }

            .form-control, .form-select {
                height: 50px;
                padding: .65rem .7rem;
                font-size: 1rem;
            }

            .btn-primary {
                padding: .72rem .6rem;
                font-size: 1rem;
            }

            /* Provide extra spacing between fields for easier tapping */
            .mb-3 { margin-bottom: .85rem !important; }

            /* Make the "Back to Patient Login" link more tappable */
            .meta-links a { display: inline-block; padding: .35rem .5rem; width: 100%; text-align: center; background: transparent; border-radius: .45rem; }
            .meta-links { margin-top: .75rem; }
        }

        /* Medium screens: keep card comfortable */
        @media (min-width: 576px) and (max-width: 991.98px) {
            .login-card { padding: 1.6rem; max-width: 460px; }
            .form-control, .form-select { height: 46px; }
        }

        /* Reduce visual clutter on very large screens */
        @media (min-width: 1200px) {
            body { padding: 2rem; }
            .login-card { padding: 2.5rem; }
        }

        /* Accessibility: focus visible */
        .form-control:focus, .form-select:focus, .btn:focus {
            box-shadow: 0 0 0 4px rgba(13,110,253,0.12);
            outline: none;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <h3 class="text-primary fw-bold">Doctor Portal</h3>
        <p class="text-muted">Identify yourself to access patient records</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Specialization Dropdown -->
        <div class="mb-3">
            <label class="form-label fw-bold">Department / Specialization</label>
            <select name="specialization" class="form-select" required>
                <option value="">-- Select Department --</option>
                <?php foreach ($specs as $s): ?>
                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] === $s) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Name Textbox -->
        <div class="mb-3">
            <label class="form-label fw-bold">Doctor Full Name</label>
            <input type="text" class="form-control" name="full_name" placeholder="e.g. Dr. John Smith"
                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="form-label fw-bold">Password</label>
            <!-- NOTE: field name should match server variable: doc_password -->
            <input type="password" class="form-control" name="doc_password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Secure Login</button>
    </form>

    <div class="mt-4 text-center border-top pt-3">
        <a href="login.php" class="text-decoration-none text-secondary">← Back to Patient Login</a>
    </div>
</div>
</body>
</html>
