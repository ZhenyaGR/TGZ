<?php

namespace ZhenyaGR\TGZ;

class TGZ
{
    private $token;
    private $apiUrl;
    private $botId;
    private $chatId;
    private $update;
    private $debug_mode = false;

    public function __construct($token)
    {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";

        // Получение информации о боте
        $botInfo = $this->callAPI('getMe');
        if ($botInfo && isset($botInfo['result']['id'])) {
            $this->botId = $botInfo['result']['id'];
        } else {
            throw new Exception("Не удалось получить информацию о боте.");
        }
    }

    public function callAPI($method, $params = [])
    {
        $url = $this->apiUrl . $method;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        error_log("Telegram API call failed: $response");
        return null;
    }

    public function getWebhookUpdate()
    {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        $this->update = $update;
        $this->chatId = isset($update['message']) ? $update['message']['chat']['id'] : (isset($update['callback_query']) ? $update['callback_query']['message']['chat']['id'] : null);

        if ($this->debug_mode == true) {
            $this->sendMessage($this->chatId, $input);
        }
        return $update;
    }

    public function __call($method, $args = [])
    {
        $args = (empty($args)) ? $args : $args[0];
        return $this->callAPI($method, $args);
    }

    public function initVars(&$chat_id = null, &$user_id = null, &$text = null, &$type = null, &$callback_data = null, &$callback_id = null, &$msg_id = null, &$is_bot = null, &$is_command = null)
    {
        $update = $this->update;

        if (isset($update['message'])) {

            $chat_id = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? null;
            if ($text == null) {
                $text = $update['message']['caption'] ?? null;
            }
            $user_id = $update['message']['from']['id'];
            $msg_id = $update['message']['message_id'];
            $is_bot = $update['message']['from']['is_bot'];
            $is_command = (isset($update['message']['entities'][0]['type']) && $update['message']['entities'][0]['type'] == 'bot_command') ? true : false;
            $callback_data = false;
            $callback_id = false;

            if ($is_command) {
                $type = 'bot_command';
            } else {
                $type = 'text';
            }

        } else if (isset($update['callback_query'])) {

            $chat_id = $update['callback_query']['message']['chat']['id'];
            $text = $update['callback_query']['message']['text'] ?? null;
            if ($text == null) {
                $text = $update['callback_query']['message']['caption'] ?? null;
            }
            $user_id = $update['callback_query']['from']['id'];
            $msg_id = $update['callback_query']['message']['message_id'];
            $is_bot = $update['callback_query']['from']['is_bot'];
            $is_command = false;

            $type = 'callback_query';
            $callback_data = $update['callback_query']['data'];
            $callback_id = $update['callback_query']['id'];
        }
    }

    public function debug_mode($flag = true)
    {
        $this->debug_mode = $flag;
    }

    public function end_script()
    {
        http_response_code(200);
        echo 'ok';
    }

    public function msg($text = '')
    {
        return new Message($text, $this->token, $this->chatId, $this->update);
    }


    public function sendMessage($chatId, $text)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        return $this->callAPI('sendMessage', $params);
    }

    public function buttonCallback($buttonText, $buttonData)
    {
        return [
            'text' => $buttonText,
            "callback_data" => $buttonData
        ];
    }

    public function buttonUrl($buttonText, $buttonUrl)
    {
        return [
            'text' => $buttonText,
            "url" => $buttonUrl
        ];
    }


    public function answerCallbackQuery($callbackId, $options = [])
    {
        $params = array_merge([
            'callback_query_id' => $callbackId,
        ], $options);
        return $this->callAPI('answerCallbackQuery', $params);
    }
}