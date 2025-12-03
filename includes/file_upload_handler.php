<?php
require_once __DIR__ . '/../config/config.php';

class FileUploadHandler {
    
    // Allowed file types (MIME types)
    private static $allowedTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    // Max file size (5MB)
    private static $maxSize = 5 * 1024 * 1024;

    /**
     * Upload a file securely
     * @param array $file The $_FILES['input_name'] array
     * @param string $subfolder 'prescriptions' or 'doctor_profiles'
     * @return array ['success' => bool, 'path' => string, 'message' => string]
     */
    public static function upload($file, $subfolder) {
        // 1. Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed with error code: ' . $file['error']];
        }

        // 2. Validate Size
        if ($file['size'] > self::$maxSize) {
            return ['success' => false, 'message' => 'File is too large. Max limit is 5MB.'];
        }

        // 3. Validate MIME Type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!array_key_exists($mimeType, self::$allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, and PNG are allowed.'];
        }

        // 4. Generate Safe Filename (Random Hash + Timestamp)
        $extension = self::$allowedTypes[$mimeType];
        $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;

        // 5. Define Target Path
        // We use the UPLOAD_DIR constant defined in config.php
        $targetDir = UPLOAD_DIR . $subfolder . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . $filename;

        // 6. Move File
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Return relative path for database storage
            return [
                'success' => true, 
                'path' => $subfolder . '/' . $filename, 
                'message' => 'File uploaded successfully'
            ];
        }

        return ['success' => false, 'message' => 'Failed to move uploaded file. Check permissions.'];
    }
}
?>