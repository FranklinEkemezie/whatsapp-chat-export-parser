<?php


function dumpMsg(string $msg): void
{
    echo $msg . PHP_EOL;
}

function getFilename(): string
{
    $filename = dirname(__DIR__) . DIRECTORY_SEPARATOR . "samples/mce.txt";
    return str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $filename);
}

function getFromDate(): DateTime
{
    return new DateTime('last week');
}

/**
 * 
 * @return Chat[]
 */
function parseChatsFromFile(string $filename, ?DateTime $from=null): array
{
    $chats = [];

    if (! file_exists($filename)) {
        throw new InvalidArgumentException("File not found");
    }

    $file = fopen($filename, "r");
    if (! $file) {
        throw new Exception("Could not open file");
    }

    while (($line = fgets($file)) !== false) {
        if (($chat = parseLine($line)) === null) {
            continue;
        }

        if ($from !== null && $chat->sentBefore($from)) {
            continue;
        }

        array_push($chats, $chat);
    }

    fclose($file);

    return $chats;
}

function parseLine(string $line): ?Chat {
    $dateRegex      = "\d\d\/\d\d\/\d\d\d\d";
    $timeRegex      = "(\d?\d:\d\d).+([a|p]m)";
    $senderRegex    = ".+?";
    $messageRegex   = ".+";

    $chatRegex      = "/($dateRegex), ($timeRegex) - ($senderRegex): ($messageRegex)/";
    if (preg_match(
        $chatRegex,
        $line,
        $matches
    ) !== 1) return null;

    $date = str_replace("/", "-", $matches[1]);
    $time = "{$matches[3]} {$matches[4]}";

    return new Chat($matches[5], $matches[6], new DateTime("$date $time"));
}

/**
 * 
 * @param Chat[] $chats
 * @param bool $asArray
 * @return array
 */
function sortChatsBySender(array $chats, bool $asArray=true): array
{
    $sortedChats = [];

    foreach($chats as $chat) {
        $sender         = $chat->getSender();
        $senderChats    = $sortedChats[$sender] ?? [];

        array_push($senderChats,  $asArray ? $chat->toArray() : $chat);
        $sortedChats[$sender] = $senderChats;
    }

    return $sortedChats;
}


/**
 * 
 * @param Chat[] $chats
 * @param bool $latest
 * @param bool $asArray
 * @return array
 */
function sortChatsByDate(array $chats, bool $latest=true, bool $asArray=true): array
{
    $sortedChats = [];

    foreach($chats as $chat) {
        $dateSent           = $chat->getTimestamp()->format('d-m-Y');
        $chatsOnSameDate    = $sortedChats[$dateSent] ?? [];

        array_push($chatsOnSameDate, $asArray ? $chat->toArray() : $chat);
        $sortedChats[$dateSent] = $chatsOnSameDate;
    }

    uksort($sortedChats, function ($date1, $date2) use ($latest) {
        $date1 = new DateTime($date1);
        $date2 = new DateTime($date2);

        $date2ComesAfterdate1 = (int) $date1->diff($date2)->format('%R%d');

        if (! $latest) $date2ComesAfterdate1 = 0 - $date2ComesAfterdate1;

        return $date2ComesAfterdate1;
    });

    return $sortedChats;
}

function getChatsPerSender(array $chats, ?bool $sortAsc=null): array
{
    $chatsBySender  = sortChatsBySender($chats);
    $chatsPerSender = array_map(fn(array $chats) => count($chats), $chatsBySender);

    if ($sortAsc !== null) {
        $sortAsc ? asort($chatsPerSender) : arsort($chatsPerSender);
    }

    return $chatsPerSender;
}

/**
 * 
 * @param string $filename
 * @param Chat[] $chats
 * @return bool
 */
function saveParseResults(string $filename, array $chats): bool
{

    // Check if the `result/` directory exists
    $rootDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "results";
    if (! is_dir($rootDir) && ! mkdir($rootDir)) {
        die("Could not save results");
    }

    // Check if the `results/php/` exists
    $phpResultDir = $rootDir . DIRECTORY_SEPARATOR . "php";
    if (! is_dir($phpResultDir) && ! mkdir($phpResultDir)) {
        die("Could not save results.");
    }

    // Lastly, check if the `results/php/{filename}/` directory exists
    $nameOfFile = pathinfo($filename)['filename'];
    $resultDir = $phpResultDir . DIRECTORY_SEPARATOR . $nameOfFile;
    if (! is_dir($resultDir) && ! mkdir($resultDir)) {
        die("Could not save results");
    }

    // Send in the results to chats.json
    $jsonFilename   = $resultDir . DIRECTORY_SEPARATOR . "chats.json";
    $jsonFile       = fopen($jsonFilename, "w");
    if (! $jsonFile) {
        throw new Exception("Could not open file");
    }

    fputs(
        $jsonFile,
        json_encode(
            array_map(fn(Chat $chat) => $chat->toArray(), $chats),
            JSON_PRETTY_PRINT
        ),
    );
    fclose($jsonFile);

    // Send in the summary to summary.txt
    $summaryFilename    = $resultDir . DIRECTORY_SEPARATOR . "summary.txt";
    $summaryFile        = fopen($summaryFilename, "w");
    if (! $summaryFile) {
        throw new Exception("Could not open file");
    }

    $totalChats = count($chats) * 2;
    $summary    = getSummaryFromTemplate($filename, $chats);

    fputs($summaryFile, $summary);
    fclose($summaryFile);

    return true;
}

function getSummaryFromTemplate(
    string $filename,
    array $chats
): string
{
    $headerTemplate = <<<TEXT
    ************************ SUMMMARY ***************************
    =============================================================

    Filename:       %-s
    Total Chats:    %d
    TEXT;

    $chatsSummaryTemplate = getChatsSummaryFromTemplate(
        getChatsPerSender($chats, false)
    );

    $totalChats = count($chats);
    $summaryTemplate = <<<TEXT
    $headerTemplate

    +-------+-----------------------------------+--------------+
    |  S/N  | By                                | Total Chats  |
    +-------+-----------------------------------+--------------+
    $chatsSummaryTemplate
    +-------+-----------------------------------+--------------+

    Automatically generated by `whatsapp-chat-export-parser` (PHP) on %s
    TEXT;

    return sprintf(
        $summaryTemplate,
        $filename,
        $totalChats,
        (new DateTime())->format("D, jS M., Y @ h:i:s a")
    );
}

/**
 * 
 * @param Chat[] $chatsBySender
 * @return string
 */
function getChatsSummaryFromTemplate(
    array $chatsBySender
): string
{

    $senders = array_keys($chatsBySender);
    return join("\n", array_map(
        fn(string $sender, int $index) : string  => sprintf(
            "|  %'.03d  | %- 32s  | % 12s |",
            $index + 1,
            $sender,
            $chatsBySender[$sender]
        ),
        $senders, array_keys($senders)
    ));
}