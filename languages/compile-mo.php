#!/usr/bin/env php
<?php
/**
 * Simple PHP-based .po to .mo compiler
 * Converts .po files to .mo binary format
 */

function compile_po_to_mo($po_file, $mo_file) {
    $po_content = file_get_contents($po_file);
    if ($po_content === false) {
        return false;
    }

    // Parse .po file
    $entries = [];
    $current_msgid = '';
    $current_msgstr = '';
    $in_msgid = false;
    $in_msgstr = false;

    $lines = explode("\n", $po_content);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if (empty($line) || $line[0] === '#') {
            continue;
        }

        // msgid line
        if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
            // Save previous entry
            if ($current_msgid !== '' && $current_msgstr !== '') {
                $entries[$current_msgid] = $current_msgstr;
            }
            $current_msgid = stripcslashes($matches[1]);
            $current_msgstr = '';
            $in_msgid = true;
            $in_msgstr = false;
        }
        // msgstr line
        elseif (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches)) {
            $current_msgstr = stripcslashes($matches[1]);
            $in_msgid = false;
            $in_msgstr = true;
        }
        // Continuation line
        elseif (preg_match('/^"(.*)"$/', $line, $matches)) {
            $value = stripcslashes($matches[1]);
            if ($in_msgid) {
                $current_msgid .= $value;
            } elseif ($in_msgstr) {
                $current_msgstr .= $value;
            }
        }
    }

    // Save last entry
    if ($current_msgid !== '' && $current_msgstr !== '') {
        $entries[$current_msgid] = $current_msgstr;
    }

    // Remove empty header
    if (isset($entries[''])) {
        unset($entries['']);
    }

    // Build .mo file
    $mo = '';

    // Magic number
    $mo .= pack('L', 0x950412de);

    // Format revision
    $mo .= pack('L', 0);

    // Number of strings
    $count = count($entries);
    $mo .= pack('L', $count);

    // Offset of table with original strings
    $mo .= pack('L', 28);

    // Offset of table with translated strings
    $mo .= pack('L', 28 + ($count * 8));

    // Hash table size (not used)
    $mo .= pack('L', 0);

    // Hash table offset (not used)
    $mo .= pack('L', 0);

    // Calculate string offsets
    $ids = '';
    $strs = '';
    $offset = 28 + ($count * 16);

    foreach ($entries as $msgid => $msgstr) {
        $ids .= pack('L', strlen($msgid));
        $ids .= pack('L', $offset);
        $offset += strlen($msgid) + 1;
    }

    foreach ($entries as $msgid => $msgstr) {
        $strs .= pack('L', strlen($msgstr));
        $strs .= pack('L', $offset);
        $offset += strlen($msgstr) + 1;
    }

    $mo .= $ids . $strs;

    foreach ($entries as $msgid => $msgstr) {
        $mo .= $msgid . "\x00";
    }

    foreach ($entries as $msgid => $msgstr) {
        $mo .= $msgstr . "\x00";
    }

    return file_put_contents($mo_file, $mo);
}

// Main execution
if (php_sapi_name() === 'cli') {
    $dir = __DIR__;
    $files = glob($dir . '/*.po');

    if (empty($files)) {
        echo "No .po files found in $dir\n";
        exit(1);
    }

    foreach ($files as $po_file) {
        $mo_file = preg_replace('/\.po$/', '.mo', $po_file);
        echo "Compiling " . basename($po_file) . " → " . basename($mo_file) . "... ";

        if (compile_po_to_mo($po_file, $mo_file)) {
            echo "✓ Success\n";
        } else {
            echo "✗ Failed\n";
        }
    }

    echo "Done!\n";
}
