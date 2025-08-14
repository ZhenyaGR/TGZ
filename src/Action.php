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

    /**
     * Устанавливает обработчик для маршрута.
     *
     * @param callable $handler Обработчик
     */
    public function func(callable $handler): self
    {
        $this->handler = \Closure::fromCallable($handler);

        return $this;
    }

    /**
     * Перенаправляет один маршрут на другой.
     * Копирует обработчик и данные ответа из маршрута $id в текущий маршрут.
     *
     * @param string $id    ID маршрута, куда перенаправлять
     *
     * @throws \InvalidArgumentException Если один из маршрутов не найден.
     */
    public function redirect(string $id): self
    {
        $this->button_redirect = $id;

        return $this;
    }

    /**
     * Задает текст сообщения, которое будет отправлено в ответ
     *
     * @param string $text    Текст сообщения
     */
    public function text(string $text = ''): self
    {
        $this->messageData['text'] = $text;

        return $this;
    }

    /**
     * Изменяет текст сообщения
     *
     * @param string $text    Новый текст
     */
    public function editText(string $text = ''): self
    {
        $this->messageData['text'] = $text;
        $this->messageData['editText'] = true;

        return $this;
    }

    /**
     * Изменяет текст описания
     *
     * @param string $text    Новый текст
     */
    public function editCaption(string $text = ''): self
    {
        $this->messageData['text'] = $text;
        $this->messageData['editCaption'] = true;

        return $this;
    }

    /**
     * Добавляет изображение к сообщению
     *
     * @param string|array $img    Ссылка или массив ссылок (ID) изображений
     */
    public function img(string|array $img): self
    {
        $this->messageData['img'] = $img;

        return $this;
    }

    /**
     * Задает всплывающий текст при нажатии на кнопку
     *
     * @param string $query    Всплывающий текст
     */
    public function query(string $query): self
    {
        return $this->setQueryText($query);
    }

    /**
     * Добавляет клавиатуру к сообщению
     *
     * @param array $buttons    Кнопки клавиатуры
     * @param bool  $one_time   Показывать клавиатуру однократно?
     * @param bool  $resize     Растягивать клавиатуру?
     */
    public function kbd(array $buttons, bool $one_time = false,
        bool $resize = true,
    ): self {
        $this->messageData['kbd'] = $buttons;
        $this->messageData['inline'] = false;
        $this->messageData['oneTime'] = $one_time;
        $this->messageData['resize'] = $resize;

        return $this;
    }

    /**
     * Добавляет inline-клавиатуру к сообщению
     *
     * @param array $buttons    Кнопки клавиатуры
     */
    public function inlineKbd(array $buttons): self
    {
        $this->messageData['kbd'] = $buttons;
        $this->messageData['inline'] = true;
        $this->messageData['oneTime'] = false;
        $this->messageData['resize'] = false;

        return $this;
    }

    /**
     * Удаляет клавиатуру
     */
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