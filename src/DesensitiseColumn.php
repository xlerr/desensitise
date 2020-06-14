<?php

namespace xlerr\desensitise;

use yii\web\View;

class DesensitiseColumn extends \yii\grid\DataColumn
{
    public $format = 'raw';

    public $plain = false;

    protected function registerWidget()
    {
        static $registered;
        if ($registered !== true) {
            $registered = true;
            $this->grid->getView()->on(View::EVENT_END_PAGE, function () {
                DesensitiseWidget::widget();
            });
        }
    }

    public function getDataCellValue($model, $key, $index)
    {
        $this->registerWidget();

        $value = parent::getDataCellValue($model, $key, $index);

        return DesensitiseWidget::decrypt($value, $this->plain);
    }
}
