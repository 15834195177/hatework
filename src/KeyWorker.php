<?php
namespace Phptool;


class KeyWorker{
    const ASN=1;
    const PEM=2;

    private $key;
    private $isPrivate;
    private $keyFormat;
    private $keyProvider;

    function __construct($key,$keyFormat=2){

        $this->KeyWorker($key,$keyFormat);
    }

    function KeyWorker($key,$keyFormat=2)
    {
        $this->key = $key;
        $this->keyFormat = $keyFormat;
    }

    function encrypt($data){
        $this->_makesure_provider();
        $encrypted = '';
        if($this->isPrivate){
            foreach (str_split($data, 117) as $chunk) {
                $r= openssl_private_encrypt($chunk, $encryptData, $this->keyProvider,OPENSSL_PKCS1_PADDING);
                $encrypted .= $encryptData;
            }
            //$r= openssl_private_encrypt($data,$encrypted,$this->keyProvider,OPENSSL_PKCS1_PADDING);
        }
        else{
            foreach (str_split($data, 117) as $chunk) {
                $r= openssl_public_encrypt($chunk, $encryptData, $this->keyProvider,OPENSSL_PKCS1_PADDING);
                $encrypted .= $encryptData;
            }
            //$r= openssl_public_encrypt($data,$encrypted,$this->keyProvider,OPENSSL_PKCS1_PADDING);
        }

        return $r?$data = base64_encode($encrypted):null;
    }

    function decrypt($data){
        $this->_makesure_provider();
        $data = base64_decode($data);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            if($this->isPrivate){
                $r= openssl_private_decrypt($chunk,$decrypted,$this->keyProvider,OPENSSL_PKCS1_PADDING);
            }
            else{
                $r= openssl_public_decrypt($chunk,$decrypted,$this->keyProvider,OPENSSL_PKCS1_PADDING);
            }
            $crypto .= $decrypted;
        }
        return $crypto;
    }

    function _makesure_provider(){
        if($this->keyProvider==null){
            $this->isPrivate = strlen($this->key)>500;

            switch($this->keyFormat){
                case 1:{

                    $this->key = chunk_split($this->key,64,"\r\n");//转换为pem格式的公钥
                    if($this->isPrivate){
                        $this->key = "-----BEGIN PRIVATE KEY-----\r\n".$this->key."-----END PRIVATE KEY-----";
                    }
                    else{
                        $this->key = "-----BEGIN PUBLIC KEY-----\r\n".$this->key."-----END PUBLIC KEY-----";
                    }

                    break;
                }
            }

            $this->keyProvider = $this->isPrivate?openssl_pkey_get_private($this->key):openssl_pkey_get_public($this->key);
        }
    }
}
?>