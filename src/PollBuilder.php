<?php

namespace ZhenyaGR\TGZ;

class PollBuilder
{

    public string $type = 'regular';
    private TGZ $tg;
    public ?string $question_parse_mode = null;
    public ?string $question = null;
    public array $options = [];
    public bool $is_anonymous = true;
    public bool $allows_multiple_answers = false;
    public ?int $correct_option_id = null;
    public ?string $explanation_parse_mode = null;
    public ?string $explanation = null;
    public bool $is_closed = false;
    public ?int $open_period = null;
    public ?int $close_date = null;


    public static function create(string $type, TGZ $tg): self
    {
        return new self($type, $tg);
    }

    public function __construct(string $type, TGZ $tg)
    {
        $type = in_array($type, ['regular', 'quiz']) ? $type : 'regular';
        $this->type = $type;
        $this->tg = $tg;
    }

    public function parseMode(?string $parse_mode = ''): self
    {
        $parse_mode = in_array(
            $parse_mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true,
        ) ? $parse_mode : '';
        $this->explanation_parse_mode = $parse_mode;
        $this->question_parse_mode = $parse_mode;

        return $this;
    }

    public function questionParseMode(?string $parse_mode = ''): self
    {
        $parse_mode = in_array(
            $parse_mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true,
        ) ? $parse_mode : '';
        $this->question_parse_mode = $parse_mode;

        return $this;
    }

    public function question(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function addAnswers(string ...$answers): self
    {
        $this->options = array_merge($this->options, $answers);

        return $this;
    }

    public function isAnonymous(?bool $anon = true): self
    {
        $this->is_anonymous = $anon;

        return $this;
    }

    public function multipleAnswers(bool $multiple = true): self
    {
        $this->allows_multiple_answers = $multiple;

        return $this;
    }

    public function correctAnswer(int $id): self
    {
        if ($this->type === 'quiz') {
            $this->correct_option_id = $id;
        }

        return $this;
    }

    public function explanation(string $explanation): self
    {
        if ($this->type === 'quiz') {
            $this->explanation = $explanation;
        }

        return $this;
    }

    public function explanationParseMode(?string $parse_mode = ''): self
    {
        if ($this->type === 'quiz') {
            $parse_mode = in_array(
                $parse_mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true,
            ) ? $parse_mode : '';
            $this->explanation_parse_mode = $parse_mode;
        }

        return $this;
    }

    public function close(?bool $close = true): self
    {
        $this->is_closed = $close;

        return $this;
    }

    public function openPeriod(int $seconds): self
    {
        if ($seconds < 5 || $seconds > 600) {
            $seconds = 600;
        }

        $this->open_period = $seconds;
        $this->close_date = null;

        return $this;
    }

    public function closeDate(int $timestamp): self
    {
        $now = time();

        if ($timestamp < ($now + 5) || $timestamp > ($now + 600)) {
            $timestamp = $now + 600;
        }

        $this->close_date = $timestamp;
        $this->open_period = null;

        return $this;
    }

    public function send(?int $chatID = null): array
    {
        $this->tg->initChatID($initChatID);

        $params = [
            'chat_id' => $chatID ?? $initChatID,
        ];

        $params['question'] = $this->question;
        $params['options'] = json_encode($this->options, JSON_THROW_ON_ERROR);
        $params['is_anonymous'] = $this->is_anonymous;
        $params['type'] = $this->type;
        $params['allows_multiple_answers'] = $this->allows_multiple_answers;
        $params['question_parse_mode'] = $this->question_parse_mode;
        $params['is_closed'] = $this->is_closed;

        if ($this->type === 'quiz') {
            $params['correct_option_id'] = $this->correct_option_id;

            if (!empty($this->explanation)) {
                $params['explanation'] = $this->explanation;
            }

            if (!empty($this->explanation_parse_mode)) {
                $params['explanation_parse_mode']
                    = $this->explanation_parse_mode;
            }

            if (!empty($this->open_period)) {
                $params['open_period'] = $this->open_period;
            }

            if (!empty($this->close_date)) {
                $params['close_date'] = $this->close_date;
            }
        }

        return $this->tg->callAPI('sendPoll', $params);
    }


}