<?php

namespace ZhenyaGR\TGZ;

class Bot
{

    private TGZ $tg;
    private ApiClient $api;
    private UpdateContext $context;

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
        $this->api = $tg->api;
        $this->context = $tg->context;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string      $id   Уникальный идентификатор кнопки.
     * @param string|null $text Текст кнопки.
     *
     * @return Action
     */
    public function btn(string $id, string $text = null): Action
    {
        $this->buttons['btn'][$id] = $text ?? $id;

        $route = new Action($id, $text ?? $id);
        $this->buttons['action'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string            $id      Уникальный идентификатор маршрута.
     * @param array|string|null $command Текст команды бота, например '/start'.
     *
     * @return Action
     */
    public function onBotCommand(string $id, array|string $command = null): Action
    {
        $route = new Action($id, $command ?? $id);
        $this->routes['bot_command'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string       $id      Уникальный идентификатор маршрута.
     * @param array|string|null $command Текст команды, например '!start'.
     *
     * @return Action
     */
    public function onCommand(string $id, array|string $command = null): Action
    {
        $route = new Action($id, $command ?? $id);
        $this->routes['command'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для точного совпадения текста.
     *
     * @param string       $id   Уникальный идентификатор.
     * @param array|string|null $text Текст для совпадения.
     *
     * @return Action
     */
    public function onText(string $id, array|string $text = null): Action
    {
        $route = new Action($id, $text ?? $id);
        $this->routes['text_exact'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для текста по регулярному выражению.
     *
     * @param string       $id      Уникальный идентификатор.
     * @param array|string|null $pattern Регулярное выражение.
     *
     * @return Action
     */
    public function onTextPreg(string $id, array|string $pattern = null): Action
    {
        $route = new Action($id, $pattern ?? $id);
        $this->routes['text_preg'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для callback-запроса.
     *
     * @param string $id   Уникальный идентификатор.
     * @param string|null $data Данные из callback-кнопки.
     *
     * @return Action
     */
    public function onCallback(string $id, string $data): Action
    {
        $route = new Action($id, $data ?? $id);
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
        $type = $this->context->getType();
        $text = $this->context->getText();
        $callback_data = $this->context->getCallbackData();

        if ($type === 'text' || $type === 'bot_command') {

            $userText = strtolower(mb_convert_encoding($text, 'UTF-8'));

            // 1. Проверяем команды бота (onBotCommand)
            if ($type === 'bot_command') {
                $words = explode(' ', $userText);
                $command = $words[0];
                unset($words[0]);
                $refs = implode(' ', $words);

                foreach ($this->routes['bot_command'] as $route) {
                    $conditions = (array)$route->getCondition();
                    foreach ($conditions as $condition) {
                        if ($condition === $command) {
                            $this->dispatchAnswer($route, $type, [$refs]);

                            return;
                        }
                    }
                }
            }

            // 2. Проверяем текстовые команды (onCommand)
            foreach ($this->routes['command'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $commandPattern) {
                    if (preg_match('/%[swn]/', $commandPattern)) {
                        $regex = $this->convertCommandPatternToRegex(
                            $commandPattern,
                        );
                        if (preg_match($regex, $userText, $matches)) {
                            $args = array_slice($matches, 1);
                            $this->dispatchAnswer($route, $type, $args);

                            return;
                        }
                    } else {
                        $commandFromRoute = mb_convert_encoding(
                            $commandPattern, 'UTF-8',
                        );
                        if (str_starts_with($userText, $commandFromRoute)) {
                            $commandLength = strlen($commandFromRoute);
                            if (!isset($userText[$commandLength])
                                || $userText[$commandLength] === ' '
                                || $userText[$commandLength] === "\n"
                            ) {
                                $argsString = trim(
                                    substr($userText, $commandLength),
                                );
                                $args = ($argsString === '')
                                    ? []
                                    : preg_split(
                                        '/\s+/', $argsString, -1,
                                        PREG_SPLIT_NO_EMPTY,
                                    );
                                $this->dispatchAnswer($route, $type, $args);

                                return;
                            }
                        }
                    }
                }
            }

            // 3. Проверяем точное совпадение (onText)
            foreach ($this->routes['text_exact'] as $route) {
                $conditions = (array)$route->getCondition();
                foreach ($conditions as $condition) {
                    if ($condition === $text) {
                        $this->dispatchAnswer($route, $type);

                        return;
                    }
                }
            }

            // 4. Проверяем текстовые кнопки
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

            // 5. Проверяем регулярные выражения (onTextPreg)
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
            // 6. Проверяем inline-кнопки
            foreach ($this->buttons['action'] as $route) {
                if ($route->getId() === $callback_data) {
                    $this->dispatchAnswer($route, 'button_'.$type);

                    return;
                }
            }

            // 7. Проверяем callback_data
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

    private function convertCommandPatternToRegex(string $pattern): string
    {
        // Находим все "токены": паттерны (%w и т.д.) или группы непробельных символов (\S+)
        preg_match_all('/%[swn]|\S+/u', $pattern, $matches);
        $tokens = $matches[0];

        $regexParts = [];
        foreach ($tokens as $token) {
            switch ($token) {
                case '%s': // string (до конца строки)
                    $regexParts[] = '(.+)';
                    break;
                case '%w': // word (одно слово)
                    $regexParts[] = '(\S+)';
                    break;
                case '%n': // number (только цифры)
                    $regexParts[] = '(\d+)';
                    break;
                default: // статическая часть команды (например, "!cmd" или "+")
                    $regexParts[] = preg_quote($token, '/');
                    break;
            }
        }

        // Соединяем части через \s+, что требует хотя бы одного пробельного символа между ними
        $regex = '^'.implode('\s+', $regexParts).'$';

        return '/'.$regex.'/u';
    }


    private function dispatchAnswer($route, $type, array $other_data = [])
    {
        if (!empty($route->button_redirect)) {
            $targetAction = $this->findActionById($route->button_redirect);

            if ($targetAction === null) {
                throw new \LogicException(
                    "Button redirect target with ID '{$route->button_redirect}' not found.",
                );
            }

            $query_id = $this->context->getQueryId();

            if ($query_id && !empty($route->getQueryText())) {
                $this->tg->answerCallbackQuery(
                    $query_id, ['text' => $route->getQueryText()],
                );
            }

            return $this->executeAction($targetAction, $other_data);
        }

        $handler = $route->getHandler();
        if (!empty($handler)) {
            $handler(...$other_data);

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
            $query_id = $this->context->getQueryId();
            $this->tg->answerCallbackQuery(
                $query_id, ['text' => $route->getQueryText()],
            );

            $messageData = $route->getMessageData();

            if (empty($messageData)) {
                $callback_data = $this->context->getCallbackData();

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
            $query_id = $this->context->getQueryId();
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
        $msg = new Message('', '', $this->api, $this->context);

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

    private function executeAction(Action $action, ?array $other_files = [])
    {
        $handler = $action->getHandler();
        if ($handler !== null) {
            return $handler(...$other_files);
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

