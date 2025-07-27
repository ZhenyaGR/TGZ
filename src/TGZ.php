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
    private string $parseModeDefault = '';

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


    public function callAPI(string $method, ?array $params = []): array
    {
        return $this->api->callAPI($method, $params);
    }

    public function __call(string $method, array $args): array
    {
        $params = $args[0] ?? [];

        return $this->api->callAPI($method, $params);
    }

    public function getWebhookUpdate(): array
    {
        return $this->context->getUpdateData();
    }

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

    public function initCallbackData(&$callback_data)
    {
        $callback_data = $this->context->getCallbackData();

        return $this;
    }

    public function initQuery(&$query_id)
    {
        $query_id = $this->context->getQueryId();

        return $this;
    }

    public function initType(&$type): static
    {
        $type = $this->context->getType();

        return $this;
    }

    public function initMsgID(&$msg_id): static
    {
        $msg_id = $this->context->getMessageId();

        return $this;
    }

    public function initText(&$text): static
    {
        $text = $this->context->getText();

        return $this;
    }

    public function initUserID(&$user_id): static
    {
        $user_id = $this->context->getUserId();

        return $this;
    }

    public function initChatID(&$chat_id): static
    {
        $chat_id = $this->context->getChatId();

        return $this;
    }

    public function defaultParseMode(string $mode = ''): static
    {
        if ($mode !== 'HTML' && $mode !== 'Markdown' && $mode !== 'MarkdownV2'
            && $mode !== ''
        ) {
            $mode = '';
        }
        $this->parseModeDefault = $mode;

        return $this;
    }

    public function sendOK(): void
    {
        http_response_code(200);
        echo 'ok';
    }

    public function msg(string $text = ''): Message
    {
        return new Message($text, $this->parseModeDefault, $this->api, $this->context);
    }

    public function poll(string $type = 'regular'): Poll
    {
        return new Poll($type, $this->api, $this->context);
    }

    public function inline(string $type = ''): Inline
    {
        return new Inline($type, $this->parseModeDefault);
    }

    public function delMsg(array|int $msg_ids = null, int|string $chat_id = null): array
    {
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

    public function copyMsg(int $msg_ids = null, int|string $chat_id = null, int|string $from_chat_id = null): array
    {
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
            $method, ['chat_id' => $chat_id, 'from_chat_id' => $from_chat_id, $param => $msg_ids],
        );
    }

    public function getFileID(string $url, string $type = 'document', int|string $chat_id = null): string {
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

    public function sendMessage(int $chatId, string $text, array $params = []): array
    {
        $params_message = [
            'chat_id' => $chatId,
            'text'    => $text,
        ];

        return $this->api->callAPI('sendMessage', array_merge($params_message, $params));
    }

    public function reply($message, array $params = []): array
    {
        if (!isset($params['chat_id'])) {
            $this->initChatID($params['chat_id']);
        }
        return $this->api->callAPI('sendMessage', array_merge($params, ['text' => $message]));
    }

    /**
     * @return string[]
     *
     * @psalm-return array{text: string, callback_data: string}
     */
    public function buttonCallback(string $buttonText, string $buttonData,
    ): array {
        return [
            'text'          => $buttonText,
            "callback_data" => $buttonData,
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return array{text: string, url: string}
     */
    public function buttonUrl(string $buttonText, string $buttonUrl): array
    {
        return [
            'text' => $buttonText,
            "url"  => $buttonUrl,
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return array{text: string}
     */
    public function buttonText(string $buttonText): array
    {
        return [
            'text' => $buttonText,
        ];
    }

    public function answerCallbackQuery(string $callbackID, array $options = [],
    ): array {
        $params = array_merge([
            'callback_query_id' => $callbackID,
        ], $options);

        return $this->callAPI('answerCallbackQuery', $params);
    }

    public function answerInlineQuery(string $inlineQueryID, array $results,
        array $extra = [],
    ): array {
        $params = array_merge([
            'inline_query_id' => $inlineQueryID,
            'results'         => json_encode($results),
        ], $extra);

        return $this->callAPI('answerInlineQuery', $params);
    }
}