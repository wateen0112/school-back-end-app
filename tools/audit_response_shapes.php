<?php
/**
 * Audit API controllers for response-shape deviations from the canonical envelope.
 *   2xx  { success: true,  message, data?, meta? }
 *   4xx/5xx { success: false, message, error?, errors? }
 *   422 is reserved for field validation and must carry `errors`.
 *   Business-rule conflicts must use 409, not 422.
 */
$root = dirname(__DIR__) . '/app/Http/Controllers';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$rawJson = [];   // ->json($var) with no array literal  => no envelope at all
$noSuccess = []; // array literal returned without a success key
$valNoErrors = [];
$conflictAs422 = [];

foreach ($rii as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $code = file_get_contents($path);
    $rel = str_replace(dirname(__DIR__) . '/', '', $path);

    $methodAt = function (int $pos) use ($code) {
        preg_match_all('/function\s+(\w+)\s*\(/', substr($code, 0, $pos), $fm);

        return $fm[1] ? end($fm[1]) : 'closure';
    };

    // 1) ->json(  NOT immediately followed by '['  => raw payload, no envelope
    if (preg_match_all('/->json\(\s*(?!\[)/', $code, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) {
            $pos = $hit[1];
            $lineNo = substr_count(substr($code, 0, $pos), "\n") + 1;
            $rawJson[] = sprintf('%s:%d  %s()', $rel, $lineNo, $methodAt($pos));
        }
    }

    // 2) array-literal responses: scan balanced brackets
    $offset = 0;
    while (($pos = strpos($code, '->json(', $offset)) !== false) {
        $open = strpos($code, '[', $pos);
        if ($open === false) {
            break;
        }
        $between = trim(substr($code, $pos + 7, $open - ($pos + 7)));
        $offset = $pos + 7;
        if ($between !== '') {
            continue; // not a bare array literal
        }

        $depth = 0;
        $i = $open;
        for (; $i < strlen($code); $i++) {
            if ($code[$i] === '[') {
                $depth++;
            } elseif ($code[$i] === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }
        $block = substr($code, $open, $i - $open + 1);
        $rest = trim(substr($code, $i + 1, 12));
        $status = preg_match('/^,\s*(\d{3})/', $rest, $m2) ? (int) $m2[1] : 200;
        $lineNo = substr_count(substr($code, 0, $open), "\n") + 1;
        $method = $methodAt($open);

        $hasSuccess = str_contains($block, "'success'");
        $hasErrors = str_contains($block, "'errors'");

        if (! $hasSuccess) {
            $noSuccess[] = sprintf('%s:%d  [%d] %s()', $rel, $lineNo, $status, $method);
        } elseif ($status === 422 && ! $hasErrors) {
            $conflictAs422[] = sprintf('%s:%d  %s()', $rel, $lineNo, $method);
        }

        $offset = $i + 1;
    }
}

echo "RAW PAYLOAD (no envelope): " . count($rawJson) . "\n";
foreach ($rawJson as $r) {
    echo "  $r\n";
}
echo "\nMISSING success KEY: " . count($noSuccess) . "\n";
foreach ($noSuccess as $r) {
    echo "  $r\n";
}
echo "\n422 WITHOUT errors (should be 409): " . count($conflictAs422) . "\n";
foreach ($conflictAs422 as $r) {
    echo "  $r\n";
}