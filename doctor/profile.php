<?php
require_once '../includes/doctor_header.php'; // Already handles Auth & $doctor_id
require_once '../includes/file_upload_handler.php'; // For profile image

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $specialization = clean_input($_POST['specialization']);
    $qualification = clean_input($_POST['qualification']);
    $experience = clean_input($_POST['experience_years']);
    $fee = clean_input($_POST['consultation_fee']);
    $bio = clean_input($_POST['bio']);
    
    // Default to current image
    $profile_image = $doctor['profile_image'];

    // Handle Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload = FileUploadHandler::upload($_FILES['profile_image'], 'doctor_profiles');
        if ($upload['success']) {
            $profile_image = $upload['path'];
        } else {
            $error = $upload['message'];
        }
    }

    if (!isset($error)) {
        try {
            $sql = "UPDATE doctors SET specialization=?, qualification=?, experience_years=?, consultation_fee=?, bio=?, profile_image=? WHERE doctor_id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$specialization, $qualification, $experience, $fee, $bio, $profile_image, $doctor_id]);
            
            set_flash_message("Profile updated successfully!");
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
        :root{
            --card-radius: .6rem;
        }
        body {
            background: #f8f9fa;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .card { border-radius: var(--card-radius); }
        .img-thumb {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            display: block;
            margin: 0 auto;
        }
        /* Make file input full width & friendly on touch devices */
        .file-input { width: 100%; }
        .form-label { font-weight: 600; }

        /* Layout: stack columns on xs, keep side-by-side md+ */
        @media (max-width: 575.98px) {
            .profile-row { gap: .5rem; }
            .col-md-3, .col-md-9 { width: 100%; display:block; padding: .35rem 0; }
            .col-md-3 { border-bottom: 1px solid #e9ecef; padding-bottom: .75rem; margin-bottom: .5rem; }
            .img-thumb { width: 100px; height: 100px; }
            .mb-4 { margin-bottom: .85rem !important; }
            .form-control, .form-select, textarea { padding: .6rem .65rem; font-size: 1rem; }
            .btn { padding: .7rem .8rem; font-size: 1rem; }
            .d-flex.justify-content-between { flex-direction: column; gap: .6rem; }
        }

        /* Medium devices: slightly larger image and tighter form controls */
        @media (min-width: 576px) and (max-width: 991.98px) {
            .img-thumb { width: 110px; height: 110px; }
            .card-body { padding: 1rem; }
        }

        /* Desktop: maintain original spacing */
        @media (min-width: 992px) {
            .card-body { padding: 1.25rem; }
            .img-thumb { width: 120px; height: 120px; }
        }

        /* Ensure number inputs remain compact on larger screens */
        input[type="number"] { max-width: 160px; }

        /* Accessibility focus states */
        .form-control:focus, .form-select:focus, .btn:focus {
            box-shadow: 0 0 0 4px rgba(13,110,253,0.08);
            outline: none;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Professional Profile</h4>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); ?>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-4 align-items-center">
                            <div class="col-md-3 text-center">
                                <?php 
                                    $img = $doctor['profile_image'] ? '../uploads/' . $doctor['profile_image'] : '../assets/images/default_doctor.png'; 
                                ?>
                                <img src="<?php echo $img; ?>" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Update Profile Picture</label>
                                <input type="file" name="profile_image" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name (Read Only)</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($doctor['qualification']); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Experience (Yrs)</label>
                                <input type="number" name="experience_years" class="form-control" value="<?php echo $doctor['experience_years']; ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fee ($)</label>
                                <input type="number" name="consultation_fee" class="form-control" value="<?php echo $doctor['consultation_fee']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Professional Bio</label>
                            <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($doctor['bio']); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>