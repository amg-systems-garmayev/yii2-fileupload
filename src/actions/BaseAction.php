<?php
// src/actions/BaseAction.php

namespace garmayev\fileupload\actions;

use Yii;
use yii\base\Action;
use yii\web\Response;

abstract class BaseAction extends Action
{
    public $uploadPath = '@webroot/uploads';
    public $uploadUrl = '@web/uploads';

    public $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    public $maxFileSize = 5242880;

    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Разрешаем CORS для AJAX запросов
        $headers = Yii::$app->response->headers;
        $headers->set('Access-Control-Allow-Origin', '*');
        $headers->set('Access-Control-Allow-Methods', 'POST, GET, DELETE, OPTIONS');
        $headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, ' . Yii::$app->request->csrfParam);
    }

    public function beforeRun()
    {
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->setStatusCode(200);
            return false;
        }
        return parent::beforeRun();
    }

    protected function isImage($extension)
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
    }

    protected function generateFilename($file, $prefix = '')
    {
        $name = uniqid($prefix, true);
        return $name . '.' . strtolower($file->extension);
    }

    protected function getUploadPath()
    {
        $path = Yii::getAlias($this->uploadPath);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }
}