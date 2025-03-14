<?php

namespace ZhenyaGR\TGZ;

class Message
{
    private $token;
    private $text;
    private $chatId_auto;
    private $update;
    private $reply_to = false;
    private $kbd = [];
    private $parse_mode = '';
    private $params_additionally = [];
    private $sendPhoto = false;
    private $sendPoll = false;
    private $img_url = '';
    private $question = '';
    private $options = [];
    private $is_anonymous = false;
    private $pollType = "regular";


    public function __construct($text, $token, $chatId, $update)
    {
        $this->token = $token;
        $this->text = $text;
        $this->chatId_auto = $chatId;
        $this->update = $update;
    }

    public function kbd(array $buttons, array $params = ['inline' => false, "one_time_keyboard" => false, "resize_keyboard" => false], ?bool $inline = null, ?bool $one_time_keyboard = null, ?bool $resize_keyboard = null)
    {

        $params = array_merge(['inline' => false, 'one_time_keyboard' => false, 'resize_keyboard' => false], $params);

        if ($inline !== null || $one_time_keyboard !== null || $resize_keyboard !== null) {
            $params['inline'] = $inline ?? $params['inline'];
            $params['one_time_keyboard'] = $one_time_keyboard ?? $params['one_time_keyboard'];
            $params['resize_keyboard'] = $resize_keyboard ?? $params['resize_keyboard'];
        }

        $this->kbd = $params['inline']
            ? ['inline_keyboard' => $buttons]
            : [
                'keyboard' => $buttons,
                'resize_keyboard' => $params["resize_keyboard"],
                'one_time_keyboard' => $params["one_time_keyboard"]
            ];

        return $this;
    }

    public function parseMode(string $mode = '')
    {
        if ($mode != 'HTML' && $mode != 'Markdown' && $mode != 'MarkdownV2' && $mode != '') {
            $mode = '';
        }
        $this->parse_mode = $mode;
        return $this;
    }

    public function params(array $params = [])
    {
        $this->params_additionally = $params;
        return $this;
    }

    public function reply(?int $reply_to_message_id = 0)
    {
        if ($reply_to_message_id === 0) {
            $msg_id = $this->update['message']['message_id'] ?? $this->update['callback_query']['message']['message_id'];
        } else {
            $msg_id = $reply_to_message_id;
        }
        $this->reply_to = $msg_id;
        return $this;
    }

    public function img(string $url)
    {
        $this->sendPhoto = true;
        $this->img_url = $url;
        return $this;
    }

    public function urlImg(string $url)
    {
        $this->text = '<a href="' . htmlspecialchars($url) . '">​</a>' . $this->text; // Использует пробел нулевой ширины
        $this->parse_mode = "HTML";                                                          // с ссылкой в начале сообщения
        return $this;
    }

    public function poll(string $text)
    {
        $this->sendPoll = true;
        $this->question = $text;
        return $this;
    }

    public function addAnswer(string $text)
    {
        $this->options[] = $text;
        return $this;
    }

    public function isAnonymous(?bool $anon = true)
    {
        $this->is_anonymous = $anon;
        return $this;
    }

    public function pollType(string $type) {
        $this->pollType = $type;
        return $this;
    }

    public function send(?int $chatId = null)
    {
        $tg = new TGZ($this->token);

        $params = [];
        $params = $this->kbd != [] ? array_merge($params, ['reply_markup' => $this->kbd]) : $params;
        $params = $this->reply_to !== false ? array_merge($params, ['reply_to_message_id' => $this->reply_to]) : $params;
        $params = $this->params_additionally != [] ? array_merge($params, $this->params_additionally) : $params;

        if (!$this->sendPhoto && !$this->sendPoll) {
            $params['chat_id'] = !empty($chatId) ? $chatId : $this->chatId_auto;
            $params['text'] = $this->text;
            $params['parse_mode'] = $this->parse_mode;

            $method = 'sendMessage';
            return $tg->callAPI($method, $params);
        }

        if ($this->sendPhoto) {
            $params['chat_id'] = !empty($chatId) ? $chatId : $this->chatId_auto;
            $params['caption'] = $this->text;
            $params['parse_mode'] = $this->parse_mode;
            $params['photo'] = $this->img_url;
            
            $method = 'sendPhoto';
            return $tg->callAPI($method, $params);
        }

        if ($this->sendPoll) {
            $params['chat_id'] = !empty($chatId) ? $chatId : $this->chatId_auto;
            $params['question'] = $this->question;
            $params['options'] = $this->options;
            $params['is_anonymous'] = $this->is_anonymous;
            $params['type'] = $this->pollType;

            $method = 'sendPoll';
            return $tg->callAPI($method, $params);

        }   
        

    }

    public function sendEdit(?int $messageId = 0, ?int $chatId = 0)
    {
        $tg = new TGZ($this->token);

        $params = [
            'chat_id' => ($chatId !== 0) ? $chatId : $this->chatId_auto,
            'text' => $this->text,
            'parse_mode' => $this->parse_mode,
            'message_id' => ($messageId !== 0) ? $messageId : $this->update['callback_query']['message']['message_id']
        ];
        $params = $this->kbd != [] ? array_merge($params, ['reply_markup' => $this->kbd]) : $params;
        $params = $this->params_additionally != [] ? array_merge($params, $this->params_additionally) : $params;


        $method = 'editMessageText';

        $result = $tg->callAPI($method, $params);
        return $result;
    }

}

