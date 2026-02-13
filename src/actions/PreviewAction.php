<?php
// src/actions/PreviewAction.php

namespace garmayev\fileupload\actions;

use Yii;
use yii\base\Action;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class PreviewAction extends Action
{
    public $uploadPath = '@webroot/uploads';
    public $thumbSizes = [
        'thumb' => [100, 100],
        'medium' => [300, 300],
        'large' => [800, 600],
    ];

    public function run($filename, $size = 'medium')
    {
        // Защита от path traversal
        $filename = basename($filename);
        $uploadPath = Yii::getAlias($this->uploadPath);

        // Проверяем существует ли директория
        if (!is_dir($uploadPath)) {
            throw new NotFoundHttpException('Upload directory not found');
        }

        $pathInfo = pathinfo($filename);

        // Сначала ищем thumbnail
        $thumbPath = $uploadPath . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];

        if (file_exists($thumbPath) && is_file($thumbPath)) {
            $path = $thumbPath;
        } else {
            // Если thumbnail не найден, ищем оригинал
            $path = $uploadPath . '/' . $filename;
            if (!file_exists($path) || !is_file($path)) {
                throw new NotFoundHttpException('File not found');
            }
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', mime_content_type($path));
        $response->headers->set('Content-Length', filesize($path));
        $response->headers->set('Cache-Control', 'public, max-age=86400');
        $response->headers->set('Pragma', 'cache');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

        return file_get_contents($path);
    }
}