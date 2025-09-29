<?php

namespace ZhenyaGR\TGZ;

class File
{
    private array $file_info = [];
    private ApiClient $api;
    private string $file_id;

    private const MAX_DOWNLOAD_SIZE_BYTES = 20 * 1024 * 1024;

    public function __construct(string $file_id, ApiClient $api,
    ) {
        $this->api = $api;
        $this->file_id = $file_id;
    }

    public function getFileInfo(): array
    {
        if (empty($this->file_info)) {
            $this->file_info = $this->api->callAPI(
                'getFile', ['file_id' => $this->file_id],
            );
        }

        return $this->file_info['result'];
    }

    /**
     * Возвращает размер файла в байтах
     *
     * @return int Размер в битах
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/file
     */
    public function getFileSize(): int
    {
        return $this->getFileInfo()['file_size'];
    }

    public function getFilePath(): string
    {
        return $this->api->getApiFileUrl().$this->getFileInfo()['file_path'];
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
     *
     * @see https://zhenyagr.github.io/TGZ-Doc/classes/file
     */
    public function save(string $path): string
    {
        if ($this->getFileSize() >= $this::MAX_DOWNLOAD_SIZE_BYTES) {
            throw new \RuntimeException('Размер файла превышает 20 МБ');
        }

        // 3. Передаем управление методу для скачивания
        return $this->downloadFile($path);
    }

    private function downloadFile(string $path): string
    {
        $downloadUrl = $this->getFilePath();

        if (is_dir($path)) {
            // Если передан путь к директории, добавляем к нему имя файла
            $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $path .= basename($downloadUrl);
        }

        $destinationPath = $path;
        $directory = dirname($destinationPath);

        // Cначала проверяем, потом создаем
        // Если директории нет, пытаемся её создать рекурсивно
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            // Если создать не удалось - выбрасываем исключение
            throw new \RuntimeException(
                'Не удалось создать директорию для сохранения файла: '
                .$directory,
            );
        }

        if (!@copy($downloadUrl, $destinationPath)) {
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'неизвестная ошибка';
            throw new \RuntimeException(
                'Не удалось скачать или сохранить файл. Причина: '.$errorMessage
                .'. Путь: '.$destinationPath,
            );
        }

        return $destinationPath;
    }

    public static function getFileId(array $context, ?string $type = null,
    ): ?string {
        $message = $context['result'] ?? $context['message'] ?? [];
        if (empty($message)) {
            return null;
        }

        if ($type !== null) {
            return match ($type) {
                'photo' => end($message['photo'])['file_id'],
                'audio' => $message['audio']['file_id'],
                'video' => $message['video']['file_id'],
                'document' => $message['document']['file_id'],
                'voice' => $message['voice']['file_id'],
                'sticker' => $message['sticker']['file_id'],
                default => null,
            };
        }

        $fileTypes = [
            'photo', 'document', 'video', 'audio',
            'voice', 'sticker', 'video_note', 'animation',
        ];

        foreach ($fileTypes as $fileType) {
            if (isset($message[$fileType])) {
                if ($fileType === 'photo') {
                    return end($message['photo'])['file_id'];
                }

                return $message[$fileType]['file_id'];
            }
        }

        return null;
    }
}