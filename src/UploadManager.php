<?php
/**
 * Upload Manager.
 *
 * This file contains upload manager.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 */

namespace herroffizier\yii2um;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

class UploadManager extends Component
{
    /**
     * Throw exception when trying to overwrite existing file.
     */
    const STRATEGY_KEEP = 0;

    /**
     * Overwrite existing file silently.
     */
    const STRATEGY_OVERWRITE = 1;

    /**
     * Rename new file if file with same name exists.
     */
    const STRATEGY_RENAME = 2;

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

    /**
     * Generate partition name based on file name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPartitionName($name)
    {
        return substr(md5(mb_substr($name, 0, 2)), 0, 2);
    }

    /**
     * Add partition folder to given path.
     *
     * @param string $path
     * @param string $name
     *
     * @return string
     */
    protected function getPartitionedPath($path, $name)
    {
        $subfolder = $this->getPartitionName($name);
        $path = FileHelper::normalizePath($path).'/'.$subfolder;

        return $path;
    }

    /**
     * Get prefixed path.
     *
     * @param string $path
     * @param string $prefix
     *
     * @return string
     */
    protected function getPrefixedPath($path, $prefix)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/'.$path;
        }
        $path = FileHelper::normalizePath($path);
        $prefixedPath = Yii::getAlias($prefix.$path);

        return $prefixedPath;
    }

    /**
     * Add index to file name.
     *
     * @param string $name
     * @param int    $index
     *
     * @return string
     */
    protected function addIndexToName($name, $index)
    {
        $pathinfo = pathinfo($name);

        if (empty($pathinfo['extension'])) {
            return $pathinfo['basename'].'-'.$index;
        } else {
            return $pathinfo['filename'].'-'.$index.'.'.$pathinfo['extension'];
        }
    }

    /**
     * Pick up file name according to overwrite strategy and create path.
     *
     * @throws InvalidParamException when file cannot be created.
     *
     * @param string $path
     * @param string $name
     * @param int    $overwriteStrategy
     *
     * @return string
     */
    protected function createFilePath($path, $name, $overwriteStrategy)
    {
        $partitionedPath = $this->getPartitionedPath($path, $name);
        $absolutePath = $this->getAbsolutePath($partitionedPath);

        if (file_exists($absolutePath.'/'.$name)) {
            switch ($overwriteStrategy) {
                case self::STRATEGY_KEEP:
                    // File overwrtiting is forbidden.
                    throw new InvalidParamException('File '.$name.' already exists in '.$path.'.');

                case self::STRATEGY_RENAME:
                    $index = 0;
                    do {
                        ++$index;
                        $indexedName = $this->addIndexToName($name, $index);

                        $partitionedPath = $this->getPartitionedPath($path, $name);
                        $absolutePath = $this->getAbsolutePath($partitionedPath);
                    } while (file_exists($absolutePath.'/'.$indexedName));
                    $name = $indexedName;
                    break;

                case self::STRATEGY_OVERWRITE:
                    if (is_dir($absolutePath.'/'.$name)) {
                        // Cannot overwrtite folder.
                        throw new InvalidParamException($path.'/'.$name.' is a directory and cannot be overwritten.');
                    }
                    break;
            }
        }

        $this->createPath($partitionedPath);

        return $partitionedPath.'/'.$name;
    }

    /**
     * Get relative URL for relative path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getUrl($path)
    {
        return $this->getPrefixedPath($path, $this->uploadUrl);
    }

    /**
     * Get absolute path for relative path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getAbsolutePath($path)
    {
        return $this->getPrefixedPath($path, $this->uploadDir);
    }

    /**
     * Create given folder tree in upload folder.
     *
     * Returns absolute path of given path.
     *
     * @throws InvalidParamException when file cannot be created.
     *
     * @param string $path
     *
     * @return string
     */
    public function createPath($path)
    {
        $absolutePath = $this->getAbsolutePath($path);
        if (!file_exists($absolutePath)) {
            // FIXME Check return value.
            FileHelper::createDirectory($absolutePath);
        } elseif (!is_dir($absolutePath)) {
            throw new InvalidParamException($path.' is a file, cannot create folder with the same name.');
        }

        return $absolutePath;
    }

    /**
     * Create folder tree appended with partition folder in upload folder.
     *
     * Partition folder name depends on given file name.
     *
     * Returns absolute path of given path.
     *
     * @param string $path
     * @param string $name
     *
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
     * @param string $filePath
     *
     * @return bool
     */
    public function exists($filePath)
    {
        $absoluteFilePath = $this->getAbsolutePath($filePath);

        return file_exists($absoluteFilePath);
    }

    /**
     * Save data stored in $content as file to $path/$name in upload folder.
     *
     * Returns relative path with partition folder.
     *
     * @param string        $path
     * @param string        $name
     * @param string        $content
     * @param int[optional] $overwriteStrategy
     *
     * @return string
     */
    public function saveContent($path, $name, $content, $overwriteStrategy = self::STRATEGY_KEEP)
    {
        $filePath = $this->createFilePath($path, $name, $overwriteStrategy);
        $absoluteFilePath = $this->getAbsolutePath($filePath);

        file_put_contents($absoluteFilePath, $content);
        unset($content);

        return $filePath;
    }

    /**
     * Save $upload file to $path in upload folder.
     *
     * Returns relative path with partition folder.
     *
     * @param string        $path
     * @param UploadedFile  $upload
     * @param int[optional] $overwriteStrategy
     *
     * @return string
     */
    public function saveUpload($path, UploadedFile $upload, $overwriteStrategy = self::STRATEGY_KEEP)
    {
        $filePath = $this->createFilePath($path, $upload->name, $overwriteStrategy);
        $absoluteFilePath = $this->getAbsolutePath($filePath);

        $upload->saveAs($absoluteFilePath);

        return $filePath;
    }

    /**
     * Move or copy file.
     *
     * @param string        $path
     * @param string        $absoluteFilePath
     * @param int[optional] $overwriteStrategy
     * @param string        $function
     *
     * @return string
     */
    protected function saveFileInternal($path, $absoluteFilePath, $overwriteStrategy, $function)
    {
        $filePath = $this->createFilePath($path, pathinfo($absoluteFilePath, PATHINFO_BASENAME), $overwriteStrategy);
        $newAbsoluteFilePath = $this->getAbsolutePath($filePath);

        $function($absoluteFilePath, $newAbsoluteFilePath);

        return $filePath;
    }

    /**
     * Save $absoluteFilePath file to $path in upload folder.
     *
     * Returns relative path with partition folder.
     *
     * @param string        $path
     * @param string        $absoluteFilePath
     * @param int[optional] $overwriteStrategy
     *
     * @return string
     */
    public function saveFile($path, $absoluteFilePath, $overwriteStrategy = self::STRATEGY_KEEP)
    {
        return $this->saveFileInternal($path, $absoluteFilePath, $overwriteStrategy, 'copy');
    }

    /**
     * Move $absoluteFilePath file to $path in upload folder.
     *
     * Returns relative path with partition folder.
     *
     * @param string        $path
     * @param string        $absoluteFilePath
     * @param int[optional] $overwriteStrategy
     *
     * @return string
     */
    public function moveFile($path, $absoluteFilePath, $overwriteStrategy = self::STRATEGY_KEEP)
    {
        return $this->saveFileInternal($path, $absoluteFilePath, $overwriteStrategy, 'rename');
    }
}
