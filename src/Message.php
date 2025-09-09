<?php

namespace ZhenyaGR\TGZ;

use CURLFile;

final class Message
{
    private ApiClient $api;
    private UpdateContext $context;
    private TGZ $TGZ;
    private ?string $text;
    private array $reply_to = [];
    private array $kbd = [];
    private ?string $parse_mode;
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
    private ?array $entities = null;

    /**
     * @var string|null URL для медиа-превью.
     */
    private ?string $media_preview_url = null;

    public function __construct(?string $text, TGZ $TGZ,
    ) {
        $this->text = $text;
        $this->parse_mode = $TGZ->parseModeDefault;
        $this->api = $TGZ->api;
        $this->context = $TGZ->context;
        $this->TGZ = $TGZ;
    }

    /**
     * Добавляет клавиатуру или inline-клавиатуру и удаляет клавиатуру
     *
     * @param array $buttons
     * @param bool  $inline
     * @param bool  $one_time_keyboard
     * @param bool  $resize_keyboard
     * @param bool  $remove_keyboard
     *
     * @return Message
     *
     * @throws \JsonException
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/kbd
     */
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

    /**
     * Задает текст сообщения
     *
     * @param string $text
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/text
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Задает режим парсинга
     *
     * @param ?string $mode 'HTML', 'Markdown', 'MarkdownV2'
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/parseMode
     */
    public function parseMode(?string $mode = null): Message
    {
        $mode = in_array($mode, ['HTML', 'Markdown', 'MarkdownV2', '']) ? $mode
            : null;

        $this->parse_mode = $mode;

        return $this;
    }

    /**
     * Добавляет дополнительные параметры
     *
     * @param array $params
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/params
     */
    public function params(array $params = []): static
    {
        $this->params_additionally = $params;

        return $this;
    }

    /**
     * Отвечает на сообщение
     *
     * @param int|null $reply_to_message_id
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/reply
     */
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

    /**
     * Отправляет анимированные эмодзи
     *
     * @param string $dice '🎲', '🎯', '🏀', '⚽', '🎳', '🎰'
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/dice
     */
    public function dice(string $dice): static
    {
        $this->sendDice = true;
        $this->text = $dice;

        return $this;
    }

    /**
     * Добавляет gif-файл к сообщению
     *
     * @param string|array $url Ссылка или массив ссылок (ID)
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/gif
     */
    public function gif(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendAnimation = true;

        return $this;
    }

    /**
     * Отправляет голосовое сообщение
     *
     * @param string|array $url Ссылка или массив ссылок (ID)
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/voice
     */
    public function voice(string $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'voice');
        $this->sendVoice = true;

