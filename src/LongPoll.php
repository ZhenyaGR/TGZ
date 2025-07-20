<?php

declare(strict_types=1);

namespace ZhenyaGR\TGZ;

use Closure;
use Throwable;
use ZhenyaGR\TGZ\Contracts\ApiInterface;

/**
 * Класс для получения обновлений от Telegram в режиме Long Polling.
 * Является "генератором" событий, а не их обработчиком.
 */
class LongPoll
{
    private ApiInterface $api;
    private int $timeout;
    private int $offset = 0;

    /**
     * Конструктор принимает зависимости. Он используется "под капотом"
     * и нужен для обеспечения гибкости и возможности тестирования.
     * Конечный пользователь обычно использует статический метод create().
     */
    public function __construct(ApiInterface $api, int $timeout = 20)
    {
        $this->api = $api;
        $this->timeout = $timeout;
    }

    /**
     * Удобный статический метод-фабрика для простого запуска.
     * Это рекомендуемый способ создания объекта LongPoll.
     *
     * @param string $token   Токен вашего бота.
     * @param int    $timeout Таймаут для long-polling запроса в секундах.
     *
     * @return self
     */
    public static function create(string $token, int $timeout = 20): self
    {
        // Вся сложность создания зависимостей спрятана здесь.
        // Пользователю не нужно ничего знать про ApiClient.
        $api = new ApiClient($token);

        return new self($api, $timeout);
    }

    /**
     * Запускает бесконечный цикл прослушивания обновлений от Telegram.
     *
     * @param Closure $handler Анонимная функция, которая будет вызвана для
     *                         каждого нового обновления. Она должна принимать
     *                         один аргумент: готовый к работе объект TGZ.
     *                         Пример: function(TGZ $tg) {ваш код}
     */
    public function listen(Closure $handler): void
    {
        echo "Long Poll запущен... Нажмите Ctrl+C для остановки.\n";

        while (true) {
            // Используем наш унифицированный API клиент
            $response = json_decode(
                file_get_contents(
                    $this->api->getApiUrl() . 'getUpdates?' . http_build_query(
                        [
                            'offset'          => $this->offset,
                            'timeout'         => $this->timeout,
                            'allowed_updates' => [],
                            // Можно указать, какие типы обновлений получать
                        ],
                    ),
                ), true, 512, JSON_THROW_ON_ERROR,
            );

            $updates = $response['result'];

            if (empty($updates)) {
                continue; // Если обновлений нет, просто начинаем следующий запрос
            }

            // Перебираем все полученные обновления
            foreach ($updates as $updateData) {
                // ШАГ 1: Для КАЖДОГО обновления создаем свой собственный,
                // чистый и независимый объект контекста.
                $context = new UpdateContext($updateData);

                // ШАГ 2: Создаем новый экземпляр TGZ, "заряженный" этим контекстом.
                // Мы передаем один и тот же объект $this->api, а не создаем его заново.
                $tg_instance = new TGZ($this->api, $context);

                // ШАГ 3: Вызываем обработчик пользователя и ЯВНО передаем ему
                // полностью готовый к работе экземпляр TGZ.
                $handler($tg_instance);

                // Обновляем offset, чтобы не получать это же обновление снова
                $this->offset = $updateData['update_id'] + 1;
            }

        }
    }
}