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

class TranslitValidatorTest extends TestCase
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
}
