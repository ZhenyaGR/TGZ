<?php

namespace ZhenyaGR\TGZ;

use CURLFile;
use Exception;
use LogicException;
use ZhenyaGR\TGZ\Contracts\ApiInterface;
use ZhenyaGR\TGZ\Dto\UserDto;
use ZhenyaGR\TGZ\Dto\ChatDto;

class TGZ
{
    use ErrorHandler;

    public ApiInterface $api;
    public UpdateContext $context;
    public string $parseModeDefault = '';

    public function __construct(ApiInterface $api, UpdateContext $context)
    {
        $api->addTgz($this);
        $this->api = $api;
        $this->context = $context;
    }

    public static function create(string $token): self
    {
        http_response_code(200);
        echo 'ok';

        $api = new ApiClient($token);
        $context = UpdateContext::fromWebhook();

        return new self($api, $context);
    }

    /**
     * Выполняет вызов к Telegram Bot API.
     *
     * @param string     $method
     * @param array|null $params
     *
     * @return array
     *
     * @throws Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/callAPI
     */
    public function callAPI(string $method, ?array $params = []): array
    {
        return $this->api->callAPI($method, $params);
    }

    /**
     * Устанавливает режим парсинга по умолчанию для всех сообщений
     *
     * @param ?string $mode HTML, Markdown, MarkdownV2
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/defaultParseMode
     */
    public function defaultParseMode(?string $mode = null): self
    {
        if ($mode !== 'HTML' && $mode !== 'Markdown' && $mode !== 'MarkdownV2'
            && $mode !== null
        ) {
            $mode = null;
        }
        $this->parseModeDefault = $mode;

        return $this;
    }

    public function sendOK(): void
    {
        http_response_code(200);
        echo 'ok';
    }

    /**
     * Метод создает объект класса Message для конструктора сообщений
     *
     * @param string $text
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/msg
     */
    public function msg(string $text = ''): Message
    {
        return new Message(
            $text, $this,
        );
    }

    /**
     * Метод создает объект класса Poll для конструктора опросов
     *
     * @param string $type
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/poll
     */
    public function poll(string $type = 'regular'): Poll
    {
        return new Poll($type, $this->api, $this->context);
    }

    /**
     * Метод создает объект класса Inline для конструктора Inline-запросов
     *
     * @param string $type
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/inline
     */
    public function inline(string $type = ''): Inline
    {
        return new Inline($type, $this->parseModeDefault);
    }

    /**
     * Метод создает объект класса File для скачивания
     *
     * @param string $file_id
     *
     * @return File
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/file
     */
    public function file(string $file_id): File
    {
        return new File($file_id, $this->api);
    }

    /**
     * Метод удаляет одно или несколько сообщений
     *
     * @param array|int|null  $msg_ids
     * @param int|string|null $chat_id
     *
     * @return array
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/delMsg
     */
    public function delMsg(array|int $msg_ids = null,
        int|string $chat_id = null,
    ): array {
        if ($msg_ids === null) {
            $msg_ids = $this->getMsgId();
        }

        if ($chat_id === null) {
            $chat_id = $this->getChatId();
        }


        $bool = is_array($msg_ids);
        $method = $bool ? 'deleteMessages' : 'deleteMessage';
        $param = $bool ? 'messages_id' : 'message_id';

        return $this->api->callAPI(
            $method, ['chat_id' => $chat_id, $param => $msg_ids],
        );
    }

    /**
     * Метод копирует одно или несколько сообщений
     *
     * @param int|array|null  $msg_ids
     * @param int|string|null $chat_id
     * @param int|string|null $from_chat_id
     *
     * @return array
     *
     * @throws \Exception
     *
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/copyMsg
     */
    public function copyMsg(int|array $msg_ids = null,
        int|string $chat_id = null, int|string $from_chat_id = null,
    ): array {
        if ($msg_ids === null) {
            $msg_ids = $this->getMsgId();
        }

        if ($chat_id === null) {
            $chat_id = $this->getChatId();
        }

        if ($from_chat_id === null) {
            $from_chat_id = $chat_id;
        }

        $bool = is_array($msg_ids);
        $method = $bool ? 'copyMessages' : 'copyMessage';
        $param = $bool ? 'messages_id' : 'message_id';

        return $this->api->callAPI(
            $method, ['chat_id' => $chat_id, 'from_chat_id' => $from_chat_id,
                      $param    => $msg_ids],
        );
    }

    /**
     * Метод сначала загружает файл на сервер Telegram, а затем возвращает ID
     * файла, для последующей быстрой отправки
     *
     * @param string          $url
     * @param string          $type
     * @param int|string|null $chat_id
     *
     * @return null|string
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/getFileID
     */
    public function getFileID(string $url, string $type = 'document',
        int|string $chat_id = null,
    ): ?string {
        if (!in_array(
            $type,
            ['document', 'audio', 'photo', 'animation', 'video', 'video_note',
             'voice', 'sticker'],
        )
        ) {
            $type = 'document';
        }
        $params[$type] = new CURLFile($url);
        $params['chat_id'] = $chat_id;

        $method = 'send'.ucfirst($type);
        $result = $this->api->callAPI($method, $params);

        return File::getFileId($result, $type);
    }


