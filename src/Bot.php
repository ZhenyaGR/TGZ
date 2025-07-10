<?php

namespace ZhenyaGR\TGZ;

class Bot
{

    private TGZ $tg;

    private array $routes
        = [
            'bot_command'    => [],
            'command'        => [],
            'text_exact'     => [],
            'text_preg'      => [],
            'callback_query' => [],
            'fallback'       => null,
        ];

    public array $buttons
        = [
            'btn'    => [],
            'action' => [],
        ];

    private array $pendingRedirects = [];

    public function __construct(TGZ $tg)
    {
        $this->tg = $tg;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string $id   Уникальный идентификатор кнопки.
     * @param string $text Текст кнопки.
     *
     * @return Action
     */
    public function btn(string $id, string $text): Action
    {
        $this->buttons['btn'][$id] = $text;

        $route = new Action($id, $text);
        $this->buttons['action'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string       $id      Уникальный идентификатор маршрута.
     * @param array|string $command Текст команды бота, например '/start'.
     *
     * @return Action
     */
    public function onBotCommand(string $id, array|string $command): Action
    {
        $route = new Action($id, $command);
        $this->routes['bot_command'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string       $id      Уникальный идентификатор маршрута.
     * @param array|string $command Текст команды, например '!start'.
     *
     * @return Action
     */
    public function onCommand(string $id, array|string $command): Action
    {
        $route = new Action($id, $command);
        $this->routes['command'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для точного совпадения текста.
     *
     * @param string       $id   Уникальный идентификатор.
     * @param array|string $text Текст для совпадения.
     *
     * @return Action
     */
    public function onText(string $id, array|string $text): Action
    {
        $route = new Action($id, $text);
        $this->routes['text_exact'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для текста по регулярному выражению.
     *
     * @param string       $id      Уникальный идентификатор.
     * @param array|string $pattern Регулярное выражение.
     *
     * @return Action
     */
    public function onTextPreg(string $id, array|string $pattern): Action
    {
        $route = new Action($id, $pattern);
        $this->routes['text_preg'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для callback-запроса.
     *
     * @param string $id   Уникальный идентификатор.
     * @param string $data Данные из callback-кнопки.
     *
     * @return Action
     */
    public function onCallback(string $id, string $data): Action
    {
        $route = new Action($id, $data);
        $this->routes['callback_query'][$id] = $route;

        return $route;
    }

    /**
     * Устанавливает обработчик по умолчанию (fallback).
     *
     * @return Action
     */
    public function onDefault(): Action
    {
        $route = new Action('fallback', null);
        $this->routes['fallback'] = $route;

        return $route;
    }

    private function dispatch(): void
    {
        $this->tg->initType($type);
        $this->tg->initText($text);
        $this->tg->initCallbackData($callback_data);

        if ($type === 'bot_command') {
            $command = explode(' ', $text)[0];

            foreach ($this->routes['bot_command'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $condition) {
                    if ($condition === $command) {
                        $this->dispatchAnswer($route, $type);

                        return;
                    }
                }
            }
        }

        if ($type === 'text') {
            $userText = mb_convert_encoding($text, 'UTF-8');

            // 1. Проверяем текстовые команды (onCommand)
            foreach ($this->routes['command'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $condition) {
                    $commandFromRoute = mb_convert_encoding(
                        $condition, 'UTF-8',
                    );
                    if (str_starts_with($userText, $commandFromRoute)) {
                        $commandLength = strlen($commandFromRoute);

                        if (!isset($userText[$commandLength])
                            || $userText[$commandLength] === ' '
                            || $userText[$commandLength] === "\n"
                        ) {
                            $this->dispatchAnswer($route, $type);

                            return;
                        }
                    }
                }
            }

            // 2. Проверяем точное совпадение (onText)
            foreach ($this->routes['text_exact'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $condition) {
                    if ($condition === $text) {
                        $this->dispatchAnswer($route, $type);

                        return;
                    }
                }
            }

            // 3. Проверяем текстовые кнопки
            foreach ($this->buttons['action'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $condition) {
                    if ($condition === $text) {
                        if (!empty($route->button_redirect)) {
                            $targetAction = $this->findActionById(
                                $route->button_redirect,
                            );

                            if ($targetAction === null) {
                                throw new \LogicException(
                                    "Button redirect target with ID '{$route->button_redirect}' not found.",
                                );
                            }

                            $this->executeAction($targetAction);

                            return;

                        }

                        $this->dispatchAnswer($route, 'text_button');

                        return;
                    }
                }
            }

            // 4. Проверяем регулярные выражения (onTextPreg)
            foreach ($this->routes['text_preg'] as $route) {
                $patterns = (array)$route->getCondition();
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $text, $matches)) {
                        $this->dispatchAnswer($route, $type, $matches);

                        return;
                    }
                }
            }

        }

        if ($type === 'callback_query') {
            foreach ($this->buttons['action'] as $route) {
                if ($route->getId() === $callback_data) {
                    $this->dispatchAnswer($route, 'button_'.$type);

                    return;
                }
            }

            foreach ($this->routes['callback_query'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $condition) {
                    if ($condition === $callback_data) {
                        $this->dispatchAnswer($route, $type);

                        return;
                    }
                }
            }
        }

        // Fallback, если ни один маршрут не сработал
        if ($this->routes['fallback'] !== null) {
            $this->dispatchAnswer($this->routes['fallback'], 'text');

            return;
        }

    }

    private function dispatchAnswer($route, $type, $matches = null)
    {
        if (!empty($route->button_redirect)) {
            $targetAction = $this->findActionById($route->button_redirect);

            if ($targetAction === null) {
                throw new \LogicException(
                    "Button redirect target with ID '{$route->button_redirect}' not found.",
                );
            }

            $this->tg->initQuery($query_id);
            if ($query_id && !empty($route->getQueryText())) {
                $this->tg->answerCallbackQuery(
                    $query_id, ['text' => $route->getQueryText()],
                );
            }

            return $this->executeAction($targetAction, $matches);
        }

        $handler = $route->getHandler();
        if (!empty($handler)) {
            $handler($this->tg, $matches);

            return null;
        }

        if ($type === 'bot_command' || $type === 'text'
            || $type === 'text_button'
        ) {
            $messageData = $route->getMessageData();
            if (empty($messageData)) {
                return null;
            }

            $this->constructMessage($messageData);

            return null;
        }

        if ($type === 'button_callback_query') {
            $this->tg->initQuery($query_id);
            $this->tg->answerCallbackQuery(
                $query_id, ['text' => $route->getQueryText()],
            );

            $messageData = $route->getMessageData();

            if (empty($messageData)) {
                $this->tg->initCallbackData($callback_data);
                foreach ($this->routes['callback_query'] as $route2) {
                    if ($route2->getCondition() === $callback_data) {
                        $this->dispatchAnswer($route2, 'callback_query');

                        return null;
                    }
                }

                return null;
            }

            $this->constructMessage($messageData);

            return null;

        }

        if ($type === 'callback_query') {
            $this->tg->initQuery($query_id);
            $this->tg->answerCallbackQuery(
                $query_id, ['text' => $route->getQueryText()],
            );

            $messageData = $route->getMessageData();

            if (empty($messageData)) {
                return null;
            }

            $this->constructMessage($messageData);

            return null;

        }

        return null;
    }

    private function constructMessage($messageData): array
    {
        $msg = new Message('', $this->tg);

        if (isset($messageData['text'])) {
            $msg->text($messageData['text']);
        }
        if (isset($messageData['img'])) {
            $msg->img($messageData['img']);
        }
        if (isset($messageData['kbd'])) {
            $this->constructKbd(
                $msg, $messageData['kbd'], $messageData['inline'],
                $messageData['oneTime'], $messageData['resize'],
            );
        }
        if (isset($messageData['remove_keyboard'])) {
            $msg->kbd(remove_keyboard: true);
        }

        if (isset($messageData['edit']) && $messageData['edit'] === true) {
            return $msg->sendEdit();
        }

        return $msg->send();

    }

    private function constructKbd(&$msg, $kbd, $inline, $oneTime, $resize)
    {
        $keyboardLayout = [];
        $definedButtons = $this->buttons['btn'];

        foreach ($kbd as $row) {
            $keyboardRow = [];

            foreach ($row as $button) {
                if (is_string($button)) {
                    $buttonId = $button;
                    if (isset($definedButtons[$buttonId])) {
                        if ($inline) {
                            $keyboardRow[] = [
                                'text'          => $definedButtons[$buttonId],
                                'callback_data' => $buttonId,
                            ];
                        } else {
                            $keyboardRow[] = [
                                'text' => $definedButtons[$buttonId],
                            ];
                        }
                    }
                } elseif (is_array($button)
                    && ($inline
                        && (isset($button['callback_data'])
                            || isset($button['url'])))
                ) {
                    $keyboardRow[] = $button;
                }

            }

            if (!empty($keyboardRow)) {
                $keyboardLayout[] = $keyboardRow;
            }

        }

        if (!empty($keyboardLayout)) {
            $msg->kbd($keyboardLayout, $inline, $oneTime, $resize);
        }

    }

    public function run(?string $id = null): void
    {
        if ($id === null) {
            $this->processRedirects();

            $this->dispatch();
        } else {
            $actionToRun = $this->findActionById($id);

            if ($actionToRun === null) {
                throw new \InvalidArgumentException(
                    "Cannot run handler: Action with ID '$id' not found.",
                );
            }

            $this->executeAction($actionToRun);
        }

    }

    private function executeAction(Action $action, ?array $matches = null)
    {
        $handler = $action->getHandler();
        if ($handler !== null) {
            return $handler($this->tg, $matches);
        }

        $messageData = $action->getMessageData();
        if (!empty($messageData)) {
            return $this->constructMessage($messageData);
        }

        return null;
    }

    /**
     * Перенаправляет один маршрут на другой.
     * Копирует обработчик и данные ответа из маршрута $to_id в маршрут $id.
     *
     * @param string $id    ID исходного маршрута (откуда редирект).
     * @param string $to_id ID целевого маршрута (куда редирект).
     *
     * @throws \InvalidArgumentException Если один из маршрутов не найден.
     */
    public function redirect(string $id, string $to_id): void
    {
        $this->pendingRedirects[] = ['from' => $id, 'to' => $to_id];
    }

    private function processRedirects(): void
    {
        foreach ($this->pendingRedirects as $redirect) {
            $sourceAction = $this->findActionById($redirect['from']);
            if ($sourceAction === null) {
                throw new \LogicException(
                    "Redirect source route with ID '{$redirect['from']}' not found.",
                );
            }

            $targetAction = $this->findActionById($redirect['to']);
            if ($targetAction === null) {
                throw new \LogicException(
                    "Redirect target route with ID '{$redirect['to']}' not found.",
                );
            }

            $sourceAction
                ->setHandler($targetAction->getHandler())
                ->setMessageData($targetAction->getMessageData())
                ->setQueryText($targetAction->getQueryText());
        }

        $this->pendingRedirects = [];
    }

    private function findActionById(string $id): ?Action
    {
        foreach ($this->routes as $type => $actions) {
            if ($type === 'fallback') {
                if ($this->routes['fallback']?->getId() === $id) {
                    return $this->routes['fallback'];
                }
                continue;
            }

            if (isset($actions[$id])) {
                return $actions[$id];
            }
        }

        if (isset($this->buttons['action'][$id])) {
            return $this->buttons['action'][$id];
        }

        return null;
    }

}

