<?php
// fileupload/FileUpload.php

namespace garmayev\fileupload;

use Yii;
use yii\base\Module;

class FileUpload extends Module
{
    public $controllerNamespace = 'vendor\fileupload\controllers';

    public $uploadPath = '@webroot/uploads';
    public $uploadUrl = '@web/uploads';

    public $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    public $maxFileSize = 5242880; // 5MB

    public $imageSizes = [
        'thumb' => [100, 100],
        'medium' => [300, 300],
        'large' => [800, 600],
    ];

    public $enableCropper = true;
    public $cropAspectRatio = null; // null для свободного соотношения

    public function init()
    {
        parent::init();

        if (Yii::$app->has('request') && Yii::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'vendor\fileupload\commands';
        }
    }
}