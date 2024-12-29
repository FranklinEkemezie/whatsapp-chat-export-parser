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


$filename = getFilename();
if (! file_exists($filename)) {
    dumpMsg("File not found");
    exit;
}

// $from = getFromDate();
$from = null;
$chats = parseChatsFromFile($filename, $from);

if (! saveParseResults($filename, $chats)) {
    dumpMsg("Could not save results");
    exit(1);
} else {
    dumpMsg("Parsed $filename successfully");
}