<?php
/**
 * Upload Manager
 *
 * This file contains upload manager test.
 *
 * @author  Martin Stolz <herr.offizier@gmail.com>
 */

namespace herroffizier\yii2um\tests\codeception\unit;

use org\bovigo\vfs\vfsStream;
use Yii;
use Codeception\Specify;
use yii\codeception\TestCase;
use yii\base\InvalidParamException;
use herroffizier\yii2um\UploadManager;

class UploadManagerTest extends TestCase
{
    use Specify;

    protected $fs = null;

    protected function setUp()
    {
        parent::setUp();

        $this->specifyConfig()->deepClone(false);
        $this->fs = vfsStream::setup('fs');
    }

    public function testGetUrl()
    {
        $this->specify('get url for relative path', function () {

            $this->assertEquals(
                '/upload/test/test.file',
                Yii::$app->uploads->getUrl('test/test.file')
            );
        });
    }

    public function testGetAbsolutePath()
    {
        $this->specify('get absolute path for relative path', function () {
            $this->assertEquals(
                vfsStream::url('fs/upload/test/test.file'),
                Yii::$app->uploads->getAbsolutePath('test/test.file')
            );
        });
    }

    public function testCreatePath()
    {
        $this->specify('create folders in upload folder', function ($path) {
            $expectedAbsolutePath = vfsStream::url('fs/upload/'.$path);

            $this->assertFileNotExists($expectedAbsolutePath);
            $absolutePath = Yii::$app->uploads->createPath($path);
            $this->assertFileExists($expectedAbsolutePath);
            $this->assertEquals(
                $expectedAbsolutePath,
                $absolutePath
            );
        }, [
            'examples' => [
                ['test-new'],
                ['test-new-2/test']
            ]
        ]);

        $this->specify('create existing folders in upload folder', function ($path) {
            $expectedAbsolutePath = Yii::$app->uploads->createPath($path);
            $absoluteTestFilePath = $expectedAbsolutePath.'/test-file';

            $this->assertFileExists($expectedAbsolutePath);
            file_put_contents($absoluteTestFilePath, 'test content');
            $this->assertFileExists($absoluteTestFilePath);

            $absolutePath = Yii::$app->uploads->createPath($path);
            $this->assertFileExists($expectedAbsolutePath);
            $this->assertEquals(
                $expectedAbsolutePath,
                $absolutePath
            );
            $this->assertFileExists($absoluteTestFilePath);
        }, [
            'examples' => [
                ['test-existing'],
                ['test-existing-2/test']
            ]
        ]);

        $this->specify('reject to create folder when file with the same name exists', function () {
            $absoluteFilePath = vfsStream::url('fs/upload/test-file');
            touch($absoluteFilePath);

            Yii::$app->uploads->createPath('test-file');
        }, [
            'throws' => new InvalidParamException
        ]);
    }

    public function testCreatePartitionedPath()
    {
        $this->specify('create partitioned path in upload folder', function () {
            $expectedAbsolutePath = vfsStream::url('fs/upload/test');

            $this->assertFileNotExists($expectedAbsolutePath);
            $absolutePartitionedPath = Yii::$app->uploads->createPartitionedPath('test', 'file');
            $this->assertFileExists($expectedAbsolutePath);
            $this->assertFileExists($absolutePartitionedPath);
            $this->assertRegExp('/test\/[0-9a-f]{2}$/', $absolutePartitionedPath);
        });
    }

    public function testExists()
    {
        $this->specify('check file existance', function () {
            $expectedAbsolutePath = vfsStream::url('fs/upload/test-file');

            $this->assertFileNotExists($expectedAbsolutePath);
            $this->assertFalse(Yii::$app->uploads->exists('test-file'));
            mkdir(vfsStream::url('fs/upload'));
            touch($expectedAbsolutePath);
            $this->assertTrue(Yii::$app->uploads->exists('test-file'));
        });
    }

    public function testSaveContent()
    {
        $this->specify('test saveContent', function () {
            $filePath = Yii::$app->uploads->saveContent('test', 'test-file', 'test content');
            $this->assertNotEmpty($filePath);
            $absoluteFilePath = Yii::$app->uploads->getAbsolutePath($filePath);
            $this->assertFileExists($absoluteFilePath);
            $this->assertEquals('test content', file_get_contents($absoluteFilePath));
        });

        $this->specify('test saveContent with STRATEGY_KEEP', function () {
            $filePath = Yii::$app->uploads->saveContent('test', 'test-file-keep', 'test content');
            $this->assertNotEmpty($filePath);
            $absoluteFilePath = Yii::$app->uploads->getAbsolutePath($filePath);
            $this->assertFileExists($absoluteFilePath);
            $this->assertEquals('test content', file_get_contents($absoluteFilePath));

            $this->specify('fail on ', function () {
                $filePath = Yii::$app->uploads->saveContent('test', 'test-file-keep', 'test content 2');
            }, [
                'throws' => new InvalidParamException
            ]);

            $this->assertEquals('test content', file_get_contents($absoluteFilePath));
        });

        $this->specify('test saveContent with STRATEGY_OVERWRITE', function () {
            $filePath = Yii::$app->uploads->saveContent('test', 'test-file-overwrite', 'test content');
            $this->assertNotEmpty($filePath);
            $absoluteFilePath = Yii::$app->uploads->getAbsolutePath($filePath);
            $this->assertFileExists($absoluteFilePath);
            $this->assertEquals('test content', file_get_contents($absoluteFilePath));

            $newFilePath =
                Yii::$app->uploads->saveContent(
                    'test',
                    'test-file-overwrite',
                    'test content 2',
                    UploadManager::STRATEGY_OVERWRITE
                );

            $this->assertEquals(
                $filePath,
                $newFilePath
            );
            $this->assertFileExists($absoluteFilePath);
            $this->assertEquals('test content 2', file_get_contents($absoluteFilePath));
        });

        $this->specify('test saveContent with STRATEGY_OVERWRITE when file name matches folder name', function () {
            Yii::$app->uploads->saveContent(
                'test/9f/56/test',
                'test',
                'test content'
            );
            Yii::$app->uploads->saveContent(
                'test',
                '56',
                '56',
                UploadManager::STRATEGY_OVERWRITE
            );
        }, [
            'throws' => new InvalidParamException
        ]);

        $this->specify('test saveContent with STRATEGY_RENAME', function ($name) {
            $filePath = Yii::$app->uploads->saveContent('test', $name, 'test content');
            $this->assertNotEmpty($filePath);
            $absoluteFilePath = Yii::$app->uploads->getAbsolutePath($filePath);
            $this->assertFileExists($absoluteFilePath);
            $this->assertEquals('test content', file_get_contents($absoluteFilePath));

            $newFilePath =
                Yii::$app->uploads->saveContent(
                    'test',
                    $name,
                    'test content 2',
                    UploadManager::STRATEGY_RENAME
                );
            $absoluteNewFilePath = Yii::$app->uploads->getAbsolutePath($newFilePath);

            $this->assertNotEquals(
                $filePath,
                $newFilePath
            );
            $this->assertFileExists($absoluteNewFilePath);
            $this->assertEquals('test content 2', file_get_contents($absoluteNewFilePath));
            $this->assertFileExists($absoluteFilePath);
            $this->assertEquals('test content', file_get_contents($absoluteFilePath));
        }, [
            'examples' => [
                ['test-file-rename'],
                ['test-file-rename.txt']
            ]
        ]);

    }
}
