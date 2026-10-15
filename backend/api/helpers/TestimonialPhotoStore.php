<?php
/**
 * TestimonialPhotoStore
 *
 * Testimonial photos are stored inside the database (not on disk) so they
 * survive container redeploys on hosts with an ephemeral filesystem.
 */
class TestimonialPhotoStore
{
    const MAX_UPLOAD_BYTES = 8388608;   // 8 MB incoming
    const MAX_RAW_STORE_BYTES = 1572864; // 1.5 MB when GD is unavailable and we cannot resize
    const MAX_DIMENSION = 800;
    const PHOTO_ENDPOINT = 'backend/api/public/testimonial_photo.php';

    /** Adds the photo columns on first use (idempotent). */
    public static function ensureSchema(PDO $db): void
    {
        $exists = $db->query("SHOW COLUMNS FROM testimonials LIKE 'photo_data'")->fetch();
        if (!$exists) {
            $db->exec("ALTER TABLE testimonials ADD COLUMN photo_data MEDIUMBLOB NULL, ADD COLUMN photo_mime VARCHAR(50) NULL");
        }
    }

    public static function photoUrlFor(int $id): string
    {
        return self::PHOTO_ENDPOINT . '?id=' . $id . '&v=' . time();
    }

    /**
     * Validates an uploaded image and returns [binaryData, mimeType].
     * Re-encodes to a max 800px JPEG when GD is available.
     */
    public static function prepare(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Photo upload failed. Please try again.');
        }
        if (($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            throw new Exception('Photo is too large. Maximum size is 8MB.');
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            throw new Exception('Invalid photo upload.');
        }

        $info = @getimagesize($tmp);
        $allowed = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp', IMAGETYPE_GIF => 'image/gif'];
        if (!$info || !isset($allowed[$info[2]])) {
            throw new Exception('Only JPG, PNG, WEBP or GIF photos are allowed.');
        }

        $raw = file_get_contents($tmp);
        if ($raw === false) {
            throw new Exception('Could not read the uploaded photo.');
        }

        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $src = @imagecreatefromstring($raw);
            if ($src) {
                if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($tmp);
                    $rot = $exif['Orientation'] ?? 1;
                    $angle = [3 => 180, 6 => -90, 8 => 90][$rot] ?? 0;
                    if ($angle) {
                        $rotated = imagerotate($src, $angle, 0);
                        if ($rotated) { imagedestroy($src); $src = $rotated; }
                    }
                }
                $w = imagesx($src);
                $h = imagesy($src);
                $scale = min(1, self::MAX_DIMENSION / max($w, $h));
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $dst = imagecreatetruecolor($nw, $nh);
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                ob_start();
                imagejpeg($dst, null, 85);
                $jpeg = ob_get_clean();
                imagedestroy($src);
                imagedestroy($dst);
                if ($jpeg !== false && $jpeg !== '') {
                    return [$jpeg, 'image/jpeg'];
                }
            }
        }

        if (strlen($raw) > self::MAX_RAW_STORE_BYTES) {
            throw new Exception('Photo is too large. Please upload a photo under 1.5MB.');
        }
        return [$raw, $allowed[$info[2]]];
    }
}
