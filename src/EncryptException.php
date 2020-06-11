<?php

namespace xlerr\desensitise;

use Exception;

class EncryptException extends Exception
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Encrypt Exception';
    }

    public static function throwFunc()
    {
        return function ($response) {
            throw new self($response['message']);
        };
    }
}
