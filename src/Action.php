<?php

namespace ZhenyaGR\TGZ;

class Action
{
    private string $id;
    private mixed $condition;
    private $handler;
    public array $messageData = [];
    public string $queryText = '';
    public string $button_redirect = '';

    public function __construct(string $id, mixed $condition)
    {
        $this->id = $id;
        $this->condition = $condition;
    }

    public function func(callable $handler): self
    {
        $this->handler = \Closure::fromCallable($handler);

        return $this;
    }

    public function redirect(string $id): self
    {
        $this->button_redirect = $id;

        return $this;
    }

    public function text(string $text = ''): self
    {
        $this->messageData['text'] = $text;

        return $this;
    }

    public function edit(string $text = ''): self
    {
        $this->messageData['text'] = $text;
        $this->messageData['edit'] = true;

        return $this;
    }

    public function img(string $img): self
    {
        $this->messageData['img'] = $img;

        return $this;
    }

    public function query(string $query): self
    {
        return $this->setQueryText($query);
    }

    public function kbd(array $buttons, bool $one_time = false,
        bool $resize = true,
    ): self {
        $this->messageData['kbd'] = $buttons;
        $this->messageData['inline'] = false;
        $this->messageData['oneTime'] = $one_time;
        $this->messageData['resize'] = $resize;

        return $this;
    }

    public function inlineKbd(array $buttons): self
    {
        $this->messageData['kbd'] = $buttons;
        $this->messageData['inline'] = true;
        $this->messageData['oneTime'] = false;
        $this->messageData['resize'] = false;

        return $this;
    }

    public function removeKbd(): self
    {
        $this->messageData['remove_keyboard'] = true;

        return $this;
    }

    public function getQueryText(): string
    {
        return $this->queryText;
    }

    public function getMessageData(): array
    {
        return $this->messageData;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCondition(): mixed
    {
        return $this->condition;
    }

    public function getHandler(): ?callable
    {
        return $this->handler;
    }

    public function setHandler(?callable $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function setMessageData(array $messageData): self
    {
        $this->messageData = $messageData;

        return $this;
    }

    public function setQueryText(?string $queryText): self
    {
        $this->queryText = $queryText;

        return $this;
    }
}