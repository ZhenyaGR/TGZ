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
    public \Closure|null $middleware_handler = null;
    private array $access_ids = [];
    private array $no_access_ids = [];
    private \Closure|null $access_handler = null;
    private \Closure|null $no_access_handler = null;

    public function __construct(string $id, mixed $condition)
    {
        $this->id = $id;
        $this->condition = $condition;
    }

    /**
     * Устанавливает middleware для маршрута.
     *
     * @param callable $handler Обработчик
     *
     * @return Bot
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/middleware
     */
    public function middleware(callable $handler): self
    {
        $this->middleware_handler = \Closure::fromCallable($handler);

        return $this;
    }


    /**
     * Устанавливает обработчик для маршрута.
     *
     * @param callable $handler Обработчик
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/func
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
     * @param string $id ID маршрута, куда перенаправлять
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/redirect
     */
    public function redirect(string $id): self
    {
        $this->button_redirect = $id;

        return $this;
    }

    /**
     * Задает текст сообщения, которое будет отправлено в ответ
     *
     * @param string $text Текст сообщения
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/text
     */
    public function text(string $text = ''): self
    {
        $this->messageData['text'] = $text;

        return $this;
    }

    /**
     * Изменяет текст сообщения
     *
     * @param string $text Новый текст
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/editText
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
     * @param string $text Новый текст
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/editCaption
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
     * @param string|array $img Ссылка или массив ссылок (ID) изображений
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/img
     */
    public function img(string|array $img): self
    {
        $this->messageData['img'] = $img;

        return $this;
    }

    /**
     * Задает всплывающий текст при нажатии на кнопку
     *
     * @param string $query Всплывающий текст
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/query
     */
    public function query(string $query): self
    {
        return $this->setQueryText($query);
    }

    /**
     * Добавляет клавиатуру к сообщению
     *
     * @param array $buttons  Кнопки клавиатуры
     * @param bool  $one_time Показывать клавиатуру однократно?
     * @param bool  $resize   Растягивать клавиатуру?
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/kbd
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
     * @param array $buttons Кнопки клавиатуры
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/inlineKbd
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
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/removeKbd
     */
    public function removeKbd(): self
    {
        $this->messageData['remove_keyboard'] = true;

        return $this;
    }

    /**
     * Устанавливает список ID пользователей, которым доступен маршрут
     *
     * @param int|array     $ids Идентификаторы пользователей
     * @param callable|null $handler Обработчик, если доступ к маршруту запрещен
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/access
     */
    public function access(int|array $ids, ?callable $handler = null): self
    {
        $this->access_ids =  is_numeric($ids) ? [$ids] : $ids;
        $this->access_handler = ($handler !== null) ? \Closure::fromCallable($handler) : null;

        return $this;
    }

    /**
     * Устанавливает список ID пользователей, которым не доступен маршрут
     *
     * @param int|array     $ids Идентификаторы пользователей
     * @param callable|null $handler Обработчик, если доступ к маршруту запрещен
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/actionMethods/noAccess
     */
    public function noAccess(int|array $ids, ?callable $handler = null): self
    {
        $this->no_access_ids = is_numeric($ids) ? [$ids] : $ids;

        $this->no_access_handler = ($handler !== null) ? \Closure::fromCallable($handler) : null;

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

    public function getAccessIds(): array
    {
        return $this->access_ids;
    }

    public function getNoAccessIds(): array
    {
        return $this->no_access_ids;
    }

    public function getAccessHandler(): ?callable
    {
        return $this->access_handler;
    }

    public function getNoAccessHandler(): ?callable
    {
        return $this->no_access_handler;
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