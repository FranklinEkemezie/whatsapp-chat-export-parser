<?php

/**
 * - Read the file (say ~/path/to/file/chat_export.txt)
 * - Save the extracted chat info to ~/results/{language_used}/chat_export/chats.json
 * - Save the summary to ~/results/{language_used}/chat_export/summary.txt
 * 
 */

declare(strict_types=1);

require_once __DIR__ . "/Chat.php";
require_once __DIR__ . "/helpers.php";

$filename = $from = $to = $jsonFilename = $summaryFilename = "";

if (PHP_SAPI !== 'cli') {
    die("parser must be run from the command line.\n");
}

$_ = DIRECTORY_SEPARATOR;
$defaultDir = "..{$_}results{$_}php";

getCLIArguments(
    $filename,
    $from,
    $to,
    $jsonFilename,
    $summaryFilename,
    [
        'json_filename'     => "$defaultDir{$_}chats.json",
        'summary_filename'  => "$defaultDir{$_}summary.txt"
    ]
);

if (! file_exists($filename)) {
    dumpMsg("File not found");
    exit;
}

if ($from !== null) $from = new DateTime($from);
if ($to !== null)   $to   = new DateTime($to);
$chats = parseChatsFromFile($filename, $from, $to);

if (! saveParseResults($filename, $jsonFilename, $summaryFilename, $chats)) {
    dumpMsg("Could not save results");
    exit(1);
} else {
    dumpMsg("Parsed $filename successfully");
}