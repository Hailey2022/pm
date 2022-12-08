<?php
namespace wxapp\aes;


class Prpcrypt
{
    public $key;

    public function __construct($k)
    {
        $this->key = $k;
    }

    
    public function decrypt($aesCipher, $aesIV)
    {

        if (function_exists('openssl_decrypt')) {

            $decrypted = openssl_decrypt($aesCipher, 'AES-128-CBC', $this->key, OPENSSL_RAW_DATA, $aesIV);

            if ($decrypted === false) return [ErrorCode::$IllegalBuffer, null];
        } else if (function_exists('mcrypt_module_open')) {
            try {

                $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');

                mcrypt_generic_init($module, $this->key, $aesIV);

                //解密
                $decrypted = mdecrypt_generic($module, $aesCipher);
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);
            } catch (\Exception $e) {

                return [ErrorCode::$IllegalBuffer, null];
            }
        }


        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result      = $pkc_encoder->decode($decrypted);

        } catch (\Exception $e) {
            //print $e;
            return [ErrorCode::$IllegalBuffer, null];
        }
        return [0, $result];
    }
}