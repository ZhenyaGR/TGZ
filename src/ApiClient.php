<?php

declare(strict_types=1);

namespace ZhenyaGR\TGZ;

use ZhenyaGR\TGZ\Contracts\ApiInterface;
use ZhenyaGR\TGZ;

class ApiClient implements ApiInterface
{
    private const API_BASE_URL = 'https://api.telegram.org';
    private string $apiUrl;
    private string $apiFileUrl;

    public function __construct(string $token)
    {
        $this->apiUrl = self::API_BASE_URL  . '/bot' . $token . '/';
        $this->apiFileUrl = self::API_BASE_URL  . '/file/bot' . $token . '/';
    }

    public function callAPI(string $method, ?array $params = []): array
    {
        $url = $this->apiUrl . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


        curl_close($ch);

        $response = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);

        if ($httpCode >= 200 && $httpCode < 300 && $response['ok']) {
            return $response;
        }

        throw new \RuntimeException(\TGZ::TGAPIErrorMSG($response, $params));
    }


    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getApiFileUrl(): string
    {
        return $this->apiFileUrl;
    }
}