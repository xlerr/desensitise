<?php

namespace xlerr\desensitise;

use yii\base\Behavior;

class FormatterBehavior extends Behavior
{
    /**
     * @param string $hash
     * @param bool   $plain
     * @param string $default
     *
     * @return string
     */
    public function asDDecrypt($hash, $plain = false, $default = '-')
    {
        if ($hash === '' || $hash === null) {
            return $default;
        }

        return DesensitiseWidget::decrypt($hash, $plain);
    }
}
