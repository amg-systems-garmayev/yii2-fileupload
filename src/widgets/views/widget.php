<?php
// src/widgets/views/widget.php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

/**
 * @var \yii\base\Model|\yii\db\ActiveRecord $model
 * @var $attribute string
 * @var $value
 * @var $options
 * @var $widgetId
 * @var $errorContainerId
 * @var $inputId string
 * @var $ajaxUrl
 * @var $previewSize
 * @var $showPreview
 * @var $enableCropper
 * @var $cropOptions
 * @var $accept
 * @var $multiple
 * @var $maxFileSize
 * @var $allowedExtensions
 * @var $showErrorSummary
 * @var $uploadUrl
 * @var $cropUrl
 * @var $deleteUrl
 */

$hiddenInput = Html::activeHiddenInput($model, $attribute, [
    'id' => $inputId,
    'class' => 'file-upload-input',
]);

$previewHtml = '';
if ($showPreview && $value && !empty($value)) {
    $previewUrl = Url::to(['/fileupload/default/preview', 'filename' => $value, 'size' => $previewSize]);
    $previewHtml = Html::img($previewUrl, [
        'class' => 'img-thumbnail upload-preview',
        'style' => 'max-width: 200px; max-height: 200px; object-fit: contain;'
    ]);
}

$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;

// Используем переданные URL из виджета
$uploadUrl = isset($uploadUrl) ? $uploadUrl : Url::to(['/fileupload/default/upload']);
$cropUrl = isset($cropUrl) ? $cropUrl : Url::to(['/fileupload/default/crop']);
$deleteUrl = isset($deleteUrl) ? $deleteUrl : Url::to(['/fileupload/default/delete']);
$previewBaseUrl = Url::to(['/fileupload/default/preview']);

$widgetConfig = [
    'uploadUrl' => $uploadUrl,  // Теперь используем переданный URL
    'cropUrl' => $cropUrl,
    'deleteUrl' => $deleteUrl,
    'previewUrl' => $previewBaseUrl,
    'maxFileSize' => $maxFileSize * 1024 * 1024,
    'allowedExtensions' => $allowedExtensions,
    'enableCropper' => $enableCropper,
    'cropOptions' => $cropOptions,
    'csrfToken' => $csrfToken,
    'csrfParam' => $csrfParam,
    'widgetId' => $widgetId,
    'inputId' => $inputId,
    'errorContainerId' => $errorContainerId,
    'previewSize' => $previewSize,
];

$this->registerJs("
    jQuery(function($) {
        $('#$widgetId').uploader(" . Json::encode($widgetConfig) . ");
    });
");

// Добавьте этот код сразу после открывающего тега div виджета
$csrfToken = Json::encode(Yii::$app->request->csrfToken);
$csrfParam = Json::encode(Yii::$app->request->csrfParam);
?>

    <div id="<?= Html::encode($widgetId) ?>" class="file-upload-widget mb-3">
        <?= $hiddenInput ?>

        <div class="upload-container">
            <?php if ($showPreview): ?>
                <div class="preview-container mb-3">
                    <?= $previewHtml ?>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <input type="file"
                       class="form-control file-upload-control <?= $model->hasErrors($attribute) ? 'is-invalid' : '' ?>"
                    <?= $multiple ? 'multiple' : '' ?>
                       accept="<?= Html::encode($accept) ?>"
                       id="<?= Html::encode($inputId) ?>-file" data-url="<?= $uploadUrl ?>">

                <button class="btn btn-outline-secondary btn-browse" type="button">
                    <i class="bi bi-folder2-open"></i> Выбрать
                </button>
            </div>

            <?php if ($showErrorSummary): ?>
                <div id="<?= Html::encode($errorContainerId) ?>" class="invalid-feedback d-block">
                    <?= Html::error($model, $attribute, ['class' => 'text-danger']) ?>
                </div>
            <?php endif; ?>

            <div class="upload-error alert alert-danger mt-2 d-none"></div>

            <div class="progress mt-2 d-none">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar"
                     style="width: 0%"></div>
            </div>

            <?php if (!empty($value)): ?>
                <div class="mt-2">
                    <button type="button"
                            class="btn btn-danger btn-sm btn-delete"
                            data-filename="<?= Html::encode($value) ?>">
                        <i class="bi bi-trash"></i> Удалить
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php if ($enableCropper): ?>
    <div class="modal fade" id="cropperModal-<?= Html::encode($widgetId) ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Обрезка изображения</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="cropper-container" style="max-height: 60vh;">
                        <img id="cropperImage-<?= Html::encode($widgetId) ?>" class="img-fluid" alt="Image to crop">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="button" class="btn btn-primary btn-crop"
                            data-widget-id="<?= Html::encode($widgetId) ?>">
                        Обрезать
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>