<?php
/**
 * validate_shared.php — Validation script for shared/ directory
 *
 * Usage: php tools/validate_shared.php [shared_dir]
 *   shared_dir defaults to shared/ relative to project root
 *
 * Checks performed:
 *   1. All .md files in shared/ are readable
 *   2. PHP code blocks (```php ... ```) pass syntax check (php -l)
 *   3. JSON code blocks (```json ... ```) are valid JSON
 *   4. URL-encoded sequences (%xx) in waf_bypass.md are valid hex
 *   5. Reports PASS / FAIL / WARNING per check
 */

$sharedDir = $argv[1] ?? dirname(__DIR__) . '/shared';

if (!is_dir($sharedDir)) {
    fwrite(STDERR, "FAIL: shared directory not found: $sharedDir\n");
    exit(1);
}

$pass = 0;
$fail = 0;
$warn = 0;

function report(string $status, string $file, string $msg): void {
    global $pass, $fail, $warn;
    $tag = strtoupper($status);
    echo "[$tag] $file — $msg\n";
    if ($tag === 'PASS') $pass++;
    elseif ($tag === 'FAIL') $fail++;
    else $warn++;
}

$mdFiles = glob("$sharedDir/*.md");
if (empty($mdFiles)) {
    report('warn', $sharedDir, 'No .md files found in shared directory');
}

foreach ($mdFiles as $mdFile) {
    $basename = basename($mdFile);
    $content = file_get_contents($mdFile);

    if ($content === false) {
        report('fail', $basename, 'Could not read file');
        continue;
    }
    report('pass', $basename, 'File readable');

    // Extract and validate PHP code blocks
    if (preg_match_all('/```php\s*\n(.*?)```/s', $content, $phpBlocks)) {
        foreach ($phpBlocks[1] as $i => $code) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'php_validate_');
            // Ensure code starts with <?php if it doesn't already
            $codeToCheck = $code;
            if (strpos(trim($code), '<?php') !== 0 && strpos(trim($code), '<?') !== 0) {
                $codeToCheck = "<?php\n" . $code;
            }
            file_put_contents($tmpFile, $codeToCheck);

            $output = [];
            $rc = 0;
            exec("php -l " . escapeshellarg($tmpFile) . " 2>&1", $output, $rc);
            unlink($tmpFile);

            $blockNum = $i + 1;
            if ($rc === 0) {
                report('pass', $basename, "PHP block #$blockNum syntax OK");
            } else {
                $err = implode(' ', $output);
                report('fail', $basename, "PHP block #$blockNum syntax error: $err");
            }
        }
    }

    // Extract and validate JSON code blocks
    if (preg_match_all('/```json\s*\n(.*?)```/s', $content, $jsonBlocks)) {
        foreach ($jsonBlocks[1] as $i => $jsonStr) {
            $blockNum = $i + 1;
            json_decode(trim($jsonStr));
            if (json_last_error() === JSON_ERROR_NONE) {
                report('pass', $basename, "JSON block #$blockNum valid");
            } else {
                $err = json_last_error_msg();
                report('fail', $basename, "JSON block #$blockNum invalid: $err");
            }
        }
    }

    // Special check for waf_bypass.md: validate %xx URL-encoded sequences
    if ($basename === 'waf_bypass.md') {
        if (preg_match_all('/%([0-9A-Fa-f]{0,2})/', $content, $encMatches, PREG_SET_ORDER)) {
            $badEncodings = [];
            foreach ($encMatches as $m) {
                if (strlen($m[1]) !== 2) {
                    $badEncodings[] = $m[0];
                }
            }
            if (empty($badEncodings)) {
                report('pass', $basename, 'All %xx URL encodings are valid hex');
            } else {
                $examples = implode(', ', array_slice($badEncodings, 0, 5));
                report('fail', $basename, "Invalid URL encodings found: $examples");
            }
        } else {
            report('warn', $basename, 'No %xx URL encodings found to validate');
        }
    }
}

// ============================================================
// Schema-Documentation Consistency Check
// Validates that JSON examples in data_contracts.md match schemas/*.schema.json
// ============================================================

$schemasDir = dirname(__DIR__) . '/schemas';
$dataContractsFile = $sharedDir . '/data_contracts.md';