    /**
     * Устанавливает действие бота
     *
     * @param string|null $action
     *
     * @return TGZ
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/sendAction
     */
    public function sendAction(?string $action = 'typing'): static
    {
        if (!in_array($action, [
            'typing',
            'upload_photo',
            'upload_video',
            'record_video',
            'record_voice',
            'upload_voice',
            'upload_document',
            'choose_sticker',
            'find_location',
            'record_video_note',
            'upload_video_note',
        ])
        ) {
            $action = 'typing';
        }
        $this->api->callAPI(
            'sendChatAction',
            ['chat_id' => $this->context->getChatId(), 'action' => $action],
        );

        return $this;
    }

    /**
     * Метод отправляет сообщение в чат
     *
     * @param int    $chatID
     * @param string $text
     * @param array  $params
     *
     * @return array
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/sendMessage
     */
    public function sendMessage(int $chatID, string $text, array $params = [],
    ): array {
        $params_message = [
            'chat_id' => $chatID,
            'text'    => $text,
        ];

        return $this->api->callAPI(
            'sendMessage', $params_message + $params,
        );
    }

    /**
     * Метод отправляет сообщение в чат
     *
     * @param string $message
     * @param array  $params
     *
     * @return array
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/reply
     */
    public function reply(string $message, array $params = []): array
    {
        if (!isset($params['chat_id'])) {
            $params['chat_id'] = $this->context->getChatId();
        }

        return $this->api->callAPI(
            'sendMessage', array_merge($params, ['text' => $message]),
        );
    }

    /**
     * Создает callback-кнопку
     *
     * @param string $buttonText Текст кнопки
     * @param string $buttonData Данные кнопки
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/buttons
     */
    public function buttonCallback(string $buttonText, string $buttonData,
    ): array {
        return [
            'text'          => $buttonText,
            "callback_data" => $buttonData,
        ];
    }

    /**
     * Создает url-кнопку
     *
     * @param string $buttonText Текст кнопки
     * @param string $buttonUrl  URL
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/buttons
     */
    public function buttonUrl(string $buttonText, string $buttonUrl): array
    {
        return [
            'text' => $buttonText,
            "url"  => $buttonUrl,
        ];
    }

    /**
     * Создает текстовую кнопку
     *
     * @param string $buttonText Текст кнопки
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/buttons
     */
    public function buttonText(string $buttonText): array
    {
        return [
            'text' => $buttonText,
        ];
    }

    /**
     * Метод отправляет ответ Телеграму на callback-запрос
     *
     * @param string $callbackQueryID
     * @param array  $options
     *
     * @return array
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/answers
     */
    public function answerCallbackQuery(string $callbackQueryID,
        array $options = [],
    ): array {
        $params = array_merge([
            'callback_query_id' => $callbackQueryID,
        ], $options);

        return $this->api->callAPI('answerCallbackQuery', $params);
    }

    /**
     * Метод отправляет ответ Телеграму на inline-запрос
     *
     * @param string $inlineQueryID
     * @param array  $results
     * @param array  $extra
     *
     * @return array
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/answers
     */
    public function answerInlineQuery(string $inlineQueryID, array $results,
        array $extra = [],
    ): array {
        $params = array_merge([
            'inline_query_id' => $inlineQueryID,
            'results'         => json_encode($results, JSON_THROW_ON_ERROR),
        ], $extra);

        return $this->api->callAPI('answerInlineQuery', $params);
    }

    /**
     * Инициализирует переменные из обновления
     *
     * @param $chat_id
     * @param $user_id
     * @param $text
     * @param $type
     * @param $callback_data
     * @param $query_id
     * @param $msg_id
     * @param $is_bot
     * @param $is_command
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/initVars
     */
    public function initVars(
        &$chat_id = null,
        &$user_id = null,
        &$text = null,
        &$type = null,
        &$callback_data = null,
        &$query_id = null,
        &$msg_id = null,
        &$is_bot = null,
        &$is_command = null,
    ): array {
        $update = $this->context->getUpdateData();

        $user_id = $this->context->getUserId();
        $chat_id = $this->context->getChatId();
        $text = $this->context->getText();
        $msg_id = $this->context->getMessageId();
        $type = $this->context->getType();
        $query_id = $this->context->getQueryId();
        $callback_data = $this->context->getCallbackData();

        if (isset($update['message'])) {
            $is_bot = $update['message']['from']['is_bot'];

            $is_command = (isset($update['message']['entities'][0]['type'])
                && $update['message']['entities'][0]['type'] === 'bot_command');

        } elseif (isset($update['callback_query'])) {
            $is_bot = $update['callback_query']['from']['is_bot'];

            $is_command = false;

        } elseif (isset($update['inline_query'])) {
            $is_bot = $update['inline_query']['from']['is_bot'];

            $is_command = false;

        }

        return $update;
    }

