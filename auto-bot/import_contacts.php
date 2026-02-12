<?php
/**
 * import_contacts.php
 * Reads contacts from Excel / CSV
 * Expected columns:
 * Name | Number | Business Type
 */

use PhpOffice\PhpSpreadsheet\IOFactory;

function readContacts(string $filePath): array
{
    if (!file_exists($filePath)) {
        throw new Exception("Contacts file not found: {$filePath}");
    }

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getSheet(0);
    $rows = $sheet->toArray(null, true, true, true);

    $contacts = [];

    // Skip header row (row 1)
    foreach ($rows as $index => $row) {
        if ($index === 1) {
            continue;
        }

        $name  = trim($row['A'] ?? '');
        $num   = preg_replace('/\D/', '', $row['B'] ?? '');
        $type  = trim($row['C'] ?? '');

        if (!$name || !$num) {
            continue;
        }

        // Normalize Indian numbers
        if (strlen($num) === 10) {
            $num = '91' . $num;
        }

        $contacts[] = [
            'name'           => $name,
            'number'         => $num,
            'business_type'  => $type,
        ];
    }

    return $contacts;
}
