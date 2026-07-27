<?php
/**
 * includes/form-helpers.php
 * ------------------------------------------------------------------
 * Shared helpers used by the customer-facing request forms
 * (submit-ticket.php, warranty-request.php, returns-refund.php):
 *
 *   - isValidOrderNumber()      Order Number format check (WC-XXXX),
 *                               used on the server to back up the same
 *                               pattern enforced client-side.
 *   - handleMultiFileUpload()   Validates + stores however many files the
 *                               customer attached to one field (e.g.
 *                               <input type="file" name="attachment[]"
 *                               multiple>), as long as their *combined*
 *                               size doesn't exceed the limit.
 *   - encodeAttachmentPaths()   Turns the array of stored paths from
 *                               handleMultiFileUpload() into the single
 *                               string saved in the DB column.
 *   - decodeAttachmentPaths()   Turns that stored string back into an
 *                               array of paths for display. Understands
 *                               both the new JSON-array format and the
 *                               old plain-single-path format, so rows
 *                               submitted before this change still show
 *                               up correctly.
 *
 * Centralizing these here means all three forms validate order numbers
 * and handle uploads exactly the same way.
 * ------------------------------------------------------------------
 */

// LH-0000: "LH-" followed by 4 digits (e.g. LH-0001, LH-0100).
const ORDER_NUMBER_PATTERN = '/^LH-\d{4}$/';
const ORDER_NUMBER_PLACEHOLDER = 'LH-0000';
const ORDER_NUMBER_HELP_TEXT = 'Please enter a valid order number in the format LH-0000.';

// Combined size limit for every file attached to one upload field in a
// single submission (not a per-file limit) — matches what the forms
// tell the customer ("Maximum size 5 MB").
const ATTACHMENT_MAX_TOTAL_BYTES = 5 * 1024 * 1024;

/**
 * True if $value matches the required WC-XXXX order number format.
 */
function isValidOrderNumber(string $value): bool
{
    return preg_match(ORDER_NUMBER_PATTERN, $value) === 1;
}

/**
 * Validates and moves every file attached to one <input type="file"
 * name="$fieldName[]" multiple> field into $destDir, returning the
 * relative paths (to be stored in the database, JSON-encoded via
 * encodeAttachmentPaths()) on success.
 *
 * The customer can attach as many files as they like — there's no cap
 * on file count — but every file attached to this field together must
 * not exceed $maxTotalBytes combined.
 *
 * @param string   $fieldName     The <input type="file" name="...[]"> key (without the []).
 * @param string[] $allowedExts   Lowercase extensions without the dot, e.g. ['jpg','jpeg','png','pdf'].
 * @param string   $destDir       Absolute filesystem directory to store the files in.
 * @param string   $publicPrefix  Relative path prefix stored in the DB / used to build download links.
 * @param bool     $required      Whether at least one file must be present.
 * @param int      $maxTotalBytes Maximum combined size, across every file in this field, in bytes.
 *
 * @return array{ok: bool, paths: string[], names: string[], error: ?string}
 */
function handleMultiFileUpload(
    string $fieldName,
    array $allowedExts,
    string $destDir,
    string $publicPrefix,
    bool $required,
    int $maxTotalBytes = ATTACHMENT_MAX_TOTAL_BYTES
): array {
    $field = $_FILES[$fieldName] ?? null;

    // Normalize the PHP multi-file upload shape (parallel arrays keyed
    // by name/type/tmp_name/error/size) into a flat list of per-file
    // arrays, skipping empty slots (e.g. an <input multiple> where the
    // customer picked fewer files than a previous attempt had).
    $files = [];
    if (is_array($field) && isset($field['error'])) {
        if (is_array($field['error'])) {
            foreach ($field['error'] as $i => $error) {
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $files[] = [
                    'name' => $field['name'][$i],
                    'type' => $field['type'][$i],
                    'tmp_name' => $field['tmp_name'][$i],
                    'error' => $error,
                    'size' => $field['size'][$i],
                ];
            }
        } elseif ($field['error'] !== UPLOAD_ERR_NO_FILE) {
            // A single (non-array) upload also works, e.g. the field
            // wasn't marked multiple, or only one file was chosen.
            $files[] = $field;
        }
    }

    if (empty($files)) {
        if ($required) {
            return ['ok' => false, 'paths' => [], 'names' => [], 'error' => 'Please attach the required file.'];
        }
        return ['ok' => true, 'paths' => [], 'names' => [], 'error' => null];
    }

    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'paths' => [], 'names' => [], 'error' => 'There was a problem uploading one of your files. Please try again.'];
        }
    }

    $totalSize = array_sum(array_column($files, 'size'));
    if ($totalSize > $maxTotalBytes) {
        $maxMb = (int)($maxTotalBytes / (1024 * 1024));
        return ['ok' => false, 'paths' => [], 'names' => [], 'error' => "Your attached files total more than {$maxMb} MB. Please remove or shrink some and try again."];
    }

    $allowedMimes = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
    ];

    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $allowedList = strtoupper(implode(', ', $allowedExts));
            return ['ok' => false, 'paths' => [], 'names' => [], 'error' => "Invalid file type ({$file['name']}). Accepted formats: {$allowedList}."];
        }

        // Double-check the actual file contents match an accepted
        // image/PDF type, not just the extension the browser reported.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }
        if ($detectedMime && isset($allowedMimes[$ext]) && !in_array($detectedMime, $allowedMimes[$ext], true)) {
            return ['ok' => false, 'paths' => [], 'names' => [], 'error' => "That file does not appear to be a valid " . strtoupper($ext) . " file ({$file['name']})."];
        }
    }

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    // Only move files to disk once every file in the batch has passed
    // validation, so a bad file later in the list can't leave earlier
    // ones saved with nothing recorded in the database.
    $paths = [];
    $names = [];
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = rtrim($destDir, '/') . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'paths' => [], 'names' => [], 'error' => 'There was a problem saving your files. Please try again.'];
        }

        $paths[] = rtrim($publicPrefix, '/') . '/' . $safeName;
        $names[] = $file['name'];
    }

    return ['ok' => true, 'paths' => $paths, 'names' => $names, 'error' => null];
}

/**
 * Encodes an array of stored attachment paths into the single string
 * saved in a DB column. Returns null for an empty array so "no
 * attachment" still reads as NULL, same as before this change.
 */
function encodeAttachmentPaths(array $paths): ?string
{
    $paths = array_values(array_filter($paths, fn($p) => $p !== null && $p !== ''));
    return empty($paths) ? null : json_encode($paths);
}

/**
 * Decodes a DB column back into an array of attachment paths for
 * display. Handles three cases: NULL/empty (no attachments), the new
 * JSON-array format this change introduces, and the old plain single
 * path string that rows submitted before this change still have.
 */
function decodeAttachmentPaths(?string $value): array
{
    if ($value === null || $value === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    if (is_array($decoded)) {
        return array_values(array_filter($decoded, fn($p) => is_string($p) && $p !== ''));
    }
    // Old format: a single raw path string.
    return [$value];
}