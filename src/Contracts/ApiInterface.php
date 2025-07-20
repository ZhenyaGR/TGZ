<?php

declare(strict_types=1);

namespace ZhenyaGR\TGZ\Contracts;

interface ApiInterface
{
    /**
     * Выполняет вызов к Telegram Bot API.
     *
     * @param string $method Название метода API (например, 'sendMessage').
     * @param array|null $params Параметры запроса.
     * @return array Ответ от API в виде ассоциативного массива.
     * @throws \Exception В случае ошибки API.
     */
    public function callAPI(string $method, ?array $params = []): array;
}
