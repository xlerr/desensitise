<?php

namespace xlerr\desensitise;

use Yii;
use yii\base\Widget;

class DesensitiseWidget extends Widget
{
    public static $decryptList = [];

    /**
     * @param string $hash
     * @param bool   $plain
     *
     * @return string
     */
    public static function decrypt($hash, bool $plain = false)
    {
        if (empty($hash)) {
            return $hash;
        }

        self::$decryptList[$plain][] = $hash;

        return sprintf('<span class="desensitization" data-dck="%s" data-plain="%d">%s</span>', $hash, $plain, $hash);
    }

    public function run()
    {
        $result = [[], []];
        if (!empty(self::$decryptList)) {
            foreach (self::$decryptList as $plain => $data) {
                $result[$plain] += Desensitise::instance()->decrypt($data, $plain, function ($response) {
                    Yii::$app->getSession()->addFlash('warning', '<b>[脱敏]</b> ' . $response['message']);
                });
            }
        }
        $result = json_encode($result, JSON_UNESCAPED_UNICODE);

        $js = <<<EOF
(function ($) {
    const dcs = $result;
    $('span.desensitization').each(function () {
        const self = $(this);
        let dck = self.data('dck'),
            plain = self.data('plain');
        if (undefined !== dcs[plain] && undefined !== dcs[plain][dck]) {
            self.text(dcs[plain][dck]);
        }
    });
})(jQuery);
EOF;
        $this->getView()->registerJs($js);
    }

    public function afterRun($result)
    {
        self::$decryptList = [];

        return parent::afterRun($result);
    }
}
