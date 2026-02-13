<?php
// src/controllers/DefaultController.php

namespace garmayev\fileupload\controllers;

use Yii;
use yii\web\Controller;
use garmayev\fileupload\actions\UploadAction;
use garmayev\fileupload\actions\CropAction;
use garmayev\fileupload\actions\DeleteAction;
use garmayev\fileupload\actions\PreviewAction;

class DefaultController extends Controller
{
    public $enableCsrfValidation = false; // Временно отключаем для теста

    public function actions()
    {
        $module = $this->module;

        return [
            'upload' => [
                'class' => UploadAction::class,
                'uploadPath' => $module->uploadPath ?? '@webroot/uploads',
                'uploadUrl' => $module->uploadUrl ?? '@web/uploads',
                'allowedExtensions' => $module->allowedExtensions ?? ['jpg', 'jpeg', 'png', 'gif'],
                'maxFileSize' => $module->maxFileSize ?? 5242880,
                'thumbSizes' => $module->thumbSizes ?? [
                        'thumb' => [100, 100],
                        'medium' => [300, 300],
                    ],
            ],
            'crop' => [
                'class' => CropAction::class,
                'uploadPath' => $module->uploadPath ?? '@webroot/uploads',
                'uploadUrl' => $module->uploadUrl ?? '@web/uploads',
                'allowedExtensions' => $module->allowedExtensions ?? ['jpg', 'jpeg', 'png', 'gif'],
                'maxFileSize' => $module->maxFileSize ?? 5242880,
                'thumbSizes' => $module->thumbSizes ?? [
                        'thumb' => [100, 100],
                        'medium' => [300, 300],
                    ],
            ],
            'delete' => [
                'class' => DeleteAction::class,
                'uploadPath' => $module->uploadPath ?? '@webroot/uploads',
                'thumbSizes' => $module->thumbSizes ?? [
                        'thumb' => [100, 100],
                        'medium' => [300, 300],
                    ],
            ],
            'preview' => [
                'class' => PreviewAction::class,
                'uploadPath' => $module->uploadPath ?? '@webroot/uploads',
                'thumbSizes' => $module->thumbSizes ?? [
                        'thumb' => [100, 100],
                        'medium' => [300, 300],
                    ],
            ],
        ];
    }
}