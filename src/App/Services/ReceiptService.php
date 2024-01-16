<?php

declare(strict_types=1);

namespace App\Services;

use Framework\Database;
use Framework\Exceptions\ValidationException;
use App\Config\Paths;

class ReceiptService
{
    public function __construct(private Database $db)
    {
    }

    public function validateFile(?array $file)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException([
                'receipt' => ['Failed to upload file']
            ]);
        }
        $maxFileSizeMB = 3 * 1024 * 1024;
        if ($file['size'] > $maxFileSizeMB) {
            throw new ValidationException([
                'receipt' => ['File is too large']
            ]);
        };

        // Validate filename usign regex 
        $originalFileName = $file['name'];
        $regex = '/^[A-za-z0-9\s._-]+$/';
        if (!preg_match($regex, $originalFileName)) {
            throw new ValidationException([
                'receipt' => ['Invalid filename']
            ]);
        }

        // Validate Mime types [pdf, images only]
        $allowedMimeTypes = ['application/pdf', 'image/png', 'image/jpeg'];

        $mimeType = mime_content_type($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new ValidationException([
                'receipt' => ['Invalid file type']
            ]);
        }
    }

    public function upload(array $file, int $transaction)
    {
        $fileExtension  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = random_bytes(16);
        $newFilename = bin2hex($newFilename) . "." . $fileExtension;
        // move file to STORAGE_UPLOAD
        $uploadPath = Paths::STORAGE_UPLOADS . "/" . $newFilename;
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new ValidationException([
                'receipt' => ['Failed to upload file']
            ]);
        }

        $this->db->query(
            "INSERT INTO receipts(transaction_id, original_filename, storage_filename, media_type) VALUES(:transaction_id, :original_filename, :storage_filename, :media_type);",
            [
                'transaction_id' => $transaction,
                'original_filename' => $file['name'],
                'storage_filename' => $newFilename,
                'media_type' => $file['type']
            ]
        );
    }
}
