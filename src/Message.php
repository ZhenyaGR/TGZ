<?php

namespace ZhenyaGR\TGZ;

use CURLFile;

final class Message
{
    private ApiClient $api;
    private UpdateContext $context;
    private ?string $text;
    private array $reply_to = [];
    private array $kbd = [];
    private string $parse_mode;
    private array $params_additionally = [];
    private bool $sendPhoto = false;
    private bool $sendAnimation = false;
    private bool $sendDocument = false;
    private bool $sendSticker = false;
    private bool $sendVideo = false;
    private bool $sendAudio = false;
    private bool $sendVoice = false;
    private bool $sendDice = false;
    private bool $sendMediaGroup = false;
    private array $media = [];
    private string $sticker_id = '';
    private array $files = [];

    public function __construct(?string $text, string $defaultParseMode,
        ApiClient $api, UpdateContext $context,
    ) {
        $this->text = $text;
        $this->parse_mode = $defaultParseMode;
        $this->api = $api;
        $this->context = $context;
    }

    public function kbd(
        array $buttons = [],
        bool $inline = false,
        bool $one_time_keyboard = false,
        bool $resize_keyboard = false,
        bool $remove_keyboard = false,
    ): self {
        if ($remove_keyboard) {
            $keyboardConfig = ['remove_keyboard' => true];
            $this->kbd = [
                'reply_markup' => json_encode(
                    $keyboardConfig, JSON_THROW_ON_ERROR,
                ),
            ];

            return $this;
        }

        $kbd = $inline
            ? ['inline_keyboard' => $buttons]
            : [
                'keyboard'          => $buttons,
                'resize_keyboard'   => $resize_keyboard,
                'one_time_keyboard' => $one_time_keyboard,
            ];

        $this->kbd = [
            'reply_markup' => json_encode($kbd, JSON_THROW_ON_ERROR),
        ];

        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function parseMode(string $mode = ''): static
    {
        $mode = in_array($mode, ['HTML', 'Markdown', 'MarkdownV2', '']) ? $mode
            : '';

        $this->parse_mode = $mode;

        return $this;
    }

    public function params(array $params = []): static
    {
        $this->params_additionally = $params;

        return $this;
    }

    public function reply(?int $reply_to_message_id = null): static
    {
        if ($reply_to_message_id === null) {
            $msg_id = $this->context->getMessageId();
        } else {
            $msg_id = $reply_to_message_id;
        }
        $this->reply_to = ['reply_to_message_id' => $msg_id];

        return $this;
    }

    private function processMediaGroup(array $files, string $type): static
    {
        foreach ($files as $file) {
            if ($this->detectInputType($file)) {
                // Если требуется загрузка (локальный файл или URL)
                $fileIndex = count($this->media) + 1;
                $attachKey = 'attach://file'.$fileIndex;
                $this->media[] = [
                    'type'  => $type,
                    'media' => $attachKey,
                ];
                // Сохраняем объект CURLFile в отдельном массиве
                $this->files['file'.$fileIndex] = new CURLFile($file);
            } else {
                // Если передан file_id
                $this->media[] = [
                    'type'  => $type,
                    'media' => $file,
                ];
            }
        }

        return $this;
    }

    private function detectInputType($input): bool
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

    public function dice(string $dice): static
    {
        $this->sendDice = true;
        $this->text = $dice;

        return $this;
    }

    public function gif(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendAnimation = true;

        return $this;
    }

    public function voice(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'voice');
        $this->sendVoice = true;

        return $this;
    }

    public function audio(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'audio');
        $this->sendAudio = true;

