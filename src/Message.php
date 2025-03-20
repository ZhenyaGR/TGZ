<?php

namespace ZhenyaGR\TGZ;

use CURLFile;

class Message
{
    private $token;
    private $text;
    private $chatId_auto;
    private $update;
    private $reply_to = false;
    private $kbd = [];
    private $parse_mode;
    private $params_additionally = [];
    private $sendPhoto = false;
    private $sendAnimation = false;
    private $sendPoll = false;
    private $sendDocument = false;
    private $sendVideo = false;
    private $sendMediaGroup = false;
    private $question = '';
    private $processMediaGroup = [];
    private $media = [];
    private $files = [];
    private $options = [];
    private $is_anonymous = false;
    private $pollType = "regular";


    public function __construct($text, $token, $chatId, $update, $parse_mode)
    {
        $this->token = $token;
        $this->text = $text;
        $this->chatId_auto = $chatId;
        $this->update = $update;
        $this->parse_mode = $parse_mode;
    }

    public function kbd(
        array $buttons = [],
        array $params = ['inline' => false, "one_time_keyboard" => false, "resize_keyboard" => false],
        ?bool $inline = null,
        ?bool $one_time_keyboard = null,
        ?bool $resize_keyboard = null,
        ?bool $remove_keyboard = null
    )
    {

        if ($remove_keyboard === true) {
            $this->kbd = ['remove_keyboard' => true];
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

        $this->kbd = $kbd;
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

    public function gif(string|array $url): self
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendAnimation = true;
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

    private function mediaSend($tg, $type, $params)
    {
        $params['caption'] = $this->text;
        $params['parse_mode'] = $this->parse_mode;
        $params[$type] = strpos($this->media[0]['media'], 'attach://') !== false ? $this->files['file1'] : $this->media[0]['media'];
        $method = 'send' . ucfirst($type);
        return $tg->callAPI($method, $params);
    }

    public function send(?int $chatId = null)
    {
        $tg = new TGZ($this->token);

        $params = [];
        $params = $this->kbd != [] ? array_merge($params, ['reply_markup' => json_encode($this->kbd, JSON_THROW_ON_ERROR)]) : $params;
        $params = $this->reply_to !== false ? array_merge($params, ['reply_to_message_id' => $this->reply_to]) : $params;
        $params = $this->params_additionally != [] ? array_merge($params, $this->params_additionally) : $params;
        $params['chat_id'] = !empty($chatId) ? $chatId : $this->chatId_auto;

        if (!$this->sendPhoto && !$this->sendPoll && !$this->sendVideo && !$this->sendAnimation && !$this->sendDocument && !$this->sendMediaGroup) {
            $params['text'] = $this->text;
            $params['parse_mode'] = $this->parse_mode;

            $method = 'sendMessage';
            return $tg->callAPI($method, $params);
        }

        if (count($this->media) > 1) {
            $this->sendMediaGroup = true;
            $this->sendPhoto = false;
            $this->sendDocument = false;
            $this->sendAnimation = false;
            $this->sendVideo = false;
            $this->sendPoll = false;
        }

        if ($this->sendPhoto) {
            $this->mediaSend($tg, 'photo', $params);
        }

        if ($this->sendDocument) {
            $this->mediaSend($tg, 'document', $params);
        }

        if ($this->sendVideo) {
            $this->mediaSend($tg, 'video', $params);
        }

        if ($this->sendAnimation) {
            $this->mediaSend($tg, 'animation', $params);
        }

        if ($this->sendPoll) {
            $params['question'] = $this->question;
            $params['options'] = json_encode($this->options, JSON_THROW_ON_ERROR);
            $params['is_anonymous'] = $this->is_anonymous;
            $params['type'] = $this->pollType;

            $method = 'sendPoll';
            return $tg->callAPI($method, $params);
        }

        if ($this->sendMediaGroup) {

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
                return $tg->sendMediaGroup($postFields);

            }
        }
    }

    public
    function sendEdit(?int $messageId = 0, ?int $chatId = 0)
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

