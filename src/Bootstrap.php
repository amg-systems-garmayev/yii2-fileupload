<?php
// src/Bootstrap.php

namespace garmayev\fileupload;

use Yii;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        // Регистрация переводов
        if (!isset($app->get('i18n')->translations['fileupload*'])) {
            $app->get('i18n')->translations['fileupload*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en-US',
            ];
        }
    }
}