        return $this;
    }

    public function video(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'video');
        $this->sendVideo = true;

        return $this;
    }

    public function doc(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendDocument = true;

        return $this;
    }

    public function img(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'photo');
        $this->sendPhoto = true;

        return $this;
    }

    public function mediaPreview(string $url): static
    {
//        $invisibleCharacter = '​'; // U+200B ZERO-WIDTH SPACE
//
//        $this->text = $invisibleCharacter . $this->text;
//
//        $lengthInUtf16 = strlen(mb_convert_encoding($invisibleCharacter, 'UTF-16LE', 'UTF-8')) / 2;
//
//        $entity = [
//            'type'   => 'text_link',
//            'offset' => 0,
//            'length' => $lengthInUtf16,
//            'url'    => $url,
//        ];
//
//        if ($this->entities === null) {
//            $this->entities = [];
//        }
//
//        array_unshift($this->entities, $entity);
//
//        // Больше не требуется, так как мы используем entities
//        // unset($this->parse_mode);
//
        return $this;
    }

    public function sticker(string $file_id): static
    {
        $this->sendSticker = true;
        $this->sticker_id = $file_id;

        return $this;
    }

    public function action(?string $action = 'typing'): static
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

    public function send(?int $chatID = null): array
    {
        $params = [
            'chat_id' => $chatID ?: $this->context->getChatId(),
        ];
        $params = array_merge($params, $this->params_additionally);
        $params = array_merge($params, $this->reply_to);
        $params = array_merge($params, $this->kbd);

        if (!$this->sendPhoto && !$this->sendAudio && !$this->sendSticker
            && !$this->sendDice
            && !$this->sendVoice
            && !$this->sendVideo
            && !$this->sendAnimation
            && !$this->sendDocument
            && !$this->sendMediaGroup
        ) {
            $params['text'] = $this->text;
            $params['parse_mode'] = $this->parse_mode;

            return $this->api->callAPI('sendMessage', $params);
        }

        if (count($this->media) > 1 && !$this->sendVoice) {
            return $this->sendMediaGroup($params);
        }

        return $this->sendMediaType($params);
    }

    public function sendEdit(?string $messageID = null, ?int $chatID = null, bool $caption = false
    ): array {
        if ($caption) {
            return $this->sendEditCaption($messageID, $chatID);
        }
        return $this->sendEditText($messageID, $chatID);
    }

    /**
     * Редактирует текст существующего сообщения
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     *
     * @return array
     */
    public function sendEditText(?string $messageID = null, ?int $chatID = null,
    ): array {
        $identifier = $this->_getIdentifier($messageID, $chatID);

        if (isset($this->text)) {
            $contentParams = [
                'text'       => $this->text,
                'parse_mode' => $this->parse_mode,
            ];
        } else {
            throw new \Exception(
                'Необходимо установить свойство text перед вызовом sendEditText',
            );
        }

        $params = $identifier + $contentParams;
        $params += $this->kbd;
        $params += $this->params_additionally;

        return $this->api->callAPI('editMessageText', $params);
    }

    /**
     * Редактирует описание существующего сообщения
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     *
     * @return array
     */
    public function sendEditCaption(?string $messageID = null,
        ?int $chatID = null,
    ): array {
        $identifier = $this->_getIdentifier($messageID, $chatID);

        if (isset($this->text)) {
            $contentParams = [
                'caption'    => $this->text,
                'parse_mode' => $this->parse_mode,
            ];
        } else {
            throw new \Exception(
                'Необходимо установить свойство text перед вызовом sendEditCaption',
            );
        }

        $params = $identifier + $contentParams;
        $params += $this->kbd;
        $params += $this->params_additionally;

        return $this->api->callAPI('editMessageCaption', $params);
    }

    /**
     * Вспомогательный приватный метод для получения идентификатора сообщения.
     * Избегает дублирования кода в публичных методах.
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     *
     * @return array
     */
    private function _getIdentifier(?string $messageID = null,
        ?int $chatID = null,
    ): array {
        $updateData = $this->context->getUpdateData();
        $inlineMessageId = $updateData['callback_query']['inline_message_id'] ??
            null;

        if ($inlineMessageId !== null) {
            // Для инлайн-сообщений используется только их собственный ID
            return ['inline_message_id' => $inlineMessageId];
        }

        // Для обычных сообщений в чате
        return [
            'chat_id'    => $chatID ?: $this->context->getChatId(),
            'message_id' => $messageID ?: $this->context->getMessageId(),
        ];
    }


    private function sendMediaGroup(array $params): array
    {
        $params1 = [
            'caption'    => $this->text,
            'parse_mode' => $this->parse_mode,
        ];

        $this->media[0] = array_merge($this->media[0], $params1);
        $mediaChunks = array_chunk($this->media, 10);

        foreach ($mediaChunks as $mediaChunk) {
            $postFields = array_merge($params, [
                'media' => json_encode($mediaChunk, JSON_THROW_ON_ERROR),
            ]);

            foreach ($mediaChunk as $item) {
                if (strpos($item['media'], 'attach://') === 0) {
                    $fileKey = str_replace('attach://', '', $item['media']);
                    $postFields[$fileKey] = $this->files[$fileKey];
                }
            }
            $this->api->callAPI('sendMediaGroup', $postFields);
        }

        return [];
    }

    private function sendSticker($params): array
    {
        $params['sticker'] = $this->sticker_id;

        return $this->api->callAPI('sendSticker', $params);
    }

    private function sendMediaType(array $params): array
    {
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

            return $this->api->callAPI('sendDice', $params);
        }

        if ($this->sendSticker) {
            return $this->sendSticker($params);
        }

        return [];
    }

    private function mediaSend(string $type, $params)
    {
        $params['caption'] = $this->text;
        $params['parse_mode'] = $this->parse_mode;
        $params[$type] = strpos(
            $this->media[0]['media'],
            'attach://',
        ) !== false ? $this->files['file1'] : $this->media[0]['media'];

        return $this->api->callAPI('send'.ucfirst($type), $params);
    }

}


