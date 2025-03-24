<?php

namespace ZhenyaGR\TGZ;

use CURLFile;

class Message
{
    private $text;
    private $TGZ;
    private $chatID;
    private $reply_to = [];
    private $kbd = [];
    private $parse_mode;
    private $params_additionally = [];
    private $sendPhoto = false;
    private $sendAnimation = false;
    private $sendPoll = false;
    private $sendDocument = false;
    private $sendVideo = false;
    private $sendAudio = false;
    private $sendVoice = false;
    private $sendDice = false;
    private $sendMediaGroup = false;
    private $question = '';
    private $media = [];
    private $files = [];
    private $options = [];
    private $is_anonymous = false;
    private $pollType = "regular";


    public function __construct($text, $TGZ)
    {
        $this->text = $text;
        $this->chatID = $TGZ->chatId;
        $this->parse_mode = $TGZ->parseModeDefault;
        $this->TGZ = $TGZ;
    }

    public function kbd(array $buttons = [], array $params = ['inline' => false, "one_time_keyboard" => false, "resize_keyboard" => false], ?bool $inline = null, ?bool $one_time_keyboard = null, ?bool $resize_keyboard = null, ?bool $remove_keyboard = null)
    {

        if ($remove_keyboard) {
            $keyboardConfig = ['remove_keyboard' => true];
            $this->kbd = [
                'reply_markup' => json_encode($keyboardConfig, JSON_THROW_ON_ERROR)
            ];
            return $this;
        }

        $params = array_merge(['inline' => false, 'one_time_keyboard' => false, 'resize_keyboard' => false], $params);

        if ($inline !== null || $one_time_keyboard !== null || $resize_keyboard !== null) {
            $params['inline'] = $inline ?? $params['inline'];
            $params['one_time_keyboard'] = $one_time_keyboard ?? $params['one_time_keyboard'];
            $params['resize_keyboard'] = $resize_keyboard ?? $params['resize_keyboard'];
        }

        $kbd = $params['inline']
            ? ['inline_keyboard' => $buttons]
            : [
                'keyboard' => $buttons,
                'resize_keyboard' => $params["resize_keyboard"],
                'one_time_keyboard' => $params["one_time_keyboard"]
            ];

        $this->kbd = [
            'reply_markup' => json_encode($kbd, JSON_THROW_ON_ERROR)
        ];
        return $this;
    }

    public function parseMode(string $mode = '')
    {
        if ($mode !== 'HTML' && $mode !== 'Markdown' && $mode !== 'MarkdownV2' && $mode !== '') {
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

    public function reply(?int $reply_to_message_id = null)
    {
        if ($reply_to_message_id === null) {
            $msg_id = $this->TGZ->update['message']['message_id'] ?? $this->TGZ->update['callback_query']['message']['message_id'];
        } else {
            $msg_id = $reply_to_message_id;
        }
        $this->reply_to = ['reply_to_message_id' => $msg_id];
        return $this;
    }

    private function processMediaGroup(array $files, string $type)
    {

        foreach ($files as $file) {
            if ($this->detectInputType($file)) {
                // Если требуется загрузка (локальный файл или URL)
                $fileIndex = count($this->media) + 1;
                $attachKey = 'attach://file' . $fileIndex;
                $this->media[] = [
                    'type' => $type,
                    'media' => $attachKey
                ];
                // Сохраняем объект CURLFile в отдельном массиве
                $this->files['file' . $fileIndex] = new CURLFile($file);
            } else {
                // Если передан file_id
                $this->media[] = [
                    'type' => $type,
                    'media' => $file
                ];
            }
        }
        return $this;
    }

    private function detectInputType($input)
    {
        // Проверка на URL
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            return true;
        }
        // Проверка на локальный файл
        if (file_exists($input) && is_file($input)) {
            return true;
        }
        // Иначе file_id
        return false;

    }

    public function dice(string $dice)
    {
        $this->sendDice = true;
        $this->text = $dice;
        return $this;
    }

    public function gif(string|array $url)
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendAnimation = true;
        return $this;
    }

