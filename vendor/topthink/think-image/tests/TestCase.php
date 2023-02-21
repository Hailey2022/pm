<?php
namespace tests;
use think\File;
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getJpeg()
    {
        return new File(TEST_PATH . 'images/test.jpg');
    }
    protected function getPng()
    {
        return new File(TEST_PATH . 'images/test.png');
    }
    protected function getGif()
    {
        return new File(TEST_PATH . 'images/test.gif');
    }
}