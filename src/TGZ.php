<?php

namespace ZhenyaGR\TGZ;

use CURLFile;
use Exception;
use ZhenyaGR\TGZ\Contracts\ApiInterface;

class TGZ
{
    use ErrorHandler;

    public ApiInterface $api;
    public UpdateContext $context;
    public ?string $parseModeDefault = null;

    public function __construct(ApiInterface $api, UpdateContext $context)
    {
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
     * Выполняет вызов к Telegram Bot API.
     *
     * @param string $method
     * @param array  $args
     *
     * @return array
     *
     * @throws \Exception
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/callAPI
     */
    public function __call(string $method, array $args): array
    {
        $params = $args[0] ?? [];

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
            $text, $this
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
        if ($chat_id === null) {
            $this->initChatID($chat_id);
        }

        if ($msg_ids === null) {
            $this->initMsgID($msg_ids);
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
            $this->initMsgID($msg_ids);
        }

        if ($chat_id === null) {
            $this->initChatID($chat_id);
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
     * @return string
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/getFileID
     */
    public function getFileID(string $url, string $type = 'document',
        int|string $chat_id = null,
    ): string {
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

        if ($type === 'photo') {
            // Берем последний элемент массива (наибольший по размеру вариант)
            return end($result['result']['photo'])['file_id'];
        }

        if ($type === 'audio') {
            return $result['result']['audio']['file_id'];
        }

        if ($type === 'video') {
            return $result['result']['video']['file_id'];
        }

        return $result['result']['document']['file_id'];
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
            $this->initChatID($params['chat_id']);
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
     * Возвращает данные обновления
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/getWebhookUpdate
     */
    public function getWebhookUpdate(): array
    {
        return $this->context->getUpdateData();
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
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
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

        $this
            ->initUserID($user_id)
            ->initChatID($chat_id)
            ->initText($text)
            ->initMsgID($msg_id)
            ->initType($type)
            ->initQuery($query_id)
            ->initCallbackData($callback_data);

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
     * Инициализирует переменную callback_data
     *
     * @param $callback_data
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initCallbackData(&$callback_data): self
    {
        $callback_data = $this->context->getCallbackData();

        return $this;
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
     * Инициализирует переменную query_id
     *
     * @param $query_id
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initQuery(&$query_id): self
    {
        $query_id = $this->context->getQueryId();

        return $this;
    }

    /**
     * Возвращает переменную query_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getQueryID(): ?string
    {
        return $this->context->getQueryId();
    }

    /**
     * Инициализирует переменную type
     *
     * @param $type
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initType(&$type): self
    {
        $type = $this->context->getType();

        return $this;
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
     * Инициализирует переменную msg_id
     *
     * @param $msg_id
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initMsgID(&$msg_id): self
    {
        $msg_id = $this->context->getMessageId();

        return $this;
    }

    /**
     * Возвращает переменную msg_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getMsgID(): ?string
    {
        return $this->context->getMessageId();
    }

    /**
     * Инициализирует переменную text
     *
     * @param $text
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initText(&$text): self
    {
        $text = $this->context->getText();

        return $this;
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
     * Инициализирует переменную user_id
     *
     * @param $user_id
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initUserID(&$user_id): self
    {
        $user_id = $this->context->getUserId();

        return $this;
    }

    /**
     * Возвращает переменную user_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getUserID(): ?string
    {
        return $this->context->getUserId();
    }

    /**
     * Инициализирует переменную chat_id
     *
     * @param $chat_id
     *
     * @return TGZ
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/init
     */
    public function initChatID(&$chat_id): self
    {
        $chat_id = $this->context->getChatId();

        return $this;
    }

    /**
     * Возвращает переменную chat_id
     *
     * @return ?string
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/tgzMethods/get
     */
    public function getChatID(): ?string
    {
        return $this->context->getChatId();
    }
}