<?php

declare(strict_types=1);

$filename = dirname(__DIR__) . DIRECTORY_SEPARATOR . "samples/mce.txt";

if (! file_exists($filename)) {
    echo "File not found";
    exit;
}

$file = fopen($filename, "r");

// Keep track of chats
$chats = [];

// Keep track of max chat
$maxChat = [0, null];

// Keep track of total chat
$total = 0;

while (($line = fgets($file)) !== false) {

    if (($lineInfo = parseLine($line)) === NULL) {
        continue;
    }

    /** @var DateTime $dateSent */
    $dateSent = $lineInfo['datetime'];
    $setDate = new DateTime('-1 year');
    $messageSentAfterSetDate = ! $dateSent->diff($setDate)->invert;
    if ($messageSentAfterSetDate) {
        continue;
    }

    // Update chats
    $sender = $lineInfo['sender'];
    $noOfChats = ($chats[$sender] ?? 0) + 1;
    $chats[$sender] = $noOfChats;

    // Update max
    [$maxChatNo, $maxChatSender] = $maxChat;
    if ($noOfChats > $maxChatNo) {
        $maxChatNo = $noOfChats;
        $maxChatSender = $sender;
    }

    $maxChat = [$maxChatNo, $maxChatSender];

    $total++;

    // $x = json_encode($dateSent);
    // $y = substr($lineInfo['message'], 0, 20);
    // echo "$sender -> {$x} ($y...)" . PHP_EOL;

    // if ($counter++ === 4000) break;
}

// Chats
print_r($chats);

// Sorted from max. to min.
$sortedChats = $chats;
arsort($sortedChats);

// 
echo "--------------- Leaderboard ($total) ----------------" . PHP_EOL;

$counter = 0;
foreach($sortedChats as $sender => $noOfChats) {
    if ($counter++ === 100) break;

    $sender = str_pad($sender, 30, "-");
    echo "$counter) \t$sender\t$noOfChats" . PHP_EOL;

}


// 
print_r($maxChat);


function parseLine(string $line): ?array {
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

    return [
        'datetime'  => new DateTime("$date $time"),
        'sender'    => $matches[5],
        'message'   => $matches[6]
    ];
}