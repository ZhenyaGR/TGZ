<?php

namespace ZhenyaGR\TGZ;

class Bot
{

    private TGZ $tg;
    private UpdateContext $context;
    private array $ctx = [];

    private array $routes
        = [
            'bot_command'         => [],
            'command'             => [],
            'text_exact'          => [],
            'text_preg'           => [],
            'callback_query'      => [],
            'sticker_fallback'    => null,
            'message_fallback'    => null,
            'photo_fallback'      => null,
            'video_fallback'      => null,
            'audio_fallback'      => null,
            'voice_fallback'      => null,
            'document_fallback'   => null,
            'video_note_fallback' => null,
            'new_chat_members'    => null,
            'left_chat_member'    => null,
            'fallback'            => null,
        ];

    public array $buttons
        = [
            'btn'    => [],
            'action' => [],
        ];

    private array $pendingRedirects = [];

    private \Closure|null $middleware_handler = null;

    public function __construct(TGZ $tg)
    {
        $this->tg = $tg;
        $this->context = $tg->context;
    }

    /**
     * Устанавливает middleware
     *
     * @param callable $handler Обработчик
     *
     * @return Bot
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/middleware
     */
    public function middleware(callable $handler): self
    {
        $this->middleware_handler = \Closure::fromCallable($handler);

        return $this;
    }

