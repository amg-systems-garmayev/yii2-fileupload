<?php
// src/actions/UploadAction.php

namespace garmayev\fileupload\actions;

use Yii;
use yii\web\UploadedFile;
use yii\imagine\Image;

class UploadAction extends BaseAction
{
    public $createThumbnails = true;
    public $thumbSizes = [
        'thumb' => [100, 100],
        'medium' => [300, 300],
    ];

    // Добавьте callback для обработки ответа
    public $afterUpload = null;

    public function run()
    {
        $file = UploadedFile::getInstanceByName('file');

        if (!$file) {
            return ['success' => false, 'message' => 'Файл не получен'];
        }

        // Валидация размера
        if ($file->size > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'Файл слишком большой. Максимальный размер: ' .
                    Yii::$app->formatter->asSize($this->maxFileSize)
            ];
        }

        // Валидация расширения
        $extension = strtolower($file->extension);
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Недопустимый формат файла. Разрешены: ' . implode(', ', $this->allowedExtensions)
            ];
        }

        // Генерация уникального имени
        $filename = $this->generateFilename($file);
        $path = $this->getUploadPath() . '/' . $filename;

        // Сохранение файла
        if ($file->saveAs($path)) {
            // Создание превью для изображений
            if ($this->isImage($extension) && $this->createThumbnails) {
                $this->createThumbnails($path, $filename);
            }

            // Базовый ответ
            $response = [
                'success' => true,
                'filename' => $filename,
                'fileUrl' => Yii::getAlias($this->uploadUrl) . '/' . $filename,
                'filePath' => '/' . trim(str_replace(Yii::getAlias('@webroot'), '', $path), '\\/'), // Относительный путь
                'isImage' => $this->isImage($extension)
            ];

            // Применяем callback если он задан
            if (is_callable($this->afterUpload)) {
                $callbackResponse = call_user_func($this->afterUpload, $filename, $response);
                if (is_array($callbackResponse)) {
                    $response = array_merge($response, $callbackResponse);
                }
            }

            return $response;
        }

        return ['success' => false, 'message' => 'Ошибка при сохранении файла'];
    }

    protected function createThumbnails($originalPath, $filename)
    {
        try {
            $pathInfo = pathinfo($originalPath);

            foreach ($this->thumbSizes as $sizeName => $size) {
                $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename']
                    . '_' . $sizeName . '.' . $pathInfo['extension'];

                if (!file_exists($thumbPath)) {
                    Image::thumbnail($originalPath, $size[0], $size[1])
                        ->save($thumbPath, ['quality' => 90]);
                }
            }
        } catch (\Exception $e) {
            Yii::error('Error creating thumbnails: ' . $e->getMessage());
        }
    }
}