    /**
     * Возвращает данные обновления
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/getUpdate
     */
    public function getUpdate(): array
    {
        return $this->context->getUpdateData();
    }

    /**
     * Возвращает переменную callback_data
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getCallbackData(): ?string
    {
        return $this->context->getCallbackData();
    }

    /**
     * Возвращает переменную query_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getQueryId(): ?string
    {
        return $this->context->getQueryId();
    }

    /**
     * Возвращает переменную type
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getType(): ?string
    {
        return $this->context->getType();
    }

    /**
     * Возвращает переменную msg_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getMsgId(): ?string
    {
        return $this->context->getMessageId();
    }

    /**
     * Возвращает переменную text
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getText(): ?string
    {
        return $this->context->getText();
    }

    /**
     * Возвращает переменную user_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getUserId(): ?string
    {
        return $this->context->getUserId();
    }

    /**
     * Возвращает переменную chat_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getChatId(): ?string
    {
        return $this->context->getChatId();
    }

    /**
     * Извлекает данные пользователя из любого подходящего поля в текущем событии.
     *
     * Этот метод универсален и ищет данные пользователя ('from') в таких событиях,
     * как message, callback_query, inline_query, my_chat_member и других.
     *
     * @return UserDto Объект пользователя.
     * @throws LogicException Если данные пользователя не найдены в событии.
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getUserDto(): UserDto
    {
        $update = $this->context->getUpdateData();
        $user = null;

        $keys = [
            'message',
            'edited_message',
            'callback_query',
            'inline_query',
            'my_chat_member',
            'chat_member',
            'chat_join_request',
        ];

        foreach ($keys as $key) {
            if (isset($update[$key]['from'])) {
                $user = $update[$key]['from'];
                break;
            }
        }

        if ($user === null) {
            throw new LogicException("Не удалось найти данные пользователя ('from') в текущем событии.");
        }

        return UserDto::fromArray($user);
    }

    /**
     * Извлекает данные чата из любого подходящего поля в текущем событии.
     *
     * Этот метод универсален и ищет данные чата ('chat') в таких событиях,
     * как message, callback_query, channel_post, my_chat_member и других,
     * корректно обрабатывая вложенные структуры.
     *
     * @return ChatDto Объект чата.
     * @throws LogicException Если данные чата не найдены в событии.
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getChatDto(): ChatDto
    {
        $update = $this->context->getUpdateData();
        $chatData = null;

        // Определяем возможные пути к объекту 'chat'
        $paths = [
            ['message', 'chat'],
            ['edited_message', 'chat'],
            ['channel_post', 'chat'],
            ['edited_channel_post', 'chat'],
            ['my_chat_member', 'chat'],
            ['chat_member', 'chat'],
            ['chat_join_request', 'chat'],
            ['callback_query', 'message', 'chat'], // Вложенный путь
        ];

        foreach ($paths as $path) {
            $temp = $update;
            $found = true;

            // Проходим по каждому ключу в пути
            foreach ($path as $key) {
                if (!isset($temp[$key])) {
                    $found = false;
                    break; // Если ключ не найден, этот путь не подходит
                }
                $temp = $temp[$key]; // Спускаемся на уровень глубже
            }

            if ($found) {
                $chatData = $temp;
                break; // Нашли данные, выходим из основного цикла
            }
        }

        if ($chatData === null) {
            throw new LogicException("Не удалось найти данные чата ('chat') в текущем событии.");
        }

        return ChatDto::fromArray($chatData);
    }

    public function TGAPIErrorMSG($response, $params): string
    {
        $function_params['error_code'] = $response['error_code'];
        $function_params['description'] = $response['description'];
        $function_params['request_params'] = $params;
        return "Telegram API error:\n" . $this->formatArray($function_params);
    }

    private function formatArray(array $array, int $indent = 0): string
    {
        $space = str_repeat(" ", $indent * 2); // символ " " (U+2007, Figure Space)
        $result = "Array (\n";

        foreach ($array as $key => $value) {
            if (is_string($value) && ($decoded = json_decode($value, true)) !== null) {
                // Если значение — JSON, декодируем его в массив
                $value = $decoded;
            }

            if (is_array($value)) {
                $result .= $space . "  [$key] => " . $this->formatArray($value, $indent + 1);
            } else {
                $result .= $space . "  [$key] => " . ($value ?: 'null') . "\n";
            }
        }
        return $result . $space . ")\n";
    }

}