<?php

declare(strict_types=1);

namespace ZhenyaGR\TGZ;

use Exception;
use ZhenyaGR\TGZ\Contracts\ApiInterface;

class ApiClient implements ApiInterface
{
    private const API_BASE_URL = 'https://api.telegram.org/bot';
    private string $apiUrl;

    public function __construct(string $token)
    {
        $this->apiUrl = self::API_BASE_URL . $token . '/';
    }

    public function callAPI(string $method, ?array $params = []): array
    {
        $url = $this->apiUrl . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($responseJson, true);

        if ($httpCode >= 200 && $httpCode < 300 && $response['ok']) {
            return $response;
        }

        // Используем метод из трейта ErrorHandler для форматирования ошибки
        throw new Exception($this->TGAPIErrorMSG($response, $params));
    }

    // Магический метод __call тоже уместен здесь, чтобы делегировать вызовы
    public function __call(string $method, array $args = []): array
    {
        $params = $args[0] ?? [];
        return $this->callAPI($method, $params);
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }
}