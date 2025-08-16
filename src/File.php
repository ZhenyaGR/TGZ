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

        // Предполагается, что у вас есть метод для получения полного URL файла
        $this->file_path = $this->api->getApiFileUrl()
            . $file_data['result']['file_path'];
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
            // Если передан путь к директории, добавляем к нему имя файла
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $path .= basename($this->file_path);
        }

        $destinationPath = $path;
        $directory = dirname($destinationPath);

        // Исправленная логика: сначала проверяем, потом создаем
        if (!is_dir($directory)) {
            // Если директории нет, пытаемся её создать рекурсивно
            if (!mkdir($directory, 0775, true)) {
                // Если создать не удалось - выбрасываем исключение
                throw new \RuntimeException(
                    'Не удалось создать директорию для сохранения файла: ' . $directory
                );
            }
        }

        if (!@copy($downloadUrl, $destinationPath)) {
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'неизвестная ошибка';
            throw new \RuntimeException(
                'Не удалось скачать или сохранить файл. Причина: ' . $errorMessage
                . '. Путь: ' . $destinationPath
            );
        }

        return $destinationPath;
    }
}