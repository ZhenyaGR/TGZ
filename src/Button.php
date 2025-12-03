<?php

namespace ZhenyaGR\TGZ;

class Button {

    /**
     * Inline кнопка с Callback Data
     *
     * @var string $text Текст кнопки
     * @var string $data CallbackData
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/button#cb
     */
    public static function cb(string $text, string $data): array
    {
        return ['text' => $text, 'callback_data' => $data];
    }

    /**
     * Inline кнопка-ссылка
     *
     * @var string $text Текст кнопки
     * @var string $url Ссылка
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/button#url
     */
    public static function url(string $text, string $url): array
    {
        return ['text' => $text, 'url' => $url];
    }

    /**
     * Inline кнопка для WebApp
     *
     * @var string $text Текст кнопки
     * @var string $url Ссылка на приложение
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/button#webApp
     */
    public static function webApp(string $text, string $url): array
    {
        return ['text' => $text, 'web_app' => ['url' => $url]];
    }

    /**
     * Текстовая кнопка (для Reply)
     *
     * @var string $text Текст кнопки
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/button#text
     */
    public static function text(string $text): array
    {
        return ['text' => $text];
    }

    /**
     * Кнопка запроса контакта
     *
     * @var string $text Текст кнопки
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/button#contact
     */
    public static function contact(string $text): array
    {
        return ['text' => $text, 'request_contact' => true];
    }

    /**
     * Кнопка запроса геолокации
     *
     * @var string $text Текст кнопки
     *
     * @return array
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/button#location
     */
    public static function location(string $text): array
    {
        return ['text' => $text, 'request_location' => true];
    }

}