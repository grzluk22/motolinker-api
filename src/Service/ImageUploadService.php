<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Image;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadService
{
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    private const THUMBNAIL_SIZE = 300;
    private const UPLOAD_DIRECTORY = 'public/uploads/articles';

    private string $projectDir;

    public function __construct(
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
    }

    /**
     * Upload image file and create Image entity
     */
    public function upload(UploadedFile $file, Article $article, ?int $position = null, bool $isMain = false): Image
    {
        // Validate file
        $errors = $this->validateFile($file);
        if (!empty($errors)) {
            throw new \RuntimeException('Validation failed: ' . implode(', ', $errors));
        }

        // Get file size BEFORE moving the file
        $fileSize = $file->getSize();
        if ($fileSize === false) {
            throw new \RuntimeException('Could not determine file size');
        }

        // Get image dimensions
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \RuntimeException('Invalid image file');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Generate unique filename
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $filename = $this->generateUniqueFilename($extension);

        // Get upload directory
        $uploadDir = $this->getUploadDirectory($article->getId());
        
        // Ensure base uploads directory exists
        $baseUploadDir = $this->projectDir . '/' . self::UPLOAD_DIRECTORY;
        if (!is_dir($baseUploadDir)) {
            $oldUmask = umask(0);
            @mkdir($baseUploadDir, 0775, true);
            umask($oldUmask);
        }
        
        if (!is_dir($uploadDir)) {
            // Check if parent directory exists and is writable
            $parentDir = dirname($uploadDir);
            if (!is_dir($parentDir)) {
                // Try to create parent directory first
                $oldUmask = umask(0);
                @mkdir($parentDir, 0775, true);
                umask($oldUmask);
                
                if (!is_dir($parentDir)) {
                    throw new \RuntimeException('Parent upload directory does not exist and could not be created: ' . $parentDir);
                }
            }
            
            if (!is_writable($parentDir)) {
                // Try to fix permissions (only if we're running as the owner or root)
                @chmod($parentDir, 0775);
                if (!is_writable($parentDir)) {
                    throw new \RuntimeException(
                        'Parent upload directory is not writable: ' . $parentDir . 
                        '. Please check permissions. Current owner: ' . (fileowner($parentDir) ?? 'unknown') .
                        ', Current permissions: ' . substr(sprintf('%o', fileperms($parentDir)), -4)
                    );
                }
            }
            
            // Try to create directory with proper permissions
            $oldUmask = umask(0);
            $created = @mkdir($uploadDir, 0775, true);
            umask($oldUmask);
            
            if (!$created) {
                $error = error_get_last();
                throw new \RuntimeException(
                    'Failed to create upload directory: ' . $uploadDir . 
                    '. Error: ' . ($error['message'] ?? 'Unknown error') . 
                    '. Please ensure the web server has write permissions to: ' . dirname($uploadDir) .
                    '. Current user: ' . get_current_user() . ', Process user: ' . (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown')
                );
            }
        }
        
        // Ensure directory is writable
        if (!is_writable($uploadDir)) {
            // Try to fix permissions
            @chmod($uploadDir, 0775);
            if (!is_writable($uploadDir)) {
                throw new \RuntimeException(
                    'Upload directory is not writable: ' . $uploadDir . 
                    '. Please check permissions. Current owner: ' . (fileowner($uploadDir) ?? 'unknown') .
                    ', Current permissions: ' . substr(sprintf('%o', fileperms($uploadDir)), -4)
                );
            }
        }

        // Move uploaded file
        try {
            $file->move($uploadDir, $filename);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnail(
            $uploadDir . '/' . $filename,
            $uploadDir . '/thumbnails',
            $filename,
            self::THUMBNAIL_SIZE,
            self::THUMBNAIL_SIZE
        );

        // Create Image entity
        $image = new Image();
        $image->setArticle($article);
        $image->setFilename($filename);
        $image->setOriginalFilename($file->getClientOriginalName());
        $image->setFileSize($fileSize);
        $image->setMimeType($mimeType);
        $image->setWidth($width);
        $image->setHeight($height);
        $image->setIsMain($isMain);
        $image->setPosition($position ?? 0);
        
        // Generate URL
        $baseUrl = '/uploads/articles/' . $article->getId() . '/' . $filename;
        $image->setUrl($baseUrl);

        return $image;
    }

    /**
     * Download an image from a remote URL, save it locally, and return an Image entity.
     * Designed for Prestashop API image imports (URL already contains ws_key as query param).
     *
     * @throws \RuntimeException when download or save fails
     */
    public function downloadFromUrl(string $imageUrl, Article $article, int $position = 0, bool $isMain = false): Image
    {
        $context = stream_context_create([
            'http' => [
                'timeout'         => 15,
                'follow_location' => true,
                'user_agent'      => 'MotoLinker-Importer/1.0',
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $rawContent = @file_get_contents($imageUrl, false, $context);
        if ($rawContent === false || strlen($rawContent) === 0) {
            throw new \RuntimeException(sprintf('Failed to download image from URL: %s', $imageUrl));
        }

        // Detect image type from content
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($rawContent);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new \RuntimeException(sprintf('Downloaded content is not a supported image (mime: %s)', $mimeType));
        }

        $extension = match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/webp'              => 'webp',
            default                   => 'jpg',
        };

        $filename  = $this->generateUniqueFilename($extension);
        $uploadDir = $this->getUploadDirectory($article->getId());

        if (!is_dir($uploadDir)) {
            $oldUmask = umask(0);
            @mkdir($uploadDir, 0775, true);
            umask($oldUmask);
        }

        $filePath = $uploadDir . '/' . $filename;
        if (file_put_contents($filePath, $rawContent) === false) {
            throw new \RuntimeException(sprintf('Failed to save downloaded image to: %s', $filePath));
        }

        // Read dimensions from the saved file
        $imageInfo = @getimagesize($filePath);
        $width     = $imageInfo ? (int) $imageInfo[0] : 0;
        $height    = $imageInfo ? (int) $imageInfo[1] : 0;

        // Generate thumbnail (non-blocking — ignore failures)
        try {
            $this->generateThumbnail($filePath, $uploadDir . '/thumbnails', $filename, self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
        } catch (\Throwable) {
            // Thumbnail is nice-to-have; do not abort the import
        }

        $image = new Image();
        $image->setArticle($article);
        $image->setFilename($filename);
        $image->setOriginalFilename(basename(parse_url($imageUrl, PHP_URL_PATH) ?? $filename));
        $image->setFileSize(strlen($rawContent));
        $image->setMimeType($mimeType);
        $image->setWidth($width);
        $image->setHeight($height);
        $image->setIsMain($isMain);
        $image->setPosition($position);
        $image->setUrl('/uploads/articles/' . $article->getId() . '/' . $filename);

        return $image;
    }

    /**
     * Delete image file and thumbnail
     */
    public function delete(Image $image): void
    {
        $articleId = $image->getArticle()?->getId();
        if (!$articleId) {
            return;
        }

        $uploadDir = $this->getUploadDirectory($articleId);
        $filePath = $uploadDir . '/' . $image->getFilename();
        $thumbnailPath = $uploadDir . '/thumbnails/' . $image->getFilename();

        // Delete original file
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete thumbnail
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }

    /**
     * Generate thumbnail from source image
     */
    public function generateThumbnail(string $sourcePath, string $destinationDir, string $filename, int $width, int $height): ?string
    {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is not installed. Please install php-gd extension.');
        }

        if (!file_exists($sourcePath)) {
            return null;
        }

        // Create thumbnails directory if it doesn't exist
        if (!is_dir($destinationDir)) {
            $parentDir = dirname($destinationDir);
            if (!is_dir($parentDir) || !is_writable($parentDir)) {
                return null;
            }
            
            $oldUmask = umask(0);
            $created = @mkdir($destinationDir, 0755, true);
            umask($oldUmask);
            
            if (!$created) {
                return null;
            }
        }

        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            return null;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($width / $sourceWidth, $height / $sourceHeight);
        $newWidth = (int)($sourceWidth * $ratio);
        $newHeight = (int)($sourceHeight * $ratio);

        // Create source image resource
        $sourceImage = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => null,
        };

        if ($sourceImage === null) {
            return null;
        }

        // Create thumbnail image
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and WebP
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Save thumbnail
        $thumbnailPath = $destinationDir . '/' . $filename;
        $saved = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($thumbnail, $thumbnailPath, 85),
            'image/png' => imagepng($thumbnail, $thumbnailPath, 6),
            'image/webp' => imagewebp($thumbnail, $thumbnailPath, 85),
            default => false,
        };

        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);

        return $saved ? $thumbnailPath : null;
    }

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        // Check if file was uploaded
        if (!$file->isValid()) {
            $errors[] = 'File upload failed';
            return $errors;
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds maximum allowed size of ' . (self::MAX_FILE_SIZE / 1048576) . 'MB';
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_MIME_TYPES);
        }

        // Verify it's actually an image
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            $errors[] = 'File is not a valid image';
        } else {
            // Verify MIME type matches actual image type
            $detectedMimeType = $imageInfo['mime'];
            if (!in_array($detectedMimeType, self::ALLOWED_MIME_TYPES, true)) {
                $errors[] = 'File MIME type does not match image type';
            }
        }

        return $errors;
    }

    /**
     * Generate unique filename using UUID
     */
    public function generateUniqueFilename(string $extension): string
    {
        $uuid = sprintf(
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

        return $uuid . '.' . $extension;
    }

    /**
     * Get upload directory path for article
     */
    public function getUploadDirectory(int $articleId): string
    {
        return $this->projectDir . '/' . self::UPLOAD_DIRECTORY . '/' . $articleId;
    }

    /**
     * Get thumbnail URL for image
     */
    public function getThumbnailUrl(Image $image, ?int $articleId = null): ?string
    {
        // Jeśli articleId nie jest podane, spróbuj pobrać z relacji
        if ($articleId === null) {
            $article = $image->article;
            if (!$article) {
                return null;
            }
            $articleId = $article->getId();
            if (!$articleId) {
                return null;
            }
        }

        $filename = $image->getFilename();
        if (!$filename) {
            return null;
        }

        $thumbnailPath = $this->getUploadDirectory($articleId) . '/thumbnails/' . $filename;
        if (!file_exists($thumbnailPath)) {
            return null;
        }

        return '/uploads/articles/' . $articleId . '/thumbnails/' . $filename;
    }
}

