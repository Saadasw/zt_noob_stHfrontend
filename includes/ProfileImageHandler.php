<?php

class ProfileImageHandler
{
    private $pdo;
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $maxSize = 5 * 1024 * 1024; // 5MB

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        // Adjust path based on your project structure. 
        // Assuming this file is in 'includes/' and uploads is in root 'uploads/'
        $this->uploadDir = dirname(__DIR__) . '/uploads/profiles/';
    }

    /**
     * Upload a profile image for a user
     * 
     * @param string $userId The user's ID
     * @param array $file The $_FILES['image'] array
     * @return array ['success' => bool, 'message' => string, 'file_path' => string|null]
     */
    public function uploadProfileImage($userId, $file)
    {
        try {
            // 1. Validate File
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new Exception('Invalid parameters.');
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
                    throw new Exception('Unknown errors.');
            }

            if ($file['size'] > $this->maxSize) {
                throw new Exception('Exceeded filesize limit (5MB).');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $this->allowedTypes)) {
                throw new Exception('Invalid file format. Only JPG, PNG, GIF, and WebP are allowed.');
            }

            // 2. Prepare Directory
            if (!file_exists($this->uploadDir)) {
                if (!mkdir($this->uploadDir, 0755, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }

            // 3. Generate Filename
            // Use user_id + timestamp hash to avoid caching issues and collisions
            $extension = array_search($mimeType, [
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ], true);

            if ($extension === false) {
                // Fallback extension extraction if MIME lookup fails (though unlikely with finfo)
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            }

            $fileName = $userId . '_' . time() . '.' . $extension;
            $targetPath = $this->uploadDir . $fileName;

            // 4. Move File
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file.');
            }

            // 5. Update Database
            $this->pdo->beginTransaction();

            // Mark old images as not current
            $stmt = $this->pdo->prepare("UPDATE profile_images SET is_current = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Insert new image record
            $imageId = $this->generateUUID();
            $webPath = 'uploads/profiles/' . $fileName; // Path relative to web root

            $stmt = $this->pdo->prepare("
                INSERT INTO profile_images (id, user_id, image_url, file_type, is_current, uploaded_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$imageId, $userId, $webPath, $mimeType]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Profile image uploaded successfully.',
                'image_url' => $webPath
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get the current profile image URL for a user
     */
    public function getProfileImage($userId)
    {
        $stmt = $this->pdo->prepare("SELECT image_url FROM profile_images WHERE user_id = ? AND is_current = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['image_url'] : null;
    }

    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
