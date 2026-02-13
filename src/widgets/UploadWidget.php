<?php
// src/widgets/UploadWidget.php

namespace garmayev\fileupload\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use garmayev\fileupload\assets\UploaderAsset;
use garmayev\fileupload\assets\CropperAsset;
use yii\web\View;

class UploadWidget extends Widget
{
    public $model;
    public $attribute;
    public $options = [];

    public $ajaxUrl;
    public $deleteUrl;

    public $previewSize = 'medium';
    public $showPreview = true;
    public $enableCropper = true;
    public $cropOptions = [];

    public $accept = 'image/*';
    public $multiple = false;

    public $widgetId;
    public $maxFileSize = 5; // MB
    public $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    public $showErrorSummary = true;
    public $errorContainerId;

    public function init()
    {
        parent::init();

        if ($this->widgetId === null) {
            $this->widgetId = $this->getId();
        }

        if ($this->errorContainerId === null) {
            $this->errorContainerId = $this->widgetId . '-error';
        }

        // Правильные URL для экшенов
        if ($this->ajaxUrl === null) {
            $this->ajaxUrl = Url::to(['/fileupload/default/upload']);
        }

        if ($this->deleteUrl === null) {
            $this->deleteUrl = Url::to(['/fileupload/default/delete']);
        }

        // Применяем опции из $options
        if (isset($this->options['maxFileSize'])) {
            $this->maxFileSize = $this->options['maxFileSize'];
        }

        if (isset($this->options['allowedExtensions'])) {
            $this->allowedExtensions = $this->options['allowedExtensions'];
        }

        if (isset($this->options['enableCropper'])) {
            $this->enableCropper = (bool)$this->options['enableCropper'];
        }

        if (isset($this->options['cropOptions'])) {
            $this->cropOptions = $this->options['cropOptions'];
        }

        if (isset($this->options['showErrorSummary'])) {
            $this->showErrorSummary = (bool)$this->options['showErrorSummary'];
        }

        if ($this->enableCropper) {
            \garmayev\fileupload\assets\CropperAsset::register($this->view);

            $cropOptions = Json::encode(array_merge([
                'aspectRatio' => 1, // 1:1 для аватара
                'viewMode' => 2,
                'autoCropArea' => 0.8,
                'background' => false,
                'movable' => true,
                'rotatable' => true,
                'scalable' => true,
                'zoomable' => true,
                'zoomOnTouch' => true,
                'zoomOnWheel' => true,
            ], $this->cropOptions));

            $this->view->registerJs("
            if (typeof window.cropperOptions === 'undefined') {
                window.cropperOptions = {};
            }
            window.cropperOptions['{$this->widgetId}'] = {$cropOptions};
            console.log('Cropper options registered for widget:', '{$this->widgetId}');
            ", View::POS_BEGIN);
        }

        UploaderAsset::register($this->view);
    }

    public function run()
    {
        if ($this->model === null || $this->attribute === null) {
            throw new \InvalidArgumentException('Model and attribute must be specified.');
        }

        $inputId = Html::getInputId($this->model, $this->attribute);
        $value = $this->model->{$this->attribute};

        if (is_array($value)) {
            $value = reset($value);
        }

        // Используем переданный ajaxUrl, а не создаем новый
        $uploadUrl = $this->ajaxUrl;
        $deleteUrl = $this->deleteUrl ?: Url::to(['/fileupload/default/delete']);

        $bundle = UploaderAsset::register($this->view);
        // Добавьте отладку
        Yii::info('UploaderAsset registered. JS files: ' . json_encode($bundle->js), __METHOD__);

        if ($this->enableCropper) {
            $cropperBundle = CropperAsset::register($this->view);
            Yii::info('CropperAsset registered. JS files: ' . json_encode($cropperBundle->js), __METHOD__);
        }

        return $this->render('widget', [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'value' => (string)$value,
            'options' => $this->options,
            'widgetId' => $this->widgetId,
            'errorContainerId' => $this->errorContainerId,
            'inputId' => $inputId,
            'ajaxUrl' => $this->ajaxUrl,
            'previewSize' => $this->previewSize,
            'showPreview' => $this->showPreview,
            'enableCropper' => $this->enableCropper,
            'cropOptions' => $this->cropOptions,
            'accept' => $this->accept,
            'multiple' => $this->multiple,
            'maxFileSize' => $this->maxFileSize,
            'allowedExtensions' => $this->allowedExtensions,
            'showErrorSummary' => $this->showErrorSummary,
            'uploadUrl' => $uploadUrl, // Передаем в представление
            'cropUrl' => $this->ajaxUrl, // Для crop используем тот же базовый URL
            'deleteUrl' => $deleteUrl,
        ]);
    }
}