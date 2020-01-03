<?php

namespace xlerr\desensitise;

use GuzzleHttp\RequestOptions;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use xlerr\httpca\RequestClient;

/**
 * Class Desensitized
 * @package common\components
 */
class Desensitise extends RequestClient
{
    const TYPE_PHONE_NUMBER     = 1; // 手机号
    const TYPE_IDENTITY_NUMBER  = 2; // 身份证号
    const TYPE_BANK_CARD_NUMBER = 3; // 银行卡号
    const TYPE_NAME             = 4; // 姓名
    const TYPE_EMAIL            = 5; // 邮箱
    const TYPE_ADDRESS          = 6; // 地址

    /**
     * @param string $plain
     * @param int $type
     * @param array $options
     * @return mixed
     * @throws InvalidConfigException
     */
    public static function execEncrypt(string $plain, int $type, array $options = [])
    {
        $expire    = ArrayHelper::getValue($options, 'expire', 0);
        $path      = ArrayHelper::getValue($options, 'path', 0);
        $reference = ArrayHelper::getValue($options, 'reference');

        $desensitise = self::getHandler($reference);

        $data = [
            [$plain, $type],
        ];

        if (false === ($result = $desensitise->encrypt($data, $expire))) {
            Yii::$app->getSession()->setFlash('warning', vsprintf('%s: %s >>> %s', [
                __METHOD__,
                $plain,
                $desensitise->getError(),
            ]));
        }

        return ArrayHelper::getValue($result, $path);
    }

    /**
     * @param string $hash
     * @param bool $plain
     * @param null|array|string $reference
     * @return mixed
     * @throws InvalidConfigException
     */
    public static function execDecrypt(string $hash, bool $plain = false, $reference = null)
    {
        $desensitise = self::getHandler($reference);

        if (false === ($result = $desensitise->decrypt($hash, $plain))) {
            Yii::$app->getSession()->setFlash('warning', vsprintf('%s: %s >>> %s', [
                __METHOD__,
                $hash,
                $desensitise->getError(),
            ]));
        } else {
            return ArrayHelper::getValue($result, $hash);
        }
    }

    /**
     * @param string $reference
     * @return Desensitise|object
     * @throws InvalidConfigException
     */
    protected static function getHandler($reference = null)
    {
        if (null === $reference) {
            $reference = 'desensitise';
        }

        return Instance::ensure($reference, Desensitise::class);
    }

    /**
     * 加密
     * @param array $data [plain => type]
     * @param int $expire
     * @return array|bool|false
     * @example
     *  request data: [['62302123512929589', 1]]
     *  response data: [["plain_text": "623**********9589", "hash": "enc_01_100_269"]]
     */
    public function encrypt(array $data, int $expire = 0)
    {
        foreach ($data as &$rows) {
            list($plain, $type) = $rows;
            $rows = [
                'type'  => $type,
                'plain' => strval($plain),
            ];
        }

        $responseData = [];

        $data = array_chunk($data, 10);
        foreach ($data as $chunkData) {
            if ($this->doEncrypt($chunkData, $expire)) {
                $responseData += $this->getData();
                continue;
            }

            return false;
        }

        return $responseData;
    }

    /**
     * 解码
     * @param mixed $data
     * @param bool $plain
     * @return bool|mixed|null
     */
    public function decrypt($data, $plain = false)
    {
        $data = (array) $data;
        foreach ($data as &$hash) {
            $hash = compact('hash');
        }

        $responseData = [];
        $data         = array_chunk($data, 10);
        foreach ($data as $chunkData) {
            if ($this->doDecrypt($chunkData, $plain)) {
                $responseData += $this->getData();
                continue;
            }

            return false;
        }

        return $responseData;
    }

    /**
     * @param array $data [['type'  => 1, 'plain' => '62302123512929589'], ['type'  => 5, 'plain' => 'xlerr@qq.com'],]
     * @param int $expire
     * @return bool
     */
    public function doEncrypt(array $data, int $expire = 0)
    {
        $url = 'encrypt';
        if ($expire > 0) {
            $url .= '/expire/' . $expire;
        }

        return $this->post($url . '/', [
            RequestOptions::JSON => $data,
        ]);
    }

    /**
     * @param array $data [['hash' => 'enc_01_803680_269'], ['hash' => 'enc_05_12448700_154']]
     * @param bool $plain
     * @return bool
     */
    public function doDecrypt(array $data, $plain = false)
    {
        $url = 'decrypt';
        if ($plain) {
            $url .= '/plain';
        }

        return $this->post($url . '/', [
            RequestOptions::JSON => $data,
        ]);
    }
}