        return $this;
    }

    /**
     * Добавляет аудио-файл к сообщению
     *
     * @param string|array $url Ссылка или массив ссылок (ID)
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/audio
     */
    public function audio(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'audio');
        $this->sendAudio = true;

        return $this;
    }

    /**
     * Добавляет видео-файл к сообщению
     *
     * @param string|array $url Ссылка или массив ссылок (ID)
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/video
     */
    public function video(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'video');
        $this->sendVideo = true;

        return $this;
    }

    /**
     * Добавляет документ к сообщению
     *
     * @param string|array $url Ссылка или массив ссылок (ID)
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/doc
     */
    public function doc(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'document');
        $this->sendDocument = true;

        return $this;
    }

    /**
     * Добавляет "сущность" с форматированием к сообщению
     *
     * @param array $entities Массив с форматированием
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/entity
     */
    public function entities(array $entities): self
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * Добавляет изображение к сообщению
     *
     * @param string|array $url Ссылка или массив ссылок (ID)
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/img
     */
    public function img(string|array $url): static
    {
        $url = is_array($url) ? $url : [$url];
        $this->processMediaGroup($url, 'photo');
        $this->sendPhoto = true;

        return $this;
    }

    /**
     * Добавляет превью к сообщению с помощью ссылки.
     * Теперь этот метод только сохраняет URL, а вся логика применяется в момент отправки.
     *
     * @param string $url
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/mediaPreview
     */
    public function mediaPreview(string $url): static
    {
        $this->media_preview_url = $url;
        return $this;
    }

    /**
     * Отправляет стикер
     *
     * @param string $file_id
     *
     * @return Message
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/sticker
     */
    public function sticker(string $file_id): static
    {
        $this->sendSticker = true;
        $this->sticker_id = $file_id;

        return $this;
    }

    /**
     * Отправляет действие
     *
     * @param string|null $action
     *
     * @return Message
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/action
     */
    public function action(?string $action = 'typing'): self
    {
        $this->TGZ->sendAction($action);

        return $this;
    }

    private function applyMediaPreview(): void
    {
        // Если URL для превью не был установлен, ничего не делаем.
        if ($this->media_preview_url === null) {
            return;
        }

        $url = $this->media_preview_url;
        $invisibleCharacter = '​'; // U+200B ZERO-WIDTH SPACE

        if ($this->parse_mode === 'MarkdownV2' || $this->parse_mode === 'Markdown') {
            $this->text = "[$invisibleCharacter](".$url.")".$this->text;
        } elseif ($this->parse_mode === 'HTML') {
            $this->text = "<a href=\"".$url."\">".$invisibleCharacter."</a>".$this->text;
        } else {
            // Если parse_mode не задан, используем entities
            $this->text = $invisibleCharacter.$this->text;

            $lengthInUtf16 = strlen(
                    mb_convert_encoding($invisibleCharacter, 'UTF-16LE', 'UTF-8'),
                ) / 2;

            $entity = [
                'type'   => 'text_link',
                'offset' => 0,
                'length' => $lengthInUtf16,
                'url'    => $url,
            ];

            if ($this->entities === null) {
                $this->entities = [];
            }

            array_unshift($this->entities, $entity);
        }

        $this->media_preview_url = null;
    }


    /**
     * Отправляет сообщение
     *
     * @param int|null $chatID
     *
     * @return array
     *
     * @throws \JsonException
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/send
     */
    public function send(?int $chatID = null): array
    {
        // Применяем логику превью перед формированием параметров
        $this->applyMediaPreview();

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

            if ($this->parse_mode !== null) {
                $params['parse_mode'] = $this->parse_mode;
            }

            if ($this->entities !== null) {
                $params['entities'] = json_encode($this->entities, JSON_THROW_ON_ERROR);
            }

            return $this->api->callAPI('sendMessage', $params);
        }

        if (count($this->media) > 1 && !$this->sendVoice) {
            return $this->sendMediaGroup($params);
        }

        return $this->sendMediaType($params);
    }

    /**
     * Редактирует существующее сообщение
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     * @param bool        $caption
     *
     * @return array
     *
     * @throws \JsonException|\Exception
     */
    public function sendEdit(?string $messageID = null, ?int $chatID = null,
        bool $caption = false,
    ): array {
        if ($caption) {
            return $this->editCaption($messageID, $chatID);
        }

        return $this->editText($messageID, $chatID);
    }

    /**
     * Редактирует текст существующего сообщения
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     *
     * @return array
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/editText
     */
    public function editText(?string $messageID = null, ?int $chatID = null,
    ): array {
        // Применяем логику превью
        $this->applyMediaPreview();

        $identifier = $this->getIdentifier($messageID, $chatID);

        if (isset($this->text)) {
            $contentParams = [
                'text' => $this->text,
            ];
            if ($this->parse_mode !== null) {
                $contentParams['parse_mode'] = $this->parse_mode;
            }

            if ($this->entities !== null) {
                $contentParams['entities'] = json_encode($this->entities, JSON_THROW_ON_ERROR);
            }

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
     * Редактирует описание существующего сообщения (обязательное наличие медиа
     * в сообщении)
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     *
     * @return array
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/editCaption
     */
    public function editCaption(?string $messageID = null,
        ?int $chatID = null,
    ): array {
        // Применяем логику превью
        $this->applyMediaPreview();

        $identifier = $this->getIdentifier($messageID, $chatID);

        if (isset($this->text)) {
            $contentParams = [
                'caption' => $this->text,
            ];

            if ($this->parse_mode !== null) {
                $contentParams['parse_mode'] = $this->parse_mode;
            }

            if ($this->entities !== null) {
                $contentParams['caption_entities'] = json_encode($this->entities, JSON_THROW_ON_ERROR);
            }
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
     * Редактирует медиа существующего сообщения
     *
     * @param string|null $messageID
     * @param int|null    $chatID
     *
     * @return array
     *
     * @throws \Exception
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/messageMethods/editCaption
     */
    public function editMedia(?string $messageID = null, ?int $chatID = null,
    ): array {
        $identifier = $this->getIdentifier($messageID, $chatID);
        $postFields = []; // Инициализация

        if (isset($this->media)) {

            if ($this->parse_mode !== null) {
                $postFields['parse_mode'] = $this->parse_mode;
            }

            if ($this->entities !== null) {
                $postFields['entities'] = json_encode($this->entities, JSON_THROW_ON_ERROR);
            }

            foreach ($this->media as $item) {
                if (strpos($item['media'], 'attach://') === 0) {
                    $fileKey = str_replace('attach://', '', $item['media']);
                    $postFields[$fileKey] = $this->files[$fileKey];
                }
            }

        } else {
            throw new \Exception(
                'Необходимо установить свойство text перед вызовом sendEditText',
            );
        }

        $params = $identifier + $postFields;
        $params += $this->kbd;
        $params += $this->params_additionally;

        return $this->api->callAPI('editMessageMedia', $params);
    }


    private function getIdentifier(?string $messageID = null,
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
        ];

        if($this->parse_mode !== null) {
            $params1['parse_mode'] = $this->parse_mode;
        }

        if ($this->entities !== null) {
            $params1['caption_entities'] = json_encode($this->entities, JSON_THROW_ON_ERROR);
        }

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
        if($this->parse_mode !== null) {
            $params['parse_mode'] = $this->parse_mode;
        }

        if ($this->entities !== null) {
            $params['caption_entities'] = json_encode($this->entities, JSON_THROW_ON_ERROR);
        }

        $params[$type] = str_contains($this->media[0]['media'], 'attach://')
            ? $this->files['file1'] : $this->media[0]['media'];

        return $this->api->callAPI('send'.ucfirst($type), $params);
    }
}