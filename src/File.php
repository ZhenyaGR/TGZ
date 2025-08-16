<?php

namespace ZhenyaGR\TGZ;

class File
{
    private ApiClient $api;
    public string $file_id = '';
    public string $file_path = '';
    public int $file_size = 0;

    public function __construct(string $file_id, ApiClient $api)
    {
        $this->file_id = $file_id;
        $this->api = $api;
    }

    /**
     * Получает информацию о файле и сохраняет его по указанному пути.
     *
     * @param string $path Путь для сохранения (может быть директорией или
     *                     полным путем к файлу).
     *
     * @return string Полный путь к сохраненному файлу.
     *
     * @throws \RuntimeException Если файл слишком большой, или не удалось
     *                           создать директорию/скачать файл.
     */
    public function save(string $path): string
    {
        // 1. Получаем метаданные файла от Telegram API
        $file_data = $this->api->callAPI(
            'getFile', ['file_id' => $this->file_id],
        );

        $this->file_path = $this->api->getApiFileUrl()
            .$file_data['result']['file_path'];
        $this->file_size = $file_data['result']['file_size'];

        // 2. Проверяем размер файла (лимит Telegram Bot API - 20 МБ)
        if ($this->file_size >= 20971520) {
            throw new \RuntimeException('Размер файла превышает 20 МБ');
        }

        // 3. Передаем управление методу для скачивания
        return $this->downloadFile($path);
    }

    private function downloadFile(string $path): string
    {
        $downloadUrl = $this->file_path;

        if (is_dir($path)) {
            // Убираем слэш в конце, если он есть, и добавляем один правильный
            $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $path .= basename($this->file_path);
        }

        $destinationPath = $path;
        $directory = dirname($destinationPath);

        if (!mkdir($directory, 0775, true)
            && !is_dir(
                $directory
            )
        ) {
            // Если mkdir вернул false, значит создать не удалось
            throw new \RuntimeException(
                'Не удалось создать директорию для сохранения файла: '
                .$directory,
            );
        }

        if (!copy($downloadUrl, $destinationPath)) {
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'неизвестная ошибка';
            throw new \RuntimeException(
                'Не удалось скачать или сохранить файл. Причина: '.$errorMessage
                .'. Путь: '.$destinationPath,
            );
        }

        return $destinationPath;
    }
}
// ПРИМЕЧАНИЕ: Для работы этого кода класс ApiClient должен иметь метод getFileDownloadUrl
/*
class ApiClient {
    private string $token;

    // ... другие методы

    public function getFileDownloadUrl(string $filePath): string
    {
        return 'https://api.telegram.org/file/bot' . $this->token . '/' . $filePath;
    }
}
Что было сделано и почему:
Исправлена ошибка в save(): В вашем исходном коде строка $this->file_size = $this->api->getApiUrl() . $file_data['result']['file_size']; была некорректной. Она объединяла URL и число, что приводило к неверному значению размера и ломало проверку. Я исправил её на $this->file_size = $file_data['result']['file_size'];, чтобы свойство содержало корректное число байт.
Формирование URL для скачивания: Файлы в Telegram скачиваются по специальному URL. Я предположил, что в вашем классе ApiClient есть (или должен быть) метод getFileDownloadUrl(string $filePath), который собирает этот URL. Это хорошая практика, чтобы логика работы с API была инкапсулирована в одном месте.
Определение конечного пути: Добавлена логика, которая проверяет, является ли переданный $path директорией.
Если да, то к этому пути добавляется оригинальное имя файла (которое мы получаем из basename($this->file_path)).
Если $path — это уже полный путь к файлу (например, /var/www/uploads/my_new_photo.jpg), то он используется как есть.
Создание директории: Ваш код для создания директории был немного усложнен. Я его упростил и оставил суть: если директории, в которую нужно сохранить файл, не существует, скрипт пытается её создать рекурсивно.
Скачивание файла: Самый простой и эффективный способ для этой задачи — использовать функцию copy(). Она умеет работать с http:// потоками и копирует содержимое по URL напрямую в файл на диске, не потребляя много оперативной памяти.
Обработка ошибок: Если copy() не удается (например, из-за проблем с сетью или правами на запись), она вернет false. Этот случай отлавливается, и выбрасывается исключение RuntimeException с понятным сообщением.
Возвращаемое значение: Методы save и downloadFile теперь возвращают полный путь к сохраненному файлу. Это очень удобно, так как код, который вызвал этот метод, сразу будет знать, где лежит скачанный файл.

*/
