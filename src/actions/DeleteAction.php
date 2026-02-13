<?php
// src/actions/DeleteAction.php

namespace garmayev\fileupload\actions;

use Yii;

class DeleteAction extends BaseAction
{
    public $thumbSizes = [
        'thumb' => [100, 100],
        'medium' => [300, 300],
    ];

    public function run()
    {
        $filename = Yii::$app->request->post('filename');

        if (!$filename) {
            return ['success' => false, 'message' => 'Имя файла не указано'];
        }

        // Защита от path traversal
        $filename = basename($filename);
        $path = $this->getUploadPath() . '/' . $filename;

        if (file_exists($path) && is_file($path)) {
            if (unlink($path)) {
                // Удаляем превью
                $this->deleteThumbnails($filename);
                return ['success' => true];
            }
        }

        return ['success' => false, 'message' => 'Ошибка при удалении файла'];
    }

    protected function deleteThumbnails($filename)
    {
        $path = $this->getUploadPath() . '/' . $filename;
        $pathInfo = pathinfo($path);

        foreach ($this->thumbSizes as $sizeName => $size) {
            $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename']
                . '_' . $sizeName . '.' . $pathInfo['extension'];

            if (file_exists($thumbPath) && is_file($thumbPath)) {
                unlink($thumbPath);
            }
        }
    }
}