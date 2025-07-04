<?php

namespace ZhenyaGR\TGZ;

class Inline
{

    // Лимиты Telegram в байтах
//    private const VIDEO_SIZE_LIMIT = 20971520; // 20 MB
//    private const AUDIO_SIZE_LIMIT = 20971520; // 20 MB
//    private const GIF_SIZE_LIMIT   = 20971520; // 20 MB
//    private const PHOTO_SIZE_LIMIT = 5242880;  // 5 MB

    public string $type = 'article';
    public TGZ $TGZ;
    public string $parse_mode = '';
    public string $id = '';
    public string $title = '';
    public string $description = '';
    public string $message_text = '';
    public array $kbd = [];
    public array $params_additionally = [];
    public string $fileUrl = '';
    public string $fileId = '';
    public string $thumbUrl = '';
    public string $mimeType = '';
    public float $latitude = 0;
    public float $longitude = 0;
    public string $address = '';

    public function __construct(string $type, TGZ $TGZ)
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
        $this->parse_mode = $TGZ->parseModeDefault;
        $this->TGZ = $TGZ;
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function text(string $text): self
    {
        $this->message_text = $text;

        return $this;
    }

    public function fileUrl(string $url = ''): self
    {
        $this->fileUrl = $url;

        return $this;
    }

    public function fileID(string $id = ''): self
    {
        $this->fileId = $id;

        return $this;
    }

    public function mimeType(string $mime): self
    {
        $this->mimeType = $mime;

        return $this;
    }

    public function thumb(string $url = ''): self
    {
        $this->thumbUrl = $url;

        return $this;
    }

    public function kbd(array $buttons = []): self
    {
        $this->kbd = ['inline_keyboard' => $buttons];

        return $this;
    }

    public function params(array $params = []): self
    {
        $this->params_additionally = $params;

        return $this;
    }

    public function parseMode(string $mode = ''): self
    {
        if (!in_array($mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true)) {
            $mode = '';
        }
        $this->parse_mode = $mode;

        return $this;
    }

    public function coordinates(float $latitude, float $longitude): self
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        return $this;
    }

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

        $message = $message + $this->params_additionally;

        $params = $this->createParams();

        $params = $params + [
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

        $params = $params + [
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

        $params = $params + [
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

        $params = $params + [
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

        $params = $params + [
                'description' => $this->description,
                'caption'     => $this->message_text,
                'mime_type'   => $this->mimeType == '' ? 'video/mp4'
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

        $params = $params + [
                'description'  => $this->description,
                'caption'      => $this->message_text,
                'mime_type'    => $this->mimeType,
                'parse_mode'   => $this->parse_mode,
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

        $params = $params + [
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

        $params = $params + [
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

        $params = $params + [
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

        $params = $params + [
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