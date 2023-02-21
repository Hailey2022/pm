<?php
namespace tests;
use think\Image;
class FlipTest extends TestCase
{
    public function testJpeg()
    {
        $pathname = TEST_PATH . 'tmp/flip.jpg';
        $image    = Image::open($this->getJpeg());
        $image->flip()->save($pathname);
        $file = new \SplFileInfo($pathname);
        $this->assertTrue($file->isFile());
        @unlink($pathname);
    }
    public function testGif()
    {
        $pathname = TEST_PATH . 'tmp/flip.gif';
        $image    = Image::open($this->getGif());
        $image->flip(Image::FLIP_Y)->save($pathname);
        $file = new \SplFileInfo($pathname);
        $this->assertTrue($file->isFile());
        @unlink($pathname);
    }
}