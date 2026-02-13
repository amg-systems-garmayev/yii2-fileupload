<?php
// src/actions/CropAction.php

namespace garmayev\fileupload\actions;

use Yii;
use yii\web\UploadedFile;
use yii\imagine\Image;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Palette\PaletteInterface;

class CropAction extends BaseAction
{
    public $createThumbnails = true;
    public $thumbSizes = [
        'thumb' => [100, 100],
        'medium' => [300, 300],
    ];
    public $defaultBackground = '#ffffff';
    public $quality = 92;

    public function run()
    {
        $file = UploadedFile::getInstanceByName('file');
        $cropData = json_decode(Yii::$app->request->post('cropData'), true);
        $originalFilename = Yii::$app->request->post('originalFilename');

        if (!$file || !$cropData) {
            return ['success' => false, 'message' => 'Данные для обрезки не получены'];
        }

        $extension = strtolower($file->extension);
        $filename = $this->generateFilename($file, 'cropped_');
        $path = $this->getUploadPath() . '/' . $filename;

        // Сохраняем временный файл
        $tempPath = $path . '.tmp';
        if (!$file->saveAs($tempPath)) {
            return ['success' => false, 'message' => 'Ошибка при сохранении файла'];
        }

        try {
            // Открываем изображение
            $imagine = Image::getImagine();
            $image = $imagine->open($tempPath);

            // Применяем трансформации
            if (isset($cropData['rotate']) && $cropData['rotate'] != 0) {
                $image = $this->rotateImage($image, $cropData['rotate'], $extension);
            }

            if (isset($cropData['scaleX']) && $cropData['scaleX'] == -1) {
                $image->flipHorizontally();
            }

            if (isset($cropData['scaleY']) && $cropData['scaleY'] == -1) {
                $image->flipVertically();
            }

            // Обрезка
            if (isset($cropData['x'], $cropData['y'], $cropData['width'], $cropData['height'])) {
                $x = max(0, (int) $cropData['x']);
                $y = max(0, (int) $cropData['y']);
                $width = max(1, (int) $cropData['width']);
                $height = max(1, (int) $cropData['height']);

                // Проверяем, не выходят ли координаты за пределы изображения
                $imageSize = $image->getSize();
                $x = min($x, $imageSize->getWidth() - 1);
                $y = min($y, $imageSize->getHeight() - 1);
                $width = min($width, $imageSize->getWidth() - $x);
                $height = min($height, $imageSize->getHeight() - $y);

                $image->crop(
                    new Point($x, $y),
                    new Box($width, $height)
                );
            }

            // Сохраняем с правильными параметрами
            $saveOptions = $this->getSaveOptions($extension);
            $image->save($path, $saveOptions);

            // Удаляем временный файл
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Создаем превью
            if ($this->createThumbnails) {
                $this->createThumbnails($path, $filename);
            }

            // Удаляем оригинал если нужно
            if ($originalFilename && $originalFilename != $filename) {
                $originalPath = $this->getUploadPath() . '/' . $originalFilename;
                if (file_exists($originalPath)) {
                    unlink($originalPath);
                    $this->deleteThumbnails($originalFilename);
                }
            }

            return [
                'success' => true,
                'filename' => $filename,
                'filePath' => '/' . trim(str_replace(Yii::getAlias('@webroot'), '', $path), '\\/'),
                'fileUrl' => Yii::getAlias($this->uploadUrl) . '/' . $filename . '?t=' . time()
            ];

        } catch (\Exception $e) {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            Yii::error('Crop error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Ошибка при обрезке изображения: ' . $e->getMessage()];
        }
    }

    /**
     * Поворот изображения
     */
    protected function rotateImage($image, $angle, $extension)
    {
        if ($extension === 'png') {
            // Для PNG создаем новое изображение с прозрачным фоном
            $size = $image->getSize();
            $palette = new RGB();

            // Создаем изображение с прозрачным фоном
            $newImage = Image::getImagine()->create($size, $palette->color('#fff', 0));

            // Вставляем оригинальное изображение
            $newImage->paste($image, new Point(0, 0));

            // Поворачиваем
            $newImage->rotate($angle);

            return $newImage;
        } else {
            // Для JPEG и других форматов - поворот с белым фоном
            $image->rotate($angle);
            return $image;
        }
    }

    protected function getSaveOptions($extension)
    {
        $options = [];
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $options = ['jpeg_quality' => $this->quality];
                break;
            case 'png':
                // Важно: для PNG сохраняем прозрачность
                $options = [
                    'png_compression_level' => 9,
                    'flatten' => false, // Не схлопывать прозрачность
                ];
                break;
            case 'gif':
                $options = ['animated' => false];
                break;
            case 'webp':
                $options = ['webp_quality' => $this->quality];
                break;
        }
        return $options;
    }

    protected function createThumbnails($originalPath, $filename)
    {
        try {
            $pathInfo = pathinfo($originalPath);
            $extension = strtolower($pathInfo['extension']);

            foreach ($this->thumbSizes as $sizeName => $size) {
                $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename']
                    . '_' . $sizeName . '.' . $extension;

                if (!file_exists($thumbPath)) {
                    if ($extension === 'png') {
                        // Для PNG создаем миниатюру с прозрачностью
                        $imagine = Image::getImagine();
                        $image = $imagine->open($originalPath);
                        $image->thumbnail(new Box($size[0], $size[1]))
                            ->save($thumbPath, ['png_compression_level' => 9, 'flatten' => false]);
                    } else {
                        Image::thumbnail($originalPath, $size[0], $size[1])
                            ->save($thumbPath, $this->getSaveOptions($extension));
                    }
                }
            }
        } catch (\Exception $e) {
            Yii::error('Error creating thumbnails: ' . $e->getMessage());
        }
    }

    protected function deleteThumbnails($filename)
    {
        $path = $this->getUploadPath() . '/' . $filename;
        if (!file_exists($path)) {
            return;
        }

        $pathInfo = pathinfo($path);
        foreach ($this->thumbSizes as $sizeName => $size) {
            $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename']
                . '_' . $sizeName . '.' . $pathInfo['extension'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
    }
}