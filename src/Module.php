<?php
// src/Module.php

namespace garmayev\fileupload;

use Yii;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'garmayev\fileupload\controllers';

    public $uploadPath = '@webroot/uploads';
    public $uploadUrl = '@web/uploads';

    public $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    public $maxFileSize = 5242880; // 5MB

    public $thumbSizes = [
        'thumb' => [100, 100],
        'medium' => [300, 300],
        'large' => [800, 600],
    ];

    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        Yii::$app->i18n->translations['fileupload*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@garmayev/fileupload/messages',
            'fileMap' => [
                'fileupload' => 'fileupload.php',
            ],
        ];
    }
}