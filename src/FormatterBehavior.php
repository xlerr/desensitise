<?php

namespace xlerr\desensitise;

use yii\base\Behavior;

class FormatterBehavior extends Behavior
{
    /**
     * @param string $hash
     * @param bool   $plain
     */
    public function asDDecrypt($hash, $plain = false)
    {
        return DesensitiseWidget::decrypt($hash, $plain);
    }
}
