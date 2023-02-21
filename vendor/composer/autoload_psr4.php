<?php
$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
return array(
    'wxapp\\' => array($vendorDir . '/thinkcmf/cmf-extend/src/wxapp'),
    'tree\\' => array($vendorDir . '/thinkcmf/cmf-extend/src/tree'),
    'think\\helper\\' => array($vendorDir . '/topthink/think-helper/src'),
    'think\\composer\\' => array($vendorDir . '/topthink/think-installer/src'),
    'think\\captcha\\' => array($vendorDir . '/topthink/think-captcha/src'),
    'think\\' => array($vendorDir . '/topthink/think-image/src'),
    'mindplay\\annotations\\' => array($vendorDir . '/mindplay/annotations/src/annotations'),
    'dir\\' => array($vendorDir . '/thinkcmf/cmf-extend/src/dir'),
    'cmf\\' => array($vendorDir . '/thinkcmf/cmf/src'),
    'app\\install\\' => array($vendorDir . '/thinkcmf/cmf-install/src'),
    'app\\' => array($vendorDir . '/thinkcmf/cmf-app/src'),
    'api\\' => array($vendorDir . '/thinkcmf/cmf-api/src'),
    'PHPMailer\\PHPMailer\\' => array($vendorDir . '/phpmailer/phpmailer/src'),
);