    public function voice(string $url)
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'voice');
        $this->sendVoice = true;
        return $this;
    }

    public function audio(string|array $url)
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'audio');
        $this->sendAudio = true;
        return $this;
    }

    public function video(string|array $url): self
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'video');
        $this->sendVideo = true;
        return $this;
    }

    public function doc(string|array $url)
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendDocument = true;
        return $this;
    }

    public function img(string|array $url)
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'photo');
        $this->sendPhoto = true;
        return $this;
    }

    public function urlImg(string $url)
    {
        $this->text = '<a href="' . htmlspecialchars($url) . '">​</a>' . $this->text; // Использует пробел нулевой ширины
        $this->parse_mode = "HTML";                                                      // со ссылкой в начале сообщения
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

    public function pollType(string $type)
    {
        $this->pollType = $type;
        return $this;
    }

    public function send(?int $chatID = null): array
    {

        $params = [
            'chat_id' => $chatID ?: $this->chatID
        ];
        $params = array_merge($params, $this->params_additionally);
        $params = array_merge($params, $this->reply_to);
        $params = array_merge($params, $this->kbd);

        if (!$this->sendPhoto && !$this->sendAudio && !$this->sendDice && !$this->sendVoice  && !$this->sendPoll && !$this->sendVideo && !$this->sendAnimation && !$this->sendDocument && !$this->sendMediaGroup) {
            $params['text'] = $this->text;
            $params['parse_mode'] = $this->parse_mode;
            return $this->TGZ->callAPI('sendMessage', $params);
        }

        if (count($this->media) > 1 && !$this->sendVoice ) {
            return $this->sendMediaGroup($params);
        }

        return $this->sendMediaType($params);
    }

    public function sendEdit(?int $messageID = 0, ?int $chatID = null) : array
    {

        $params = [
            'text' => $this->text,
            'parse_mode' => $this->parse_mode,
            'chat_id' => $chatID ?: $this->chatID,
            'message_id' => $messageID ?: $this->TGZ->update['callback_query']['message']['message_id']
        ];
        $params = array_merge($params, $this->kbd);
        $params = array_merge($params, $this->params_additionally);

        $method = 'editMessageText';
        return $this->TGZ->callAPI($method, $params);
    }


    private function sendMediaGroup($params): array
    {
        $params1 = [
            'caption' => $this->text,
            'parse_mode' => $this->parse_mode
        ];

        $this->media[0] = array_merge($this->media[0], $params1);
        $mediaChunks = array_chunk($this->media, 10);

        foreach ($mediaChunks as $mediaChunk) {

            $postFields = array_merge($params, [
                'media' => json_encode($mediaChunk, JSON_THROW_ON_ERROR)
            ]);

            foreach ($mediaChunk as $item) {
                if (strpos($item['media'], 'attach://') === 0) {
                    $fileKey = str_replace('attach://', '', $item['media']);
                    $postFields[$fileKey] = $this->files[$fileKey];
                }
            }
            $this->TGZ->callAPI('sendMediaGroup', $postFields);
        }
        return [];
    }

    private function sendPoll($params): array {
        $params['question'] = $this->question;
        $params['options'] = json_encode($this->options, JSON_THROW_ON_ERROR);
        $params['is_anonymous'] = $this->is_anonymous;
        $params['type'] = $this->pollType;

        return $this->TGZ->callAPI('sendPoll', $params);
    }

    private function sendMediaType($params): array {
        if ($this->sendPhoto) {
            return $this->mediaSend('photo', $params);
        }

        if ($this->sendDocument) {
            return $this->mediaSend('document', $params);
        }

        if ($this->sendVideo) {
            return $this->mediaSend('video', $params);
        }

        if ($this->sendAnimation) {
            return $this->mediaSend('animation', $params);
        }

        if ($this->sendAudio) {
            return $this->mediaSend('audio', $params);
        }

        if ($this->sendVoice) {
            return $this->mediaSend('voice', $params);
        }

        if ($this->sendDice) {
            $params['emoji'] = $this->text;
            return $this->TGZ->callAPI('sendDice', $params);
        }

        if ($this->sendPoll) {
            return $this->sendPoll($params);
        }

        return [];
    }

    private function mediaSend($type, $params)
    {
        $params['caption'] = $this->text;
        $params['parse_mode'] = $this->parse_mode;
        $params[$type] = strpos($this->media[0]['media'], 'attach://') !== false ? $this->files['file1'] : $this->media[0]['media'];
        return $this->TGZ->callAPI('send' . ucfirst($type), $params);
    }

}


