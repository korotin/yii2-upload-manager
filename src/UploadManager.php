<?php
/**
 * Upload Manager
 *
 * This file contains upload manager.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 */

namespace herroffizier\yii2um;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;
use yii\base\UploadedFile;

class UploadManager extends Component
{
    /**
     * Path to upload folder.
     *
     * @var string
     */
    public $uploadDir = '@webroot/upload';

    /**
     * URL to upload folder.
     *
     * @var string
     */
    public $uploadUrl = '@web/upload';

    protected function getSubfolder($name)
    {
        return substr(md5(mb_substr($name, 0, 2)), 0, 2);
    }

    protected function getPartitionedPath($path, $name)
    {
        $subfolder = $this->getSubfolder($name);
        $path = FileHelper::normalizePath($path).'/'.$subfolder;

        return $path;
    }

    /**
     * Get relative URL for relative path.
     *
     * @param  string $path
     * @return string
     */
    public function getUrl($path)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/'.$path;
        }

        $path = FileHelper::normalizePath($path);
        $path = Yii::getAlias($this->uploadUrl.$path);

        return $path;
    }

    /**
     * Get absolute path for relative path.
     *
     * @param  string $path
     * @return string
     */
    public function getAbsolutePath($path)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/'.$path;
        }

        $path = FileHelper::normalizePath($path);
        $absolutePath = Yii::getAlias($this->uploadDir.$path);

        return $absolutePath;
    }

    /**
     * Create given folder tree in upload folder.
     *
     * Returns absolute path of given path.
     *
     * @param  string $path
     * @return string
     */
    public function createPath($path)
    {
        $absolutePath = $this->getAbsolutePath($path);
        if (!file_exists($absolutePath)) {
            FileHelper::createDirectory($absolutePath);
        }

        return $absolutePath;
    }

    /**
     * Create folder tree appended with partition folder in upload folder.
     *
     * Partition folder name depends on given file name.
     *
     * @param  string $path
     * @param  string $name
     * @return string
     */
    public function createPartitionedPath($path, $name)
    {
        $path = $this->getPartitionedPath($path, $name);

        return $this->createPath($path);
    }

    /**
     * Whether file with given relative path exists.
     *
     * @param  string  $fileName
     * @return boolean
     */
    public function exists($fileName)
    {
        $absoluteFileName = $this->getAbsolutePath($fileName);

        return file_exists($absoluteFileName);
    }

    /**
     * Save $content to $path/$name in upload folder.
     *
     * Returns relative path with partition folder.
     *
     * @param  string  $path
     * @param  string  $name
     * @param  mixed   $content
     * @param  boolean $overwrite
     * @return string
     */
    public function save($path, $name, $content, $overwrite = false)
    {
        $path = $this->getPartitionedPath($path, $name);
        $absolutePath = $this->createPath($path, $name);
        $absoluteFileName = $absolutePath.'/'.$name;

        if (file_exists($absoluteFileName) && !$overwrite) {
            return $path.'/'.$name;
        }

        if ($content instanceof UploadedFile) {
            $content->saveAs($absoluteFileName);
        } else {
            file_put_contents($absoluteFileName, (string) $content);
            unset($content);
        }

        return $path.'/'.$name;
    }

    /**
     * Move file with given relative name to $path/$name in upload folder.
     *
     * Returns relative path with partition folder.
     *
     * @param  string  $fileName
     * @param  string  $path
     * @param  string  $name
     * @param  boolean $overwrite
     * @return string
     */
    public function move($fileName, $path, $name = null, $overwrite = false)
    {
        if (!$name) {
            $name = pathinfo($fileName, PATHINFO_BASENAME);
        }

        $path = $this->getPartitionedPath($path, $name);
        $absolutePath = $this->createPath($path, $name);
        $absoluteFileName = $absolutePath.'/'.$name;

        if (file_exists($absoluteFileName) && !$overwrite) {
            return null;
        }

        rename($fileName, $absoluteFileName);

        return $path.'/'.$name;
    }
}
