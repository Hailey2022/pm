<?php
namespace tests;
use think\Image;
class CropTest extends TestCase
{
    public function testJpeg()
    {
        $pathname = TEST_PATH . 'tmp/crop.jpg';
        $image    = Image::open($this->getJpeg());
        $image->crop(200, 200, 100, 100, 300, 300)->save($pathname);
        $this->assertEquals(300, $image->width());
        $this->assertEquals(300, $image->height());
        $file = new \SplFileInfo($pathname);
        $this->assertTrue($file->isFile());
        @unlink($pathname);
    }
    public function testPng()
    {
        $pathname = TEST_PATH . 'tmp/crop.png';
        $image    = Image::open($this->getPng());
        $image->crop(200, 200, 100, 100, 300, 300)->save($pathname);
        $this->assertEquals(300, $image->width());
        $this->assertEquals(300, $image->height());
        $file = new \SplFileInfo($pathname);
        $this->assertTrue($file->isFile());
        @unlink($pathname);
    }
    public function testGif()
    {
        $pathname = TEST_PATH . 'tmp/crop.gif';
        $image    = Image::open($this->getGif());
        $image->crop(200, 200, 100, 100, 300, 300)->save($pathname);
        $this->assertEquals(300, $image->width());
        $this->assertEquals(300, $image->height());
        $file = new \SplFileInfo($pathname);
        $this->assertTrue($file->isFile());
        @unlink($pathname);
    }
}