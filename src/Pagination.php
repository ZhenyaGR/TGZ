<?php

namespace ZhenyaGR\TGZ;

class Pagination
{
    private array $items;               // Кнопки
    private int $perPage = 5;           // Количество кнопок на странице
    private int $columns = 1;           // Количество колонок
    private int $page = 1;              // Текущая страница
    private string $callbackPrefix;     // Префикс
    private string $prevText = "<";     // Предыдущая страница
    private string $nextText = ">";     // Следующая страница
    private null|string $returnButtonText = null;
    private null|string $returnButtonCallbackData = null;
    private null|string $firstText = null;
    private null|string $lastText = null;
    private bool $showFirstLast = false;
    private null|int $totalItems = null;
    private null|array $headerButtons = null;
    private PaginationLayout $navigationLayout = PaginationLayout::ROW;
    private PaginationMode $mode = PaginationMode::ARROWS;
    private PaginationNumberStyle|\Closure $numberStyle = PaginationNumberStyle::CLASSIC;
    private int $MaxPageBtn = 5;
    private string|null $ActiveBtnFormatPattern = null;
    private string|null $ActiveBtnFormatPatternLeft = null;
    private string|null $ActiveBtnFormatPatternRight = null;

    public function __construct() {}

    /**
     * Устанавливает вид кнопок навигации (Стрелки или Цифры)
     *
     * @param PaginationMode $mode Одна из констант: PaginationMode::ARROWS,
     *                             PaginationMode::NUMBERS
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setMode
     */
    public function setMode(PaginationMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Устанавливает максимальное количество кнопок страниц
     *
     * @param int $max
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setMaxPageBtn
     */
    public function setMaxPageBtn(int $max): self
    {
        $this->MaxPageBtn = $max;

        return $this;
    }

    /**
     * Устанавливает стиль для кнопок номеров
     *
     * Либо готовые стили: PaginationNumberStyle::CLASSIC и
     * PaginationNumberStyle::EMOJI
     *
     * Либо анонимная функция, которая принимает номер страницы и возвращает
     * текст для кнопки
     *
     * @param PaginationNumberStyle|callable $style
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setNumberStyle
     */
    public function setNumberStyle(PaginationNumberStyle|callable $style): self
    {
        if (is_callable($style)) {
            $this->numberStyle = \Closure::fromCallable($style);
        } else {
            $this->numberStyle = $style;
        }

        return $this;
    }

    /**
     * Устанавливает текст, которым будет выделяться активная кнопка
     *
     * Либо указать символы с двух сторон. Пример: ->setActivePageFormat('- ',
     * ' -');
     *
     * Либо указать паттерн для sprintf. Пример: ->setActivePageFormat('- %s
     * -');
     *
     * Результат в обоих примерах будет выглядеть так:
     *
     * 1, 2, - 3 -, 4, 5
     *
     *
     * @param string      $left_or_pattern
     * @param string|null $right
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setActivePageFormat
     */
    public function setActivePageFormat(string $left_or_pattern,
        string|null $right = null,
    ): self {
        if ($right === null) {
            $this->ActiveBtnFormatPattern = $left_or_pattern;
        } else {
            $this->ActiveBtnFormatPatternLeft = $left_or_pattern;
            $this->ActiveBtnFormatPatternRight = $right;
        }

        return $this;
    }

    /**
     * Устанавливает массив кнопок, из которых будут собираться страницы
     *
     * @param array $items Массив callback-кнопок
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setItems
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Устанавливает максимальное количество элементов, из которых будет
     * состоять список
     *
     * Удобно, чтобы не передавать весь список кнопок, если их слишком много
     *
     * @param int $totalItems
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setTotalItems
     */
    public function setTotalItems(int $totalItems): self
    {
        $this->totalItems = $totalItems;

        return $this;
    }

    /**
     * Устанавливает максимальное количество кнопок
     *
     * @param int $perPage
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setPerPage
     */
    public function setPerPage(int $perPage): self
    {
        if ($perPage <= 0) {
            throw new \LogicException('PerPage должен быть больше нуля');
        }

        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Устанавливает максимальное количество колонок
     *
     * @param int $columns
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setColumns
     */
    public function setColumns(int $columns): self
    {
        if ($columns <= 0) {
            throw new \LogicException('Columns должен быть больше нуля');
        }

        $this->columns = $columns;

        return $this;
    }

    /**
     * Устанавливает текущую страницу
     *
     * @param int $page
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setPage
     */
    public function setPage(int $page): self
    {
        if ($page <= 0) {
            throw new \LogicException('Page должен быть больше нуля');
        }

        $this->page = $page;

        return $this;
    }

    /**
     * Устанавливает Callback-префикс для кнопок навигации
     *
     * @param string $callbackPrefix
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setPrefix
     */
    public function setPrefix(string $callbackPrefix): self
    {
        if ($callbackPrefix === '') {
            throw new \LogicException('CallbackPrefix не должен быть пустым');
        }

        $this->callbackPrefix = $callbackPrefix;

        return $this;
    }

    /**
     * Устанавливает текст на кнопках навигации ("Предыдущая страница",
     * "Следующая страница")
     *
     * @param string $prevText Предыдущая страница
     * @param string $nextText Следующая страница
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setSigns
     */
    public function setSigns(string $prevText, string $nextText): self
    {
        if ($prevText === '' || $nextText === '') {
            throw new \LogicException(
                'PrevText или NextText не должны быть пустыми',
            );
        }

        $this->prevText = $prevText;
        $this->nextText = $nextText;

        return $this;
    }

    /**
     * Устанавливает текст на кнопках навигации ("Первая страница", "Последняя
     * страница")
     *
     * @param string $firstText Первая страница
     * @param string $lastText  Последняя страница
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setSideSigns
     */
    public function setSideSigns(string $firstText, string $lastText): self
    {
        if ($firstText === '' || $lastText === '') {
            throw new \LogicException(
                'FirstText или LastText не должны быть пустыми',
            );
        }

        $this->firstText = $firstText;
        $this->lastText = $lastText;
        $this->showFirstLast = true;

        return $this;
    }

    /**
     * Устанавливает режим отображения кнопок навигации
     *
     * @param PaginationLayout $layout Одна из констант: PaginationLayout::ROW,
     *                                 PaginationLayout::SPLIT,
     *                                 PaginationLayout::SMART
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setNavigationLayout
     */
    public function setNavigationLayout(PaginationLayout $layout): self
    {
        $this->navigationLayout = $layout;

        return $this;
    }

    /**
     * Добавляет кнопку "Назад"
     *
     * @param string $text         Текст Кнопки
     * @param string $callbackData CallbackData
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/addReturnBtn
     */
    public function addReturnBtn(string $text, string $callbackData): self
    {
        if ($text === '' || $callbackData === '') {
            throw new \LogicException(
                'Text или CallbackData не должны быть пустыми',
            );
        }

        $this->returnButtonText = $text;
        $this->returnButtonCallbackData = $callbackData;

        return $this;
    }

    /**
     * Добавляет ряд кнопок в начало страницы
     *
     * @param array $buttons Ряд кнопок вида [[button], [button]]
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/addHeaderBtn
     */
    public function addHeaderBtn(array $buttons): self
    {
        if (empty($buttons)) {
            throw new \LogicException(
                'Ряд не должен быть пустым',
            );
        }

        $this->headerButtons = $buttons;

        return $this;
    }

    /**
     * Возвращает максимальное количество страниц
     *
     * @return int
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/getTotalPage
     */
    public function getTotalPage(): int
    {
        $totalItems = $this->totalItems ?? count($this->items);

        return (int)ceil($totalItems / $this->perPage);
    }

    /**
     * Собирает страницу
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/create
     */
    public function create(): array
    {
        if (empty($this->items)) {
            return [];
        }

        $totalPages = $this->getTotalPage();

        if ($this->page > $totalPages) {
            $this->page = $totalPages;
        }

        $offset = ($this->page - 1) * $this->perPage;
        $pageItems = array_slice($this->items, $offset, $this->perPage);

        $keyboard = array_chunk($pageItems, $this->columns);

        if ($this->headerButtons !== null) {
            array_unshift($keyboard, $this->headerButtons);
        }

        if ($totalPages > 1) {
            if ($this->mode === PaginationMode::ARROWS) {
                $keyboard = $this->createArrowsKbd($keyboard, $totalPages);
            } elseif ($this->mode === PaginationMode::NUMBERS) {
                $keyboard = $this->createNumbersKbd($keyboard, $totalPages);
            }
        }

        if ($this->returnButtonText !== null
            && $this->returnButtonCallbackData !== null
        ) {
            $keyboard[] = [[
                               'text'          => $this->returnButtonText,
                               'callback_data' => $this->returnButtonCallbackData,
                           ]];
        }

        return $keyboard;
    }

    private function createArrowsKbd($keyboard, $totalPages): array
    {
        $innerButtons = [];
        $outerButtons = [];

        // Кнопка начало
        if ($this->showFirstLast && $this->page > 1) {
            $outerButtons['first'] = [
                'text'          => $this->firstText,
                'callback_data' => $this->callbackPrefix.'1',
            ];
        }

        // Кнопка предыдущая
        if ($this->page > 1) {
            $innerButtons['prev'] = [
                'text'          => $this->prevText,
                'callback_data' => $this->callbackPrefix.($this->page - 1),
            ];
        }

        // Кнопка вперед
        if ($this->page < $totalPages) {
            $innerButtons['next'] = [
                'text'          => $this->nextText,
                'callback_data' => $this->callbackPrefix.($this->page + 1),
            ];
        }

        // Кнопка конец
        if ($this->showFirstLast && $this->page < $totalPages) {
            $outerButtons['last'] = [
                'text'          => $this->lastText,
                'callback_data' => $this->callbackPrefix.$totalPages,
            ];
        }

        $totalNavButtons = count($innerButtons) + count($outerButtons);

        switch ($this->navigationLayout) {
            case PaginationLayout::SPLIT:
                $shouldSplit = !empty($outerButtons);
                break;

            case PaginationLayout::SMART:
                $shouldSplit = $totalNavButtons > 2;
                break;

            case PaginationLayout::ROW:
                $shouldSplit = false;
                break;
        }

        if ($shouldSplit) {
            if (!empty($innerButtons)) {
                $keyboard[] = array_values($innerButtons);
            }

            if (!empty($outerButtons)) {
                $keyboard[] = array_values($outerButtons);
            }

        } else {
            $row = [];
            if (isset($outerButtons['first'])) {
                $row[] = $outerButtons['first'];
            }
            if (isset($innerButtons['prev'])) {
                $row[] = $innerButtons['prev'];
            }
            if (isset($innerButtons['next'])) {
                $row[] = $innerButtons['next'];
            }
            if (isset($outerButtons['last'])) {
                $row[] = $outerButtons['last'];
            }

            if (!empty($row)) {
                $keyboard[] = $row;
            }
        }

        return $keyboard;
    }

    private function createNumbersKbd(array $keyboard, int $totalPages): array
    {
        $maxVisible = $this->MaxPageBtn;
        $current = $this->page;

        if ($totalPages <= $maxVisible) {
            $start = 1;
            $end = $totalPages;
        } else {
            $half = (int)floor($maxVisible / 2);
            $start = $current - $half;
            $end = $start + $maxVisible - 1;

            if ($start < 1) {
                $start = 1;
                $end = $maxVisible;
            }

            if ($end > $totalPages) {
                $end = $totalPages;
                $start = $totalPages - $maxVisible + 1;
            }
        }

        $row = [];
        for ($i = $start; $i <= $end; $i++) {
            $text = $this->formatPageNumber($i);

            if ($i === $current) {
                $text = $this->decorateActivePage($text);
            }

            $row[] = [
                'text'          => $text,
                'callback_data' => $this->callbackPrefix.$i,
            ];
        }

        $keyboard[] = $row;

        return $keyboard;
    }

    private function formatPageNumber(int $number): string
    {
        if ($this->numberStyle instanceof \Closure) {
            return ($this->numberStyle)($number);
        }

        return match ($this->numberStyle) {
            PaginationNumberStyle::EMOJI => $this->toEmojiNumber($number),
            PaginationNumberStyle::CLASSIC => (string)$number,
        };
    }

    private function decorateActivePage(string $text): string
    {
        if ($this->ActiveBtnFormatPattern !== null) {
            return sprintf($this->ActiveBtnFormatPattern, $text);
        }

        if ($this->ActiveBtnFormatPatternLeft !== null) {
            return $this->ActiveBtnFormatPatternLeft.$text
                .($this->ActiveBtnFormatPatternRight ?? '');
        }

        return $text;
    }

    private function toEmojiNumber(int $number): string
    {
        $map = [
            '0' => '0️⃣', '1' => '1️⃣', '2' => '2️⃣', '3' => '3️⃣',
            '4' => '4️⃣',
            '5' => '5️⃣', '6' => '6️⃣', '7' => '7️⃣', '8' => '8️⃣',
            '9' => '9️⃣',
        ];

        return str_replace(
            array_keys($map), array_values($map), (string)$number,
        );
    }


}

enum PaginationMode: int
{
    /**
     * Стандартные стрелки навигации "Предыдущая страница" и "Следующая
     * страница"
     */
    case ARROWS = 0;    // < >

    /**
     * Несколько номеров страниц на строке
     */
    case NUMBERS = 1;   // 1 2 3
}

enum PaginationNumberStyle: int
{
    /**
     * 1, 2, 3, ...
     */
    case CLASSIC = 0;

    /**
     * 1️⃣, 2️⃣, 3️⃣, ...
     */
    case EMOJI = 1;
}

enum PaginationLayout: int
{
    /**
     * Все 4 кнопки будут находиться на одной строке
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setNavigationLayout#возможные-значения-константы
     *
     */
    case ROW = 0;

    /**
     * Кнопки "Предыдущая страница" и "Следующая страница" будут находиться на
     * одной строке
     *
     * Кнопки "Первая страница" и "Последняя страница" будут находиться на
     * второй строке
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setNavigationLayout#возможные-значения-константы
     *
     */
    case SPLIT = 1;

    /**
     * Кнопки разных типов будут находиться на одной строке только при условии,
     * что их 2
     *
     * Иначе будут на разных
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setNavigationLayout#возможные-значения-константы
     *
     */
    case SMART = 2;
}



