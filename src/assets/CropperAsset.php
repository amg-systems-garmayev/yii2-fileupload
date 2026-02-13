<?php
// src/assets/CropperAsset.php

namespace garmayev\fileupload\assets;

use yii\web\AssetBundle;

class CropperAsset extends AssetBundle
{
    public $sourcePath = '@garmayev/fileupload/assets';

    public $css = [
        'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css',
    ];

    public $js = [
        'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js',
        'js/cropper.js',
    ];

    public $depends = [
        'garmayev\fileupload\assets\UploaderAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
        'yii\web\JqueryAsset',
    ];
}