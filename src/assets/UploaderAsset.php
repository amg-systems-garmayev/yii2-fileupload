<?php
// src/assets/UploaderAsset.php

namespace garmayev\fileupload\assets;

use yii\web\AssetBundle;

class UploaderAsset extends AssetBundle
{
    public $sourcePath = '@garmayev/fileupload/assets';

    public $css = [
        'css/uploader.css',
    ];

    public $js = [
        'js/uploader.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}