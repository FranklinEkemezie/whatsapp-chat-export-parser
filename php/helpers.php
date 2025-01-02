<?php


/**
 * Get the command line argument
 * @param ?string $filename The name of the whatsapp chat export
 * @param ?string $from The start date
 * @param ?string $to The end date
 * @param ?string $chatFilename The output file to place the parsed file as JSON
 * @param ?string $summaryFilename The output file to place the summary as .txt
 * @param ?array  $defaults An associative array of the default values for the parameters
 * above if not specified. The key of the array can be any of the following:
 * `from`, `to`, `chat_filename`, `summary_filename`
 * @return bool
 */
function getCLIArguments(
     string &$filename,
    ?string &$from,
    ?string &$to,
    ?string &$jsonFilename,
    ?string &$summaryFilename,
    ?array $defaults=[]
): bool
{
    $argc = $_SERVER['argc'];
    $argv = $_SERVER['argv'];

    if ($argc < 2 || $argc > 6)
    {
        throw new InvalidArgumentException(
            "Usage: ./parser <whatsapp_chat_export.txt> [from_date: dd-mm-yyyy hr:min:sec] [to_date: dd-mm-yyyy hr:min:sec] [chat_filename.json] [summary_filename.txt]"
        );
    }

    // Get the CLI argument
    $filename           = $argv[1];
    $from               = $argv[2] ?? ($defaults['from'] ?? null);
    $to                 = $argv[3] ?? ($defaults['to'] ?? null);
    $jsonFilename       = $argv[4] ?? ($defaults['json_filename'] ?? null);
    $summaryFilename    = $argv[5] ?? ($defaults['summary_filename'] ?? null);

    return true;
}

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
function parseChatsFromFile(string $filename, ?DateTime $from=null, ?DateTime $to=null): array
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

        if ($from !== null && $chat->sentBefore($from))
            continue;

        if ($to !== null && $chat->sentAfter($to))
            continue;

        array_push($chats, $chat);
    }

    fclose($file);

    return $chats;
}

function parseLine(string $line): ?Chat {
    $dateRegex      = "\d{2}/\d{2}/\d{4}";
    $timeRegex      = "(\d?\d:\d\d).+([a|p]m)";
    $senderRegex    = ".+?";
    $messageRegex   = ".+";

    $chatRegex      = "@^($dateRegex), ($timeRegex) - ($senderRegex): ($messageRegex)@";
    if (preg_match($chatRegex, $line, $matches) !== 1) return null;

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
    return array_reduce(
        $chats,
        function(array $sortedChats, Chat $chat) use ($asArray) {
            $sender     = $chat->getSender();
            $senderChats= $sortedChats[$sender] ?? [];

            array_push($senderChats, $asArray ? $chat->toArray() : $chat);
            $sortedChats[$sender] = $senderChats;

            return $sortedChats;
    }, []);

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


function saveParseResults(
    string $filename,
    string $jsonFilename,
    string $summaryFilename,
    array $chats,
    bool $dumpOnConsole=true
): bool
{
    $jsonFile = fopen($jsonFilename, "w");
    if (! $jsonFile) {
        throw new Exception("Could not open JSON output file");
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
    $summaryFile = fopen($summaryFilename, "w");
    if (! $summaryFile) {
        throw new Exception("Could not open file");
    }

    $summary = getSummaryFromTemplate($filename, $chats);

    fputs($summaryFile, $summary);
    fclose($summaryFile);

    if ($dumpOnConsole) {
        echo $summary;
    }

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
            "|  % 3d  | %- 32s  | % 12s |",
            $index + 1,
            $sender,
            $chatsBySender[$sender]
        ),
        $senders, array_keys($senders)
    ));
}