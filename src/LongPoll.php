<?php

namespace ZhenyaGR\TGZ;

class LongPoll extends TGZ
{

    public int $timeout;

    public static function create(string $token, int $timeout = 10): self
    {
        return new self($token, $timeout);
    }

    public function __construct(string $token, int $timeout = 10)
    {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
        $this->timeout = $timeout;
    }

    public function listen($func)
    {
        $offset = 0;
        while (true) {
            $params = ['timeout' => $this->timeout, 'offset' => $offset];
            $updates = json_decode(
                           file_get_contents(
                               $this->apiUrl.'getUpdates?'.http_build_query(
                                   $params,
                               ),
                           ), true, 512, JSON_THROW_ON_ERROR,
                       )['result'];

            if (empty($updates)) {
                continue;
            }

            foreach ($updates as $update) {
                $this->update = $update;
                $func();
            }

            $offset = !empty($updates) ? $updates[count($updates)
                - 1]['update_id']
                + 1 : null;
        }
    }
}