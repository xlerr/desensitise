#  脱敏部件

### 配置

```php
return [
    'components' => [
        Desensitise::componentName() => [
            'class' => Desensitise::class,
            'baseUri' => 'http://localhost/',
        ],
    ],
];
```

### 使用

```php
$hash = Desensitise::instance()->execDecrypt('enc_01_1293051090349_123', true);
```

```php
Desensitise::instance()->encrypt([
    ['13123456789', Desensitise::TYPE_PHONE_NUMBER],
    ['510121199901011234', Desensitise::TYPE_IDENTITY_NUMBER],
], 0, function ($response) {
    throw new EncryptException($response['message']);
});
```