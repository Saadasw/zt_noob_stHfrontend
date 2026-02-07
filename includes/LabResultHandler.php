<?php

class LabResultHandler
{
    private $pdo;
    private $uploadDir;
    private $allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    private $maxSize = 10 * 1024 * 1024; // 10MB

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        // Adjust path based on your project structure. 
        // Assuming this file is in 'includes/' and uploads is in root 'uploads/'
        $this->uploadDir = dirname(__DIR__) . '/uploads/lab_results/';
    }

    /**
     * Upload a lab result file
     * 
     * @param string $testId The ID of the lab test
     * @param array $file The $_FILES['result_file'] array
     * @return array ['success' => bool, 'message' => string, 'file_path' => string|null, 'file_name' => string|null, 'file_type' => string|null, 'file_size' => int|null]
     */
    public function uploadResultFile($testId, $file)
    {
        try {
            // 1. Validate File
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new Exception('Invalid file parameters.');
            }

            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('Exceeded filesize limit.');
                default:
                    throw new Exception('Unknown upload error.');
            }

            if ($file['size'] > $this->maxSize) {
                throw new Exception('Exceeded filesize limit (10MB).');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $this->allowedTypes)) {
                throw new Exception('Invalid file format. Only PDF, JPG, PNG, GIF, and WebP are allowed.');
            }

            // 2. Prepare Directory
            if (!file_exists($this->uploadDir)) {
                if (!mkdir($this->uploadDir, 0755, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }

            // 3. Generate Filename
            // Use test_id + timestamp to allow multiple uploads if needed (though we overwrite in DB usually)
            // or just ensure uniqueness.
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!$extension) {
                // Fallback extension extraction if not present
                $extension = array_search($mimeType, [
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'pdf' => 'application/pdf'
                ], true);
                if ($extension === 'application/pdf')
                    $extension = 'pdf';
            }

            // Clean filename
            $cleanFileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $file['name']);
            $savedFileName = $testId . '_' . time() . '_' . $cleanFileName;
            $targetPath = $this->uploadDir . $savedFileName;

            // 4. Move File
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file.');
            }

            $webPath = 'uploads/lab_results/' . $savedFileName; // Path relative to web root

            return [
                'success' => true,
                'message' => 'File uploaded successfully.',
                'file_path' => $webPath,
                'file_name' => $file['name'],
                'file_type' => $mimeType,
                'file_size' => $file['size']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