    /**
     * Создает маршрут для кнопки.
     *
     * @param string      $id   Уникальный идентификатор кнопки.
     * @param string|null $text Текст кнопки.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/btn
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
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onBotCommand
     */
    public function onBotCommand(string $id, array|string $command = null,
    ): Action {
        $route = new Action($id, $command ?? $id);
        $this->routes['bot_command'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для команды.
     *
     * @param string            $id      Уникальный идентификатор маршрута.
     * @param array|string|null $command Текст команды, например '!start'.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onCommand
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
     * @param string            $id   Уникальный идентификатор.
     * @param array|string|null $text Текст для совпадения.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onText
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
     * @param string            $id      Уникальный идентификатор.
     * @param array|string|null $pattern Регулярное выражение.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onTextPreg
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
     * @param string      $id   Уникальный идентификатор.
     * @param string|null $data Данные из callback-кнопки.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onCallback
     */
    public function onCallback(string $id, string $data = null): Action
    {
        $route = new Action($id, $data ?? $id);
        $this->routes['callback_query'][$id] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех стикеров.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onSticker
     */
    public function onSticker(): Action
    {
        $route = new Action('sticker_fallback', null);
        $this->routes['sticker_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех текстовых сообщений.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onMessage
     */
    public function onMessage(): Action
    {
        $route = new Action('message_fallback', null);
        $this->routes['message_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех сообщений с фото.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onPhoto
     */
    public function onPhoto(): Action
    {
        $route = new Action('photo_fallback', null);
        $this->routes['photo_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех сообщений с видео.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onVideo
     */
    public function onVideo(): Action
    {
        $route = new Action('video_fallback', null);
        $this->routes['video_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех сообщений с аудио.
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onAudio
     */
    public function onAudio(): Action
    {
        $route = new Action('audio_fallback', null);
        $this->routes['audio_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех голосовых сообщений
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onVoice
     */
    public function onVoice(): Action
    {
        $route = new Action('voice_fallback', null);
        $this->routes['voice_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех документов
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onDocument
     */
    public function onDocument(): Action
    {
        $route = new Action('document_fallback', null);
        $this->routes['document_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для всех видео-сообщений
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onVideoNote
     */
    public function onVideoNote(): Action
    {
        $route = new Action('video_note_fallback', null);
        $this->routes['video_note_fallback'] = $route;

        return $route;
    }

    /**
     * Создает маршрут для нового(ых) участника(ов) чата
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onNewChatMember
     */
    public function onNewChatMember(): Action
    {
        $route = new Action('new_chat_members', null);
        $this->routes['new_chat_members'] = $route;

        return $route;
    }


    /**
     * Создает маршрут вышедшего участника чата
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onLeftChatMember
     */
    public function onLeftChatMember(): Action
    {
        $route = new Action('left_chat_member', null);
        $this->routes['left_chat_member'] = $route;

        return $route;
    }

    /**
     * Устанавливает обработчик по умолчанию (fallback).
     *
     * @return Action
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/onDefault
     */
    public function onDefault(): Action
    {
        $route = new Action('fallback', null);
        $this->routes['fallback'] = $route;

        return $route;
    }

    private function dispatch(): void
    {
        $next = function(): void {
            $this->processRoutes();
        };

        if (is_callable($this->middleware_handler)) {
            ($this->middleware_handler)($this->tg, $next);
        } else {
            $next();
        }
    }

    private function processRoutes()
    {
        $type = $this->context->getType();
        $text = $this->context->getText();
        $callback_data = $this->context->getCallbackData();

        if ($type === 'text' || $type === 'bot_command') {
            if (!empty($text)) {
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

                // 6. Проверяем обычное сообщение
                if ($this->routes['message_fallback'] !== null) {
                    $this->dispatchAnswer(
                        $this->routes['message_fallback'], 'text',
                    );

                    return;
                }

            }

            // 7. Проверяем сообщение с фото
            if ($this->tryProcessFallbackMedia('photo')) {
                return;
            }

            // 8. Проверяем сообщение с аудио
            if ($this->tryProcessFallbackMedia('audio')) {
                return;
            }

            // 9. Проверяем видео
            if ($this->tryProcessFallbackMedia('video')) {
                return;
            }

            // 10. Проверяем стикеры
            if ($this->tryProcessFallbackMedia('sticker')) {
                return;
            }

            // 11. Проверяем голосовые
            if ($this->tryProcessFallbackMedia('voice')) {
                return;
            }

            // 11. Проверяем документы
            if ($this->tryProcessFallbackMedia('document')) {
                return;
            }

            // 12. Проверяем видео-сообщения
            if ($this->tryProcessFallbackMedia('video_note')) {
                return;
            }

            if (!empty(
                $this->context->getUpdateData()['message']['new_chat_members']
                )
                && $this->routes['new_chat_members'] !== null
            ) {
                $this->dispatchAnswer(
                    $this->routes['new_chat_members'],
                    'text',
                    $this->context->getUpdateData(
                    )['message']['new_chat_members'],
                );
            }

            if (!empty(
                $this->context->getUpdateData()['message']['left_chat_member']
                )
                && $this->routes['left_chat_member'] !== null
            ) {
                $this->dispatchAnswer(
                    $this->routes['left_chat_member'],
                    'text',
                    [$this->context->getUpdateData(
                    )['message']['left_chat_member']],
                );
            }

            // Fallback, если ни один маршрут не сработал
            if ($this->routes['fallback'] !== null) {
                $this->dispatchAnswer($this->routes['fallback'], 'text');

                return;
            }
        }

        if ($type === 'callback_query') {
            // 13. Проверяем inline-кнопки
            foreach ($this->buttons['action'] as $route) {
                if ($route->getId() === $callback_data) {
                    $this->dispatchAnswer($route, 'button_'.$type);

                    return;
                }
            }

            // 14. Проверяем callback_data
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
    }

    private function tryProcessFallbackMedia(string $route_type): bool
    {
        $fallback_key = $route_type.'_fallback';

        // Проверяем, есть ли в сообщении медиа этого типа И существует ли для него fallback
        if (!empty($this->context->getUpdateData()['message'][$route_type])
            && $this->routes[$fallback_key] !== null
        ) {
            $this->dispatchAnswer(
                $this->routes[$fallback_key],
                'text',
            );

            return true;
        }

        return false;
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
        $this->ctx = [
            $route,
            $type,
            $other_data,
        ];

        $next = function(): void {
            $this->processAnswer();
        };

        if (is_callable($route->middleware_handler)) {
            ($route->middleware_handler)($this->tg, $next);
        } else {
            $next();
        }
    }

    private function processAnswer()
    {
        [$route, $type, $other_data] = $this->ctx;

        $user_id = $this->context->getUserId();

        if ($user_id) {
            $accessIds = $route->getAccessIds();
            if (!empty($accessIds) && !in_array($user_id, $accessIds)) {
                $accessHandler = $route->getAccessHandler();
                if (is_callable($accessHandler)) {
                    $accessHandler($this->tg);
                }

                return null;
            }

            $noAccessIds = $route->getNoAccessIds();
            if (!empty($noAccessIds) && in_array($user_id, $noAccessIds)) {
                $noAccessHandler = $route->getNoAccessHandler();
                if (is_callable($noAccessHandler)) {
                    $noAccessHandler($this->tg);
                }

                return null;
            }
        }

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
            $handler($this->tg, ...$other_data);

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
        $msg = new Message('', $this->tg);

        if (isset($messageData['text'])) {
            $msg->text($messageData['text']);
        }

        if (isset($messageData['img'])) {
            $msg->img($messageData['img']);
        }

        if (isset($messageData['gif'])) {
            $msg->gif($messageData['gif']);
        }

        if (isset($messageData['video'])) {
            $msg->video($messageData['video']);
        }

        if (isset($messageData['voice'])) {
            $msg->voice($messageData['voice']);
        }

        if (isset($messageData['audio'])) {
            $msg->audio($messageData['audio']);
        }

        if (isset($messageData['doc'])) {
            $msg->doc($messageData['doc']);
        }

        if (isset($messageData['sticker'])) {
            $msg->sticker($messageData['sticker']);
        }

        if (isset($messageData['dice'])) {
            $msg->dice($messageData['dice']);
        }

        if (isset($messageData['params'])) {
            $msg->params($messageData['params']);
        }

        if (isset($messageData['reply'])) {
            if ($messageData['reply'] === true) {
                $msg->reply();
            } else {
                $msg->reply($messageData['reply']);
            }
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

        if (isset($messageData['editText'])
            && $messageData['editText'] === true
        ) {
            return $msg->editText();
        }

        if (isset($messageData['editCaption'])
            && $messageData['editCaption'] === true
        ) {
            return $msg->editCaption();
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
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/botMethods/redirect
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


//onNewChatMember() или onUserJoined()
//Срабатывает, когда в чат/канал добавляется новый участник.
//onLeftChatMember() или onUserLeft()
//Срабатывает, когда участник покидает чат/канал.
//onEditedMessage()
//Срабатывает при редактировании пользователем своего сообщения (текста или медиа).