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
    public array $thumb = [];

    public function __construct(string $type, TGZ $TGZ)
    {
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

    public function thumb(string $url, int $width = 0, int $height = 0): self
    {
        $this->thumb = [
            'thumb_url' => $url,
        ];

        if ($width > 0) {
            $this->thumb['thumb_width'] = $width;
        }

        if ($height > 0) {
            $this->thumb['thumb_height'] = $height;
        }

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
            'reply_markup'          => $this->kbd,
        ];

        if (!empty($this->thumb)) {
            $params = array_merge($params, $this->thumb);
        }

        return $params;
    }

    public function create(): array
    {
        $return = [];
        switch ($this->type) {
            case 'article':
                $return = $this->createText();
                break;

        }

        return $return;
    }


}
