<?php

namespace xlerr\desensitise;

use xlerr\httpca\RequestClient;
use Yii;
use yii\base\Widget;
use yii\di\Instance;
use yii\helpers\Json;

class DesensitiseWidget extends Widget
{
    /**
     * @var Desensitise
     */
    public $desensitise = 'desensitise';

    /**
     * @var bool
     */
    public $plain = false;

    public static $decryptList = [];

    public static function decrypt($data)
    {
        if (empty($data)) {
            return $data;
        }

        array_push(self::$decryptList, $data);

        return sprintf('<span class="desensitization" data-dck="%s">%s</span>', $data, $data);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if (is_string($this->desensitise)) {
            $this->desensitise = Instance::ensure($this->desensitise, RequestClient::class);
        }

        parent::init();
    }

    public function run()
    {
        $dcs = '[]';
        if (!empty(self::$decryptList)) {
            if ($decryption = $this->desensitise->decrypt(self::$decryptList, $this->plain)) {
                $dcs = Json::encode($decryption);
            } else {
                Yii::$app->getSession()->setFlash('warning', '<b>[脱敏]</b> ' . $this->desensitise->getError());
            }
        }

        $js = <<<EOF
(function ($) {
    let dcs = $dcs;
    $('span.desensitization').each(function () {
        let dck = $(this).data('dck');
        if (undefined !== dcs[dck]) {
            $(this).text(dcs[dck]);
        }
    });
})(jQuery);
EOF;
        $this->getView()->registerJs($js);

        return '';
    }

    public function afterRun($result)
    {
        self::$decryptList = [];

        return parent::afterRun($result);
    }
}
