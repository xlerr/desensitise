<?php

namespace xlerr\desensitise;

class DesensitiseColumn extends \yii\grid\DataColumn
{
    public $format = 'raw';

    public function getDataCellValue($model, $key, $index)
    {
        $value = parent::getDataCellValue($model, $key, $index);
        return DesensitizedWidget::decrypt($value);
    }
}