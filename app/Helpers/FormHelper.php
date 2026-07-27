<?php
namespace App\Helpers;

class FormHelper
{
    const ORDER_NUMBER_PATTERN = '/^LH-\d{4}$/';
    const ORDER_NUMBER_PLACEHOLDER = 'LH-0000';
    const ORDER_NUMBER_HELP_TEXT = 'Please enter a valid order number in the format LH-0000.';
    const ATTACHMENT_MAX_TOTAL_BYTES = 5 * 1024 * 1024;

    public static function isValidOrderNumber(string $value): bool
    {
        return preg_match(self::ORDER_NUMBER_PATTERN, $value) === 1;
    }

    public static function encodeAttachmentPaths(array $paths): ?string
    {
        $paths = array_values(array_filter($paths, fn($p) => $p !== null && $p !== ''));
        return empty($paths) ? null : json_encode($paths);
    }

    public static function decodeAttachmentPaths(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn($p) => is_string($p) && $p !== ''));
        }
        return [$value];
    }

    public static function handleUpload($fileField, array $allowedExts, string $destDir, string $publicPrefix, bool $required, int $maxTotalBytes = self::ATTACHMENT_MAX_TOTAL_BYTES): array
    {
        // $fileField is an array of UploadedFile objects from Laravel request
        $files = is_array($fileField) ? $fileField : ($fileField ? [$fileField] : []);
        $files = array_filter($files);

        if (empty($files)) {
            if ($required) {
                return ['ok' => false, 'paths' => [], 'names' => [], 'error' => 'Please attach the required file.'];
            }
            return ['ok' => true, 'paths' => [], 'names' => [], 'error' => null];
        }

        $totalSize = 0;
        foreach ($files as $f) {
            $totalSize += $f->getSize();
        }
        if ($totalSize > $maxTotalBytes) {
            $maxMb = (int)($maxTotalBytes / (1024*1024));
            return ['ok' => false, 'paths' => [], 'names' => [], 'error' => "Your attached files total more than {$maxMb} MB. Please remove or shrink some and try again."];
        }

        foreach ($files as $f) {
            $ext = strtolower($f->getClientOriginalExtension());
            if (!in_array($ext, $allowedExts, true)) {
                $allowedList = strtoupper(implode(', ', $allowedExts));
                return ['ok' => false, 'paths' => [], 'names' => [], 'error' => "Invalid file type ({$f->getClientOriginalName()}). Accepted formats: {$allowedList}."];
            }
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $paths = [];
        $names = [];
        foreach ($files as $f) {
            $ext = strtolower($f->getClientOriginalExtension());
            $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
            $destPath = rtrim($destDir, '/') . '/' . $safeName;
            $f->move($destDir, $safeName);
            $paths[] = rtrim($publicPrefix, '/') . '/' . $safeName;
            $names[] = $f->getClientOriginalName();
        }

        return ['ok' => true, 'paths' => $paths, 'names' => $names, 'error' => null];
    }
}
