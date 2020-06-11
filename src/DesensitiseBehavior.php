<?php

namespace xlerr\desensitise;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\base\UserException;
use yii\db\ActiveRecord;

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

    /**
     * @param ModelEvent $event
     *
     * @throws EncryptException
     * @throws InvalidConfigException
     */
    public function encrypt(ModelEvent $event)
    {
        /** @var ActiveRecord $sender */
        $sender = $event->sender;

        $desensitiseMapping = array_intersect_key($sender->getDirtyAttributes(), $this->config);

        $desensitiseAttributes = [];
        $encryptData           = [];

        foreach ($desensitiseMapping as $attribute => $value) {
            $value = trim((string)$value);
            if (strlen($value) === 0) {
                continue;
            }

            $desensitiseAttributes[] = $attribute;

            $encryptData[] = [$value, $this->config[$attribute]];
        }

        if (!empty($encryptData)) {
            $result = Desensitise::instance()->encrypt($encryptData, 0, function ($response) use ($event) {
                $event->isValid = false;
                throw new EncryptException('脱敏失败: ' . $response['message']);
            });
            foreach ($desensitiseAttributes as $i => $attribute) {
                $sender->setAttribute($attribute, $result[$i]['hash']);
            }
        }
    }

    /**
     * @param bool $plain
     *
     * @throws UserException
     * @throws InvalidConfigException
     */
    public function decrypt($plain = true)
    {
        /** @var ActiveRecord $sender */
        $sender     = $this->owner;
        $attributes = $sender->getAttributes(array_keys($this->config));
        $attributes = array_filter($attributes);

        if (!empty($attributes)) {
            $result = Desensitise::instance()->decrypt(array_values($attributes), $plain, function ($response) {
                throw new EncryptException($response['message']);
            });

            foreach ($attributes as $field => $hash) {
                $sender->setAttribute($field, $result[$hash]);
            }
        }
    }
}
