<?php

namespace ZhenyaGR\TGZ;

class Inline
{

    // Лимиты Telegram в байтах
//    private const VIDEO_SIZE_LIMIT = 20971520; // 20 MB
//    private const AUDIO_SIZE_LIMIT = 20971520; // 20 MB
//    private const GIF_SIZE_LIMIT   = 20971520; // 20 MB
//    private const PHOTO_SIZE_LIMIT = 5242880;  // 5 MB

    private string $type;
    private string $parse_mode = '';
    private string $id = '';
    private string $title = '';
    private string $description = '';
    private string $message_text = '';
    private array $kbd = [];
    private array $params_additionally = [];
    private string $fileUrl = '';
    private string $fileId = '';
    private string $thumbUrl = '';
    private string $mimeType = '';
    private float $latitude = 0;
    private float $longitude = 0;
    private string $address = '';

    public function __construct(string $type, string $defaultParseMode = '')
    {
        if (!in_array(
            $type,
            ['article', 'location', 'mpeg4_gif', 'venue', 'photo',
             'gif', 'video', 'audio', 'voice', 'document'],
        )
        ) {
            $type = 'article';
        }

        $this->type = $type;
        $this->parse_mode = $defaultParseMode;
    }

    /**
     * Задает уникальный идентификатор
     *
     * @param string $id
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/id
     */
    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Задает заголовок
     *
     * @param string $title
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/title
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Задает описание
     *
     * @param string $description
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/description
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Задает текст сообщения
     *
     * @param string $text
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/text
     */
    public function text(string $text): self
    {
        $this->message_text = $text;

        return $this;
    }

    /**
     * Задает URL медиа-файла
     *
     * @param string $url
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/fileUrl
     */
    public function fileUrl(string $url = ''): self
    {
        $this->fileUrl = $url;

        return $this;
    }

    /**
     * Задает ID медиа-файла
     *
     * @param string $id
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/fileID
     */
    public function fileID(string $id = ''): self
    {
        $this->fileId = $id;

        return $this;
    }

    /**
     * Задает MIME-тип медиа-файла
     *
     * @param string $mime
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/mimeType
     */
    public function mimeType(string $mime): self
    {
        $this->mimeType = $mime;

        return $this;
    }

    /**
     * Задает URL миниатюры
     *
     * @param string $url
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/thumb
     */
    public function thumb(string $url = ''): self
    {
        $this->thumbUrl = $url;

        return $this;
    }

    /**
     * Задает inline-клавиатуру
     *
     * @param array $buttons
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/kbd
     */
    public function kbd(array $buttons = []): self
    {
        $this->kbd = ['inline_keyboard' => $buttons];

        return $this;
    }

    /**
     * Добавляет дополнительные параметры
     *
     * @param array $params
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/params
     */
    public function params(array $params = []): self
    {
        $this->params_additionally = $params;

        return $this;
    }

    /**
     * Задает режим парсинга
     *
     * @param string $mode
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/parseMode
     */
    public function parseMode(string $mode = ''): self
    {
        if (!in_array($mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true)) {
            $mode = '';
        }
        $this->parse_mode = $mode;

        return $this;
    }

