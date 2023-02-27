<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    public function crypt_decode_url($str = null)
    {
        $result = $this->decrypt_url($str);
        return $result;
    }

    public function crypt_encode_url($str = null)
    {
        $result = $this->encrypt_url($str);
        $result = urlencode($result);
        return $result;
    }

    /**
     * decrypt AES 256
     *
     * @param data $edata
     * @return decrypted data
     */
    private function decrypt_url($edata)
    {
        return openssl_decrypt($edata, 'AES-128-ECB', config('const.URL_CRYPT_SALT'));
    }

    /**
     * crypt AES 256
     *
     * @param data $data
     * @return base64 encrypted data
     */
    private function encrypt_url($data)
    {
        return openssl_encrypt($data, 'AES-128-ECB', config('const.URL_CRYPT_SALT'));
    }

}
