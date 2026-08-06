<?php

class ImageUpload {

    protected static $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    protected static $maxSizeBytes = 5 * 1024 * 1024; // 5MB

    // Nagbabalik ng ['success' => bool, 'path' => string, 'thumbnail' => string, 'error' => string]
    public static function handle($file, $destinationDir, $prefix = 'img') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Walang na-upload na file, o may error sa pag-upload.'];
        }

        if ($file['size'] > self::$maxSizeBytes) {
            return ['success' => false, 'error' => 'Sobrang laki ng file. Max 5MB lang.'];
        }

        $mimeType = mime_content_type($file['tmp_name']);
if (!in_array($mimeType, self::$allowedTypes)) {
    return ['success' => false, 'error' => 'Uri ng file na hindi tinatanggap. JPEG, PNG, o WebP lang.'];
}

if ($mimeType === 'image/webp' && !function_exists('imagecreatefromwebp')) {
    return ['success' => false, 'error' => 'Hindi suportado ang WebP sa server na ito. Gumamit ng JPEG o PNG.'];
}

        // Gumawa ng ligtas, hindi mahuhulaang filename - hindi gagamitin ang orihinal na pangalan.
        $extension = self::extensionFromMime($mimeType);
        $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        $fullDestinationDir = __DIR__ . '/../../public/' . $destinationDir;
        if (!is_dir($fullDestinationDir)) {
            mkdir($fullDestinationDir, 0755, true);
        }

        $fullPath = $fullDestinationDir . '/' . $filename;
        $publicPath = '/sitrass/public/' . $destinationDir . '/' . $filename;

        // I-compress bago i-save, sa halip na i-move nang direkta ang orihinal.
        $compressed = self::compressAndSave($file['tmp_name'], $mimeType, $fullPath);
        if (!$compressed) {
            return ['success' => false, 'error' => 'Nabigo ang pag-process ng larawan.'];
        }

        // Gumawa rin ng maliit na thumbnail.
        $thumbFilename = 'thumb_' . $filename;
        $thumbPath = $fullDestinationDir . '/' . $thumbFilename;
        $publicThumbPath = '/sitrass/public/' . $destinationDir . '/' . $thumbFilename;
        self::compressAndSave($file['tmp_name'], $mimeType, $thumbPath, 300);

        return [
            'success' => true,
            'path' => $publicPath,
            'thumbnail' => $publicThumbPath,
        ];
    }

    protected static function compressAndSave($sourcePath, $mimeType, $destPath, $maxWidth = 1200) {
    switch ($mimeType) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($sourcePath);
        break;
    case 'image/png':
        $image = imagecreatefrompng($sourcePath);
        break;
    case 'image/webp':
        if (!function_exists('imagecreatefromwebp')) {
            return false;
        }
        $image = imagecreatefromwebp($sourcePath);
        break;
    default:
        return false;
}

        if (!$image) {
            return false;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // I-resize lang kung mas malaki sa max width - huwag palakihin ang maliliit na larawan.
        if ($originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)(($originalHeight / $originalWidth) * $maxWidth);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            imagedestroy($image);
            $image = $resized;
        }

        $result = imagejpeg($image, $destPath, 80); // quality 80, tulad ng nasa system_settings
        imagedestroy($image);

        return $result;
    }

    protected static function extensionFromMime($mimeType) {
        // Palagi tayong nagse-save bilang .jpg dahil imagejpeg() ang ginagamit natin sa compressAndSave
        return 'jpg';
    }
}