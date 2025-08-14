<?php

namespace ZhenyaGR\TGZ;

class Poll
{

    private ApiClient $api;
    private UpdateContext $context;
    private string $type = 'regular';
    private ?string $question_parse_mode = null;
    private ?string $question = null;
    private array $options = [];
    private bool $is_anonymous = true;
    private bool $allows_multiple_answers = false;
    private ?int $correct_option_id = null;
    private ?string $explanation_parse_mode = null;
    private ?string $explanation = null;
    private bool $is_closed = false;
    private ?int $open_period = null;
    private ?int $close_date = null;

    public function __construct(string $type, ApiClient $api, UpdateContext $context)
    {
        $type = in_array($type, ['regular', 'quiz']) ? $type : 'regular';
        $this->type = $type;

        $this->context = $context;
        $this->api = $api;
    }

    /**
     * Устанавливает режим разметки для вопроса и объяснения
     *
     * @param string|null $parse_mode 'HTML', 'Markdown', 'MarkdownV2'
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/parseMode
     */
    public function parseMode(?string $parse_mode = ''): self
    {
        $parse_mode = in_array(
            $parse_mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true,
        ) ? $parse_mode : '';
        $this->explanation_parse_mode = $parse_mode;
        $this->question_parse_mode = $parse_mode;

        return $this;
    }

    /**
     * Устанавливает режим разметки для вопроса
     *
     * @param string|null $parse_mode 'HTML', 'Markdown', 'MarkdownV2'
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/parseMode
     */
    public function questionParseMode(?string $parse_mode = ''): self
    {
        $parse_mode = in_array(
            $parse_mode, ['HTML', 'Markdown', 'MarkdownV2', ''], true,
        ) ? $parse_mode : '';
        $this->question_parse_mode = $parse_mode;

        return $this;
    }

    /**
     * Задает вопрос для опроса
     *
     * @param string $question
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/question
     */
    public function question(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Добавляет ответы для опроса
     *
     * @param string ...$answers
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/addAnswers
     */
    public function addAnswers(string ...$answers): self
    {
        $this->options = array_merge($this->options, $answers);

        return $this;
    }

    /**
     * Устанавливает анонимность опроса
     *
     * @param bool|null $anon
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/isAnonymous
     */
    public function isAnonymous(?bool $anon = true): self
    {
        $this->is_anonymous = $anon;

        return $this;
    }

    /**
     * Устанавливает возможность выбора нескольких ответов
     *
     * @param bool|null $multiple
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/multipleAnswers
     */
    public function multipleAnswers(bool $multiple = true): self
    {
        $this->allows_multiple_answers = $multiple;

        return $this;
    }

    /**
     * Устанавливает правильный ответ (Начиная с 1)
     *
     * @param int $id
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/correctAnswer
     */
    public function correctAnswer(int $id): self
    {
        if ($this->type === 'quiz') {
            $this->correct_option_id = $id;
        }

        return $this;
    }

    /**
     * Устанавливает объяснение к опросу
     *
     * @param string $explanation
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/explanation
     */
    public function explanation(string $explanation): self
    {
        if ($this->type === 'quiz') {
            $this->explanation = $explanation;
        }

        return $this;
    }

    /**
     * Устанавливает режим разметки для объяснения
     *
     * @param string|null $parse_mode 'HTML', 'Markdown', 'MarkdownV2'
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/parseMode
     */
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

    /**
     * Сразу закрывает опрос
     *
     * @param bool|null $close
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/close
     */
    public function close(?bool $close = true): self
    {
        $this->is_closed = $close;

        return $this;
    }

    /**
     * Устанавливает время открытия опроса в секундах
     *
     * @param int $seconds
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/openPeriod
     */
    public function openPeriod(int $seconds): self
    {
        if ($seconds < 5 || $seconds > 600) {
            $seconds = 600;
        }

        $this->open_period = $seconds;
        $this->close_date = null;

        return $this;
    }

    /**
     * Устанавливает дату, когда закроется опрос
     *
     * @param int $timestamp
     *
     * @return Poll
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/closeDate
     */
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

    /**
     * Отправляет опрос
     *
     * @param int|null $chatID
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/pollMethods/send
     *
     * @throws \JsonException
     */
    public function send(?int $chatID = null): array
    {
        $params = [
            'chat_id' => $chatID ?? $this->context->getChatId(),
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

        return $this->api->callAPI('sendPoll', $params);
    }


}