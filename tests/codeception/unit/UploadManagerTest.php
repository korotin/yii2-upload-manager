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

class UploadManagerTest extends TestCase
{
    use Specify;

    protected $fs = null;

    protected function setUp()
    {
        parent::setUp();

        $this->fs = vfsStream::setup('fs');
    }

    public function testGetUrl()
    {
        $this->specify('test getUrl', function () {

            $this->assertEquals(
                '/upload/test/test.file',
                Yii::$app->uploads->getUrl('test/test.file')
            );
        });
    }

    public function testGetAbsolutePath()
    {
        $this->specify('test getAbsolutePath', function () {
            $this->assertEquals(
                vfsStream::url('fs/upload/test/test.file'),
                Yii::$app->uploads->getAbsolutePath('test/test.file')
            );
        });
    }

    public function testCreatePath()
    {
        $this->specify('test createPath for new path', function ($path) {
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
                ['test'],
                ['test2/test']
            ]
        ]);

        $this->specify('test createPath for existing path', function ($path) {
            $expectedAbsolutePath = vfsStream::url('fs/upload/'.$path);

            $this->assertFileExists($expectedAbsolutePath);
            $absolutePath = Yii::$app->uploads->createPath($path);
            $this->assertFileExists($expectedAbsolutePath);
            $this->assertEquals(
                $expectedAbsolutePath,
                $absolutePath
            );
        }, [
            'examples' => [
                ['test'],
                ['test2/test']
            ]
        ]);
    }

    public function testCretePartitionedPath()
    {
        $this->specify('test createPartitionedPath', function () {
            $expectedAbsolutePath = vfsStream::url('fs/upload/test');

            $this->assertFileNotExists($expectedAbsolutePath);
            $absolutePartitionedPath = Yii::$app->uploads->createPartitionedPath('test', 'file');
            $this->assertFileExists($expectedAbsolutePath);
            $this->assertFileExists($absolutePartitionedPath);
            $this->assertRegExp('/test\/[0-9a-f]{2}$/', $absolutePartitionedPath);
        });
    }
}
