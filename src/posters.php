<?php

function uploaded_poster($file)
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (!is_int($file['error'] ?? null) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new DomainException('The poster could not be uploaded. Choose an image up to 2 MB and try again.');
    }
    $path = $file['tmp_name'] ?? '';
    if (!is_string($path) || !is_uploaded_file($path) || filesize($path) > 2 * 1024 * 1024) {
        throw new DomainException('Choose a poster no larger than 2 MB.');
    }
    $image = @getimagesize($path);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!$image || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $image['mime'] !== $mime) {
        throw new DomainException('Choose a valid JPEG, PNG or WebP image.');
    }
    if ($image[0] > 10000 || $image[1] > 10000 || $image[0] * $image[1] > 40000000) {
        throw new DomainException('Choose an image with at most 40 million pixels and sides no larger than 10,000 pixels.');
    }
    $content = file_get_contents($path);
    return ['content' => base64_encode($content), 'mime' => $mime, 'hash' => hash('sha256', $content)];
}
