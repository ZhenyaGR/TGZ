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

    public function __construct() {}

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
     * Устанавливает текст на кнопках навигации ("Предыдущая страница",
     * "Следующая страница")
     *
     * @param string $firstText Первая страница
     * @param string $lastText Последняя страница
     *
     * @return Pagination
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/setSigns
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
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/paginationMethods/addReturnButton
     */
    public function addReturnButton(string $text, string $callbackData): self
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

        $totalItems = count($this->items);
        $totalPages = (int)ceil($totalItems / $this->perPage);

        if ($this->page > $totalPages) {
            $this->page = $totalPages;
        }

        $offset = ($this->page - 1) * $this->perPage;
        $pageItems = array_slice($this->items, $offset, $this->perPage);

        $keyboard = array_chunk($pageItems, $this->columns);

        if ($totalPages > 1) {
            $navigationRow = [];
            $sideNavigationRow = [];

            if ($this->page !== 1) {
                $navigationRow[] = [
                    'text'          => $this->prevText,
                    'callback_data' => $this->callbackPrefix.($this->page - 1),
                ];
            }

            if ($this->page !== $totalPages) {
                $navigationRow[] = [
                    'text'          => $this->nextText,
                    'callback_data' => $this->callbackPrefix.($this->page + 1),
                ];
            }

            $keyboard[] = $navigationRow;


//            if ($this->lastText !== null) {
//                $sideNavigationRow[] = [
//                    'text'          => $this->lastText,
//                    'callback_data' => $this->callbackPrefix.($totalPages),
//                ];
//            }
//
//            if ($this->firstText !== null) {
//                $sideNavigationRow[] = [
//                    'text'          => $this->firstText,
//                    'callback_data' => $this->callbackPrefix.(1),
//                ];
//            }
//
//            $keyboard[] = $sideNavigationRow;
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
}
