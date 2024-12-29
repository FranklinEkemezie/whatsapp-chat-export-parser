<?php

declare(strict_types=1);

class Chat implements JsonSerializable
{


    public function __construct(
        private string $sender,
        private string $message,
        private DateTime $timestamp
    )
    {

    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function sentAfter(DateTime $targetDate): bool
    {
        return (bool) $this->timestamp->diff($targetDate)->invert;
    }

    public function sentBefore(DateTime $targetDate): bool
    {
        return (bool) ! $this->timestamp->diff($targetDate)->invert;
    }

    public function toArray(): array
    {
        return [
            'sender'    => $this->sender,
            'message'   => $this->message,
            'timestamp' => $this->timestamp->format("d-m-Y h:i a")
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

}