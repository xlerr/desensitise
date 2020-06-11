<?php

namespace xlerr\desensitise;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use xlerr\httpca\ComponentTrait;
use xlerr\httpca\RequestClient;
use yii\base\InvalidConfigException;

/**
 * Class Desensitized
 *
 * @package common\components
 */
class Desensitise extends RequestClient
{
    use ComponentTrait;

    const TYPE_PHONE_NUMBER     = 1; // 手机号
    const TYPE_IDENTITY_NUMBER  = 2; // 身份证号
    const TYPE_BANK_CARD_NUMBER = 3; // 银行卡号
    const TYPE_NAME             = 4; // 姓名
    const TYPE_EMAIL            = 5; // 邮箱
    const TYPE_ADDRESS          = 6; // 地址

    /**
     * @param string    $plain
     * @param int       $type
     * @param bool|null $hash
     * @param int       $expire
     * @param callable  $rejected
     *
     * @return mixed
     * @throws InvalidConfigException
     */
    public function execEncrypt(string $plain, int $type, bool $hash = null, int $expire = 0, callable $rejected = null)
    {
        $data = [
            [$plain, $type],
        ];

        $result = $this->encrypt($data, $expire, $rejected ?? EncryptException::throwFunc());
        $result = $result[0] ?? [];

        if (null === $hash) {
            return $result;
        } elseif ($hash) {
            return $result['hash'] ?? false;
        } else {
            return $result['plain_text'] ?? false;
        }
    }

    /**
     * @param string   $hash
     * @param bool     $plain
     * @param callable $rejected
     *
     * @return mixed
     * @throws InvalidConfigException
     */
    public function execDecrypt(string $hash, bool $plain = false, callable $rejected = null)
    {
        $result = $this->decrypt($hash, $plain, $rejected ?? EncryptException::throwFunc());

        return $result[$hash] ?? false;
    }

    /**
     * 加密
     *
     * @param array         $data [[plain, type], [plain, type]]
     * @param int           $expire
     * @param callable|null $rejected
     *
     * @return array
     * @example
     *  request data: [['62302123512929589', 1]]
     *  response data: [["plain_text": "623**********9589", "hash": "enc_01_100_269"]]
     */
    public function encrypt(array $data, int $expire = 0, callable $rejected = null)
    {
        foreach ($data as &$rows) {
            $rows = [
                'type'  => $rows[1],
                'plain' => strval($rows[0]),
            ];
        }

        $url = sprintf('encrypt%s/', ($expire > 0 ? '/expire/' . $expire : ''));

        return $this->do($url, $data, $rejected);
    }

    /**
     * 解码
     *
     * @param string|array  $data
     * @param bool          $plain
     * @param callable|null $rejected
     *
     * @return array
     */
    public function decrypt($data, $plain = false, callable $rejected = null)
    {
        $url = 'decrypt/' . ($plain ? 'plain/' : '');

        $data = array_map(function ($hash) {
            return ['hash' => $hash];
        }, (array)$data);

        return $this->do($url, $data, $rejected);
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return \Generator
     */
    protected function makeRequest($url, array $data)
    {
        $data = array_chunk($data, 10);
        foreach ($data as $set) {
            yield function () use ($url, $set) {
                return $this->client->postAsync($url, [
                    RequestOptions::JSON        => $set,
                    RequestOptions::HTTP_ERRORS => true,
                ]);
            };
        }
    }

    /**
     * @param string        $url
     * @param array         $data
     * @param callable|null $rejected
     *
     * @return array
     */
    protected function do($url, array $data, callable $rejected = null)
    {
        $resultSet = [];

        $requests = $this->makeRequest($url, $data);

        $pool = new Pool($this->client, $requests, [
            'fulfilled' => function (Response $response, $index) use (&$resultSet, $rejected) {
                $responseRaw = (string)$response->getBody();
                $result      = array_merge([
                    'code'    => self::FAILURE,
                    'message' => '返回值格式错误: ' . $responseRaw,
                    'data'    => [],
                ], (array)json_decode($responseRaw, true));
                if ($result['code'] === self::SUCCESS) {
                    $resultSet[$index] = (array)$result['data'];
                } elseif ($rejected) {
                    $rejected($result, $index);
                }
            },
            'rejected'  => function (RequestException $reason, $index) use ($rejected) {
                $rejected && $rejected([
                    'code'      => $reason->getCode(),
                    'message'   => $reason->getMessage(),
                    'exception' => $reason,
                ], $index);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        if (empty($resultSet)) {
            return $resultSet;
        }

        ksort($resultSet);

        return array_merge(...$resultSet);
    }
}
