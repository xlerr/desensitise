<?php

namespace xlerr\desensitise;

use yii\grid\DataColumn;

class DesensitiseColumn extends DataColumn
{
    public $format = 'raw';

    public $plain = false;

    public function getDataCellValue($model, $key, $index)
    {
        $value = parent::getDataCellValue($model, $key, $index);

        return DesensitiseWidget::decrypt($value, $this->plain);
    }
}
