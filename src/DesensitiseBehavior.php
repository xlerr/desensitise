<?php

namespace xlerr\desensitise;

use Yii;
use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\base\UserException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class DesensitiseBehavior extends Behavior
{
    /**
     * @var array [['attribute' => 'type']]
     */
    public $config = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => [$this, 'encrypt'],
            ActiveRecord::EVENT_BEFORE_UPDATE => [$this, 'encrypt'],
        ];
    }

    public function encrypt(ModelEvent $event)
    {
        /** @var ActiveRecord $sender */
        $sender = $event->sender;

        $desensitiseMapping = $sender->getAttributes(array_keys($this->config));

        $desensitiseAttributes = [];
        $encryptData = [];

        foreach ($desensitiseMapping as $attribute => $value) {
            $value = trim((string)$value);
            if (strlen($value) === 0) {
                continue;
            }

            $desensitiseAttributes[] = $attribute;

            $encryptData[] = [$value, $this->config[$attribute]];
        }

        /** @var Desensitise $desensitise */
        $desensitise = Yii::$app->get('desensitise');
        if ($result = $desensitise->encrypt($encryptData)) {
            foreach ($desensitiseAttributes as $i => $attribute) {
                $sender->setAttribute($attribute, ArrayHelper::getValue($result, [$i, 'hash']));
            }
        } else {
            $event->isValid = false;
            throw new EncryptException('脱敏失败: ' . $desensitise->getError());
        }
    }

    /**
     * @param bool $plain
     *
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public function decrypt($plain = true)
    {
        /** @var ActiveRecord $sender */
        $sender = $this->owner;
        $attributes = $sender->getAttributes(array_keys($this->config));
        $attributes = array_filter($attributes);

        /** @var Desensitise $desensitise */
        $desensitise = Yii::$app->get('desensitise');
        $result = $desensitise->decrypt(array_values($attributes), $plain);
        if (!$result) {
            throw new UserException($desensitise->getError());
        }

        foreach ($attributes as $field => $cipherText) {
            $sender->setAttribute($field, ArrayHelper::getValue($result, $cipherText));
        }
    }
}