    /**
     * Задает координаты
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/coordinates
     */
    public function coordinates(float $latitude, float $longitude): self
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Задает адрес
     *
     * @param string $address
     *
     * @return Inline
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/address
     */
    public function address(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    private function createParams(): array
    {
        return [
            'type'  => $this->type,
            'id'    => $this->id,
            'title' => $this->title,
        ];
    }

    private function addKbdInParams(&$params): void
    {
        if (!empty($this->kbd)) {
            $params['reply_markup'] = $this->kbd;
        }
    }

    private function createText(): array
    {
        $message = [
            'message_text' => $this->message_text,
            'parse_mode'   => $this->parse_mode,
        ];

        $message += $this->params_additionally;

        $params = $this->createParams();

        $params += [
            'description'           => $this->description,
            'input_message_content' => $message,
        ];

        if (!empty($this->thumbUrl)) {
            $params['thumb_url'] = $this->thumbUrl;
        }

        $this->addKbdInParams($params);

        return $params;
    }

    private function createPhoto(): array
    {
        $params = $this->createParams();

        $params += [
            'description'   => $this->description,
            'caption'       => $this->message_text,
            'thumbnail_url' => empty($this->thumbUrl) ? $this->fileUrl
                : $this->thumbUrl,
            'parse_mode'    => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['photo_url'] = $this->fileUrl;
        } else {
            $params['photo_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        return $params + $this->params_additionally;
    }

    private function createGif(): array
    {
        $params = $this->createParams();

        $params += [
            'description'   => $this->description,
            'caption'       => $this->message_text,
            'thumbnail_url' => empty($this->thumbUrl) ? $this->fileUrl
                : $this->thumbUrl,
            'parse_mode'    => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['gif_url'] = $this->fileUrl;
        } else {
            $params['gif_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        return $params + $this->params_additionally;
    }

    private function createMpeg4Gif(): array
    {
        $params = $this->createParams();

        $params += [
            'description'   => $this->description,
            'caption'       => $this->message_text,
            'thumbnail_url' => empty($this->thumbUrl) ? $this->fileUrl
                : $this->thumbUrl,
            'parse_mode'    => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['mpeg4_url'] = $this->fileUrl;
        } else {
            $params['mpeg4_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        return $params + $this->params_additionally;
    }

    private function createVideo(): array
    {
        $params = $this->createParams();

        $params += [
            'description' => $this->description,
            'caption'     => $this->message_text,
            'mime_type'   => $this->mimeType === '' ? 'video/mp4'
                : $this->mimeType,
            'parse_mode'  => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['video_url'] = $this->fileUrl;
        } else {
            $params['video_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        if (!empty($this->thumbUrl)) {
            $params['thumbnail_url'] = $this->thumbUrl;
        }

        return $params + $this->params_additionally;
    }

    private function createDocument(): array
    {
        $params = $this->createParams();

        $params += [
            'description' => $this->description,
            'caption'     => $this->message_text,
            'mime_type'   => $this->mimeType,
            'parse_mode'  => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['document_url'] = $this->fileUrl;
        } else {
            $params['document_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        if (!empty($this->thumbUrl)) {
            $params['thumbnail_url'] = $this->thumbUrl;
        }

        return $params + $this->params_additionally;
    }

    private function createAudio(): array
    {
        $params = $this->createParams();

        $params += [
            'caption'    => $this->message_text,
            'parse_mode' => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['audio_url'] = $this->fileUrl;
        } else {
            $params['audio_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        return $params + $this->params_additionally;
    }

    private function createVoice(): array
    {
        $params = $this->createParams();

        $params += [
            'caption'    => $this->message_text,
            'parse_mode' => $this->parse_mode,
        ];

        if (!empty($this->fileUrl)) {
            $params['voice_url'] = $this->fileUrl;
        } else {
            $params['voice_file_id'] = $this->fileId;
        }

        $this->addKbdInParams($params);

        return $params + $this->params_additionally;
    }

    private function createLocation(): array
    {
        $params = $this->createParams();

        $params += [
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ];

        $this->addKbdInParams($params);

        if (!empty($this->thumbUrl)) {
            $params['thumbnail_url'] = $this->thumbUrl;
        }

        return $params + $this->params_additionally;
    }

    private function createVenue(): array
    {
        $params = $this->createParams();

        $params += [
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
            'address'   => $this->address,
        ];

        $this->addKbdInParams($params);

        if (!empty($this->thumbUrl)) {
            $params['thumbnail_url'] = $this->thumbUrl;
        }

        return $params + $this->params_additionally;
    }

    /**
     * Собирает все данные в один массив
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/inlineMethods/create
     */
    public function create(): array
    {
        $return = [];
        switch ($this->type) {
            case 'article':
                $return = $this->createText();
                break;

            case 'photo':
                $return = $this->createPhoto();
                break;

            case 'gif':
                $return = $this->createGif();
                break;

            case 'mpeg4_gif':
                $return = $this->createMpeg4Gif();
                break;

            case 'audio':
                $return = $this->createAudio();
                break;

            case 'video':
                $return = $this->createVideo();
                break;

            case 'voice':
                $return = $this->createVoice();
                break;

            case 'document':
                $return = $this->createDocument();
                break;

            case 'location':
                $return = $this->createLocation();
                break;

            case 'venue':
                $return = $this->createVenue();
                break;

        }

        return $return;
    }

}