<?php

namespace SimpleBus\BernardBundleBridge\Encrypter;

/**
 * Copied from https://github.com/nelmio/NelmioSecurityBundle/blob/master/Encrypter.php.
 */
class NelmioEncrypter implements Encrypter
{
    private $module;
    private $secret;
    private $algorithm;
    private $ivSize;

    public function __construct($secret, $algorithm)
    {
        $this->secret = substr($secret, 0, 32);
        $this->algorithm = $algorithm;

        if (!function_exists('mcrypt_module_open')) {
            throw new \RuntimeException('You need to install mcrypt if you want to encrypt your cookies.');
        }

        $this->module = @mcrypt_module_open($this->algorithm, '', MCRYPT_MODE_CBC, '');
        if ($this->module === false) {
            throw new \InvalidArgumentException(sprintf("The supplied encryption algorithm '%s' is not supported by this system.",
                $this->algorithm));
        }

        $this->ivSize = @mcrypt_enc_get_iv_size($this->module);
    }

    public function encrypt($input)
    {
        if (empty($input)) {
            return;
        }

        $iv = @mcrypt_create_iv($this->ivSize, MCRYPT_RAND);

        @mcrypt_generic_init($this->module, $this->secret, $iv);

        return rtrim(base64_encode($iv.@mcrypt_generic($this->module, (string) $input)), '=');
    }

    public function decrypt($input)
    {
        if (empty($input)) {
            return;
        }

        $encryptedData = base64_decode($input, true);

        $iv = substr($encryptedData, 0, $this->ivSize);

        if (strlen($iv) < $this->ivSize) {
            return;
        }

        $encryptedData = substr($encryptedData, $this->ivSize);

        $init = @mcrypt_generic_init($this->module, $this->secret, $iv);
        if ($init === false || $init < 0) {
            return;
        }

        return rtrim(@mdecrypt_generic($this->module, $encryptedData), "\0");
    }
}
