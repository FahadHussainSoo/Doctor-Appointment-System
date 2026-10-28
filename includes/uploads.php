<?php
/**
 * Phase 4 — Avatar upload helper.
 *
 * Deliberately kept out of includes/auth.php so that file (and every other
 * Phase 1–3 backend file) stays untouched. This is new, additive code only.
 *
 * Storage: users.avatar (nullable VARCHAR) — see db_patch_phase4.sql.
 * If that column hasn't been added yet, save_avatar() fails soft (returns
 * an error array instead of throwing), so profile pages keep working with
 * an initials-avatar fallback until the patch is applied.
 */

define('MB_AVATAR_DIR', __DIR__ . '/../uploads/avatars/');
define('MB_AVATAR_URL', 'uploads/avatars/');
define('MB_AVATAR_MAX_BYTES', 2 * 1024 * 1024); // 2MB
define('MB_AVATAR_ALLOWED', ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']);

/**
 * Validates and stores an uploaded avatar file, updates users.avatar,
 * and returns ['success' => bool, 'message' => string, 'path' => ?string].
 *
 * @param PDO   $pdo
 * @param int   $userId
 * @param array $file    A single entry from $_FILES, e.g. $_FILES['avatar']
 * @param string $basePath Relative prefix to reach the site root (e.g. '../' from admin/doctor pages)
 */
function mb_save_avatar(PDO $pdo, int $userId, array $file, string $basePath = ''): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'No file was selected.', 'path' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed. Please try again.', 'path' => null];
    }
    if ($file['size'] > MB_AVATAR_MAX_BYTES) {
        return ['success' => false, 'message' => 'Image must be 2MB or smaller.', 'path' => null];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(MB_AVATAR_ALLOWED[$mime])) {
        return ['success' => false, 'message' => 'Please upload a JPG, PNG, or WEBP image.', 'path' => null];
    }

    if (!is_dir(MB_AVATAR_DIR)) {
        @mkdir(MB_AVATAR_DIR, 0755, true);
    }

    $filename = 'avatar_' . $userId . '_' . time() . '.' . MB_AVATAR_ALLOWED[$mime];
    $destination = MB_AVATAR_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Could not save the uploaded file.', 'path' => null];
    }

    $relativePath = MB_AVATAR_URL . $filename;

    try {
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$relativePath, $userId]);
    } catch (PDOException $e) {
        // Most likely cause: the `avatar` column hasn't been added yet.
        @unlink($destination);
        return [
            'success' => false,
            'message' => 'Avatar column not found on `users`. Run db_patch_phase4.sql, then try again.',
            'path' => null,
        ];
    }

    return ['success' => true, 'message' => 'Profile photo updated.', 'path' => $basePath . $relativePath];
}

/**
 * Reads a user's stored avatar path, if the column exists and is set.
 * Returns null on any failure (missing column, no avatar, etc.) so
 * callers can safely fall back to an initials avatar.
 */
function mb_get_avatar(PDO $pdo, int $userId, string $basePath = ''): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $path = $stmt->fetchColumn();
        return $path ? $basePath . $path : null;
    } catch (PDOException $e) {
        return null;
    }
}
