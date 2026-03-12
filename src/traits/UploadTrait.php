<?php
// fileupload/traits/UploadTrait.php

namespace garmayev\fileupload\traits;

use Yii;
use yii\web\UploadedFile;

trait UploadTrait
{
    /**
     * @var UploadedFile
     */
    public $uploadedFile;

    /**
     * Загрузка файла
     */
    public function upload($attribute)
    {
        $file = UploadedFile::getInstance($this, $attribute);

        if ($file) {
            $filename = $this->generateFilename($file);
            $path = $this->getUploadPath() . '/' . $filename;

            if ($file->saveAs($path)) {
                $this->$attribute = $filename;
                return true;
            }
        }

        return false;
    }

    /**
     * Получение URL файла
     */
    public function getFileUrl($attribute)
    {
        if ($this->$attribute) {
            return Yii::getAlias($this->uploadUrl) . '/' . $this->$attribute;
        }

        return null;
    }

    /**
     * Удаление файла
     */
    public function deleteFile($attribute)
    {
        if ($this->$attribute) {
            $path = $this->getUploadPath() . '/' . $this->$attribute;
            if (file_exists($path)) {
                return unlink($path);
            }
        }

        return false;
    }

    /**
     * Генерация имени файла
     */
    protected function generateFilename($file)
    {
        return uniqid() . '.' . strtolower($file->extension);
    }

    /**
     * Путь для загрузки
     */
    protected function getUploadPath()
    {
        return Yii::getAlias('@webroot/uploads');
    }

    /**
     * URL для загрузки
     */
    protected function getUploadUrl()
    {
        return '@web/uploads';
    }
}