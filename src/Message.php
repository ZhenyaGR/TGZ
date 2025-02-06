<?php

namespace ZhenyaGR\TGZ;

class Message
{
    private $token;
    private $text;
    private $chatId_auto;
    private $update;
    private $kbd = [];
    private $parse_mode = '';
    private $params_additionally = [];
    private $sendPhoto = false;
    private $sendAudio = false;
    private $sendDocument = false;
    private $img_url = '';
    private $audio_url = '';
    private $doc_url = '';
    private $reply_to = false;


    public function __construct($text, $token, $chatId, $update)
    {
        $this->token = $token;
        $this->text = $text;
        $this->chatId_auto = $chatId;
        $this->update = $update;
    }

    public function kbd(
        array $buttons,
        array $params = ['inline' => false, "one_time_keyboard" => false, "resize_keyboard" => false],
        ?bool $inline = null,
        ?bool $one_time_keyboard = null,
        ?bool $resize_keyboard = null
    ) {

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

        /* $twoEnter = "\n\n";
         $msg = 'ВАРИАНТ С ИСПОЛЬЗОВАНИЕМ MarkdownV2' . $twoEnter . '*Жирный*' . $twoEnter . '_Курсив_' . $twoEnter . '__Подчёркнутый__' . $twoEnter . '`Моноширинный`' . $twoEnter . '[Ссылка](https://vk.com/ternabot)' . $twoEnter . ' ||Спойлер||' . $twoEnter;
         $tg->msg($msg)->parseMode("MarkdownV2")->send();

         $msg = 'ВАРИАНТ С ИСПОЛЬЗОВАНИЕМ HTML' . $twoEnter . '<b>Жирный</b>' . $twoEnter . '<i>Курсив</i>' . $twoEnter . '<u>Подчёркнутый</u>' . $twoEnter . '<code>Моноширинный</code>' . $twoEnter . '<a href="https://vk.com/ternabot">Ссылка</a>' . $twoEnter . ' <span class="tg-spoiler">Спойлер</span>' . $twoEnter;
         $tg->msg($msg)->parseMode("HTML")->send();*/

    }

    public function params(array $params = [])
    {
        $this->params_additionally = $params;
        return $this;
    }

    public function reply_to(?string $reply_to_message_id = null)
    {
        if ($reply_to_message_id == null || is_numeric($reply_to_message_id) == false) {
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

    public function audio(string $url)
    {
        $this->sendAudio = true;
        $this->audio_url = $url;
        return $this;
    }

    public function doc(string $url)
    {
        $this->sendDocument = true;
        $this->doc_url = $url;
        return $this;
    }

    public function urlImg(string $url)
    {
        $this->text = '<a href="' . htmlspecialchars($url) . '">​</a>' . $this->text; // Использует пробел нулевой ширины
        $this->parse_mode = "HTML";                                                          // с ссылкой в начале сообщения
        return $this;
    }

    public function send(?int $chatId = null)
    {
        $tg = new TGZ($this->token);

        if (!$this->sendPhoto && !$this->sendAudio && !$this->sendDocument) {

            $params = [
                'chat_id' => (isset($chatId) ? $chatId : $this->chatId_auto),
                'text' => $this->text,
                'parse_mode' => $this->parse_mode,
            ];
            $method = 'sendMessage';

        } else if ($this->sendPhoto) {

            $params = [
                'chat_id' => (isset($chatId) ? $chatId : $this->chatId_auto),
                'photo' => $this->img_url,
                'caption' => $this->text,
            ];
            $method = 'sendPhoto';

        } else if ($this->sendAudio) {

            $params = [
                'chat_id' => (isset($chatId) ? $chatId : $this->chatId_auto),
                'audio' => $this->audio_url,
                'caption' => $this->text,
            ];
            $method = 'sendAudio';

        } else if ($this->sendDocument) {

            $params = [
                'chat_id' => (isset($chatId) ? $chatId : $this->chatId_auto),
                'document' => $this->doc_url,
                'caption' => $this->text,
            ];
            $method = 'sendDocument';

        }
        $params = $this->kbd != [] ? array_merge($params, ['reply_markup' => $this->kbd]) : $params;
        $params = $this->reply_to != false ? array_merge($params, ['reply_to_message_id' => $this->reply_to]) : $params;
        $params = $this->params_additionally != [] ? array_merge($params, $this->params_additionally) : $params;

        $result = $tg->callAPI($method, $params);

        return $result;
    }

    public function sendEdit(?int $messageId = false, ?int $chatId = null)
    {
        $tg = new TGZ($this->token);

        $params = [
            'chat_id' => (isset($chatId) ? $chatId : $this->chatId_auto),
            'text' => $this->text,
            'parse_mode' => $this->parse_mode,
            'message_id' => ($messageId) ? $messageId : $this->update['callback_query']['message']['message_id']
        ];
        $params = $this->kbd != [] ? array_merge($params, ['reply_markup' => $this->kbd]) : $params;
        $params = $this->params_additionally != [] ? array_merge($params, $this->params_additionally) : $params;


        $method = 'editMessageText';

        $result = $tg->callAPI($method, $params);
        return $result;
    }

}

