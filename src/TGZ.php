<?php

namespace ZhenyaGR\TGZ;

use CURLFile;
use Exception;

class TGZ
{
    use ErrorHandler;

    private string $token;
    public string $apiUrl;
    public array $update;
    public string $parseModeDefault = '';


    public static function create(string $token)
    {
        return new self($token);
    }

    public function __construct(string $token)
    {
        $this->sendOK();

        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";

        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        $this->update = $update;

    }

    public function callAPI(string $method, ?array $params = []): array
    {
        $url = $this->apiUrl . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        }

        throw new Exception($this->TGAPIErrorMSG($response, $params));
        return $response;
    }

    public function getWebhookUpdate()
    {
        return $this->update;
    }

    public function __call(string $method, array $args = [])
    {
        $args = (empty($args)) ? $args : $args[0];
        return $this->callAPI($method, $args);
    }

    public function initVars(&$chat_id = null, &$user_id = null, &$text = null, &$type = null, &$callback_data = null, &$callback_id = null, &$msg_id = null, &$is_bot = null, &$is_command = null)
    {
        $update = $this->update;

        $test = $this->initUserID($user_id)
            ->initChatID($chat_id)
            ->initText($text)
            ->initMsgID($msg_id)
            ->initType($type);

        if (isset($update['message'])) {
            $is_bot = $update['message']['from']['is_bot'];
            $is_command = (isset($update['message']['entities'][0]['type']) && $update['message']['entities'][0]['type'] === 'bot_command') ? true : false;
            $callback_data = false;
            $callback_id = false;

        } else if (isset($update['callback_query'])) {
            $is_bot = $update['callback_query']['from']['is_bot'];
            $is_command = false;
            $callback_data = $update['callback_query']['data'];
            $callback_id = $update['callback_query']['id'];
        }
    }

    public function initType(&$type)
    {
        if (isset($this->update['message'])) {
            $type = (isset($this->update['message']['entities'][0]['type']) && $this->update['message']['entities'][0]['type'] === 'bot_command') ? 'bot_command' : 'text';
        } else if (isset($this->update['callback_query'])) {
            $type = 'callback_query';
        }

        return $this;
    }

    public function initMsgID(&$msg_id)
    {
        $msg_id = $this->update['message']['message_id'] ??             // обычное сообщение
            $this->update['callback_query']['message']['message_id'];   // нажатие inline-кнопки

        return $this;
    }

    public function initText(&$text)
    {
        $text = $this->update['message']['text'] ??                  // обычное сообщение
            $this->update['message']['caption'] ??                   // описание медиа
            $this->update['callback_query']['message']['text'] ??    // нажатие inline-кнопки
            $this->update['callback_query']['message']['caption'] ?? // описание медиа
            '';

        return $this;
    }

    public function initUserID(&$user_id)
    {
        $user_id = $this->update['message']['from']['id'] ?? // обычное сообщение
            $this->update['callback_query']['from']['id'] ?? // нажатие inline-кнопки
            null;

        return $this;
    }

    public function initChatID(&$chat_id)
    {
        $chat_id = $this->update['message']['chat']['id'] ?? // обычное сообщение
            $this->update['callback_query']['message']['chat']['id'] ?? // нажатие inline-кнопки
            null;

        return $this;
    }

    public function defaultParseMode(string $mode = '')
    {
        if ($mode !== 'HTML' && $mode !== 'Markdown' && $mode !== 'MarkdownV2' && $mode !== '') {
            $mode = '';
        }
        $this->parseModeDefault = $mode;
    }

    public function sendOK()
    {
        http_response_code(200);
        echo 'ok';
    }

    public function msg(string $text = '')
    {
        return new Message($text, $this);
    }

    public function delMsg(array|int $msg_ids, int $chat_id = null): array
    {
        if ($chat_id === null) {
            $this->initChatID($chat_id);
        }
        $method = is_array($msg_ids) ? 'deleteMessages' : 'deleteMessage';

        return $this->callAPI($method, ['chat_id' => $chat_id, 'messages_id' => $msg_ids]);
    }

    public function getFileID(string $url, int $chat_id, string $type = 'document')
    {
        if (!in_array($type, ['document', 'audio', 'photo', 'animation', 'video', 'video_note', 'voice', 'sticker'])) {
            $type = 'document';
        }
        $params[$type] = new CURLFile($url);
        $params['chat_id'] = $chat_id;

        $method = 'send' . ucfirst($type);
        $result = $this->callAPI($method, $params);

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

    public function sendMessage(int $chatId, string $text)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        return $this->callAPI('sendMessage', $params);
    }

    public function buttonCallback(string $buttonText, string $buttonData)
    {
        return [
            'text' => $buttonText,
            "callback_data" => $buttonData
        ];
    }

    public function buttonUrl(string $buttonText, string $buttonUrl)
    {
        return [
            'text' => $buttonText,
            "url" => $buttonUrl
        ];
    }

    public function buttonText(string $buttonText)
    {
        return [
            'text' => $buttonText
        ];
    }

    public function answerCallbackQuery(int $callbackId, array $options = [])
    {
        $params = array_merge([
            'callback_query_id' => $callbackId,
        ], $options);
        return $this->callAPI('answerCallbackQuery', $params);
    }
}