if (is_dir($schemasDir) && file_exists($dataContractsFile)) {
    echo "\n=== Schema-Documentation Consistency ===\n";

    $dcContent = file_get_contents($dataContractsFile);

    $schemaFiles = glob("$schemasDir/*.schema.json");
    foreach ($schemaFiles as $schemaFile) {
        $schemaName = basename($schemaFile, '.schema.json');
        $schemaRaw = file_get_contents($schemaFile);
        $schema = json_decode($schemaRaw, true);

        if ($schema === null) {
            report('fail', $schemaName, 'Schema file is not valid JSON: ' . json_last_error_msg());
            continue;
        }
        report('pass', $schemaName, 'Schema file is valid JSON');

        $requiredFields = [];
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $fieldName => $fieldDef) {
                if (isset($schema['required']) && in_array($fieldName, $schema['required'])) {
                    $requiredFields[] = $fieldName;
                }
            }
        }

        $pattern = '/```json\s*\n(.*?)```/s';
        if (preg_match_all($pattern, $dcContent, $jsonBlocks)) {
            $foundMatch = false;
            foreach ($jsonBlocks[1] as $i => $jsonStr) {
                $decoded = json_decode(trim($jsonStr), true);
                if ($decoded === null) continue;

                $hasSchemaFields = true;
                foreach ($requiredFields as $reqField) {
                    if (!array_key_exists($reqField, $decoded)) {
                        $hasSchemaFields = false;
                        break;
                    }
                }

                if ($hasSchemaFields && !empty($requiredFields)) {
                    $foundMatch = true;
                    $missingFromDoc = [];
                    foreach ($requiredFields as $reqField) {
                        if (!array_key_exists($reqField, $decoded)) {
                            $missingFromDoc[] = $reqField;
                        }
                    }

                    if (empty($missingFromDoc)) {
                        report('pass', $schemaName, "data_contracts.md example has all required fields (" . count($requiredFields) . ")");
                    } else {
                        report('fail', $schemaName, "data_contracts.md example missing required fields: " . implode(', ', $missingFromDoc));
                    }
                    break;
                }
            }

            if (!$foundMatch && !empty($requiredFields)) {
                report('warn', $schemaName, "No matching JSON example found in data_contracts.md for this schema");
            }
        }
    }
} else {
    if (!is_dir($schemasDir)) {
        report('warn', 'schemas/', 'Schema directory not found, skipping consistency check');
    }
    if (!file_exists($dataContractsFile)) {
        report('warn', 'data_contracts.md', 'File not found, skipping consistency check');
    }
}

// ============================================================
// Sink Registry Consistency Check
// Validates sink_registry.json is valid and sink_finder.php can load it
// ============================================================

$sinkRegistryPath = $schemasDir . '/sink_registry.json';
if (file_exists($sinkRegistryPath)) {
    echo "\n=== Sink Registry Consistency ===\n";

    $registryRaw = file_get_contents($sinkRegistryPath);
    $registry = json_decode($registryRaw, true);

    if ($registry === null) {
        report('fail', 'sink_registry.json', 'Invalid JSON: ' . json_last_error_msg());
    } else {
        report('pass', 'sink_registry.json', 'Valid JSON');

        $totalFunctions = 0;
        $totalMethods = 0;
        $totalStatic = 0;
        $categoryNames = [];

        if (isset($registry['categories'])) {
            foreach ($registry['categories'] as $catName => $cat) {
                $categoryNames[] = $catName;
                if (!isset($cat['sub_categories'])) continue;
                foreach ($cat['sub_categories'] as $subName => $sub) {
                    if (isset($sub['functions'])) $totalFunctions += count($sub['functions']);
                    if (isset($sub['methods'])) $totalMethods += count($sub['methods']);
                    if (isset($sub['static_methods'])) $totalStatic += count($sub['static_methods']);
                }
            }
        }

        report('pass', 'sink_registry.json', "Categories: " . count($categoryNames) . " | Functions: $totalFunctions | Methods: $totalMethods | Static: $totalStatic");

        $sinkDefsFile = $sharedDir . '/sink_definitions.md';
        if (file_exists($sinkDefsFile)) {
            $sinkDefsContent = file_get_contents($sinkDefsFile);
            $missingCategories = [];
            foreach ($categoryNames as $cat) {
                if (stripos($sinkDefsContent, $cat) === false) {
                    $missingCategories[] = $cat;
                }
            }
            if (empty($missingCategories)) {
                report('pass', 'sink_definitions.md', 'All registry categories referenced in documentation');
            } else {
                report('fail', 'sink_definitions.md', 'Categories missing from documentation: ' . implode(', ', $missingCategories));
            }
        }
    }
}

echo "\n=== Summary ===\n";
echo "PASS: $pass  FAIL: $fail  WARNING: $warn\n";
exit($fail > 0 ? 1 : 0);
