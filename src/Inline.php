<?php

namespace ZhenyaGR\TGZ;

class Inline
{
    public string $type = 'article';
    public TGZ $TGZ;
    public string $parse_mode = '';
    public string $id = '';
    public string $title = '';
    public string $description = '';
    public string $message_text = '';
    public array $kbd = [];
    public array $params_additionally = [];
    public string $imgUrl = '';
    public string $thumbUrl = '';

    public function __construct(string $type, TGZ $TGZ)
    {
        if (!in_array(
            $type,
            ['article', 'contact', 'location', 'mpeg4_gif', 'venue', 'photo',
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

    public function img(string $url = ''): self
    {
        $this->imgUrl = $url;

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

    public function msg(Message $msg): self
    {
        $this->message_text = $msg->text;
        $this->parse_mode = $msg->parse_mode;
        $this->params_additionally = $msg->params_additionally;
        $this->kbd($msg->buttons);
        $this->imgUrl = $msg->imgUrl;

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

    private function createText(): array
    {
        $message = [
            'message_text' => $this->message_text,
            'parse_mode'   => $this->parse_mode,
        ];


        $message = array_merge($message, $this->params_additionally);

        $params = [
            'type'                  => $this->type,
            'id'                    => $this->id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'input_message_content' => $message,
        ];

        if (!empty($this->thumb)) {
            $params = array_merge($params, $this->thumb);
        }

        if (!empty($this->kbd)) {
            $params = array_merge($params, ['reply_markup' => $this->kbd]);
        }

        return $params;
    }

    private function createPhoto(): array
    {
        $params = [
            'type'         => $this->type,
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'caption'      => $this->message_text,
            'photo_url'    => $this->imgUrl,
            'trumb_url'    => $this->thumbUrl ?? $this->imgUrl,
            'parse_mode'   => $this->parse_mode,
        ];

        if (!empty($this->kbd)) {
            $params = array_merge($params, ['reply_markup' => $this->kbd]);
        }

        return array_merge($params, $this->params_additionally);
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

        }

        return $return;
    }


}
