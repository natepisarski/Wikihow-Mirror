<?php

/**
 * Created by PhpStorm.
 * User: franky
 * Date: 2017/05/24
 * Time: 10:23 AM
 */
define('AES_128_CBC', 'aes-128-cbc');
//date_default_timezone_set('America/New_York');
class Civic_SIP {
    private $appId;         // The Application ID - for the Javascript instance
    private $iat;           // Issued At Time
    private $iss;
    private $exp;           // Expiry Time
    private $aud;           // The Audience
    private $sub;           // The Subject
    private $privateJWK;    // Private JSON Web Key
    private $publicJWK;     // Public JSON Web Key
    private $keyFromFile;   // Is the key from a file or a string
    private $civicSecret;   // The Civic Secret Key
    private $sipURL;        // THE URL of The Civic SIP




    function __construct() {
        $issuedAt   = time();
        $notBefore  = $issuedAt + 10;             //Adding 10 seconds
        $expire     = $notBefore + 60;            // Adding 60 seconds

        $this->iat = $issuedAt;
        $this->exp = $expire;
    }

    static function parseData($civic_data) {
        //print_r($civic_data);
        $civic_data_array = array();
        if (array_key_exists('encrypted', $civic_data)) {
            $civic_data_array['encrypted'] = boolval($civic_data['encrypted']);

        }

        if (array_key_exists('data', $civic_data)) {
            $civic_data_array['jwt'] = $civic_data['data'];
        }

        if (array_key_exists('userId', $civic_data)) {
            $civic_data_array['userId'] = $civic_data['userId'];
        }
        return $civic_data_array;
    }

    static function decryptData($payloadData, $secret) {
        $key = hex2bin($secret);
        $extracted_iv_hex = substr($payloadData,0,32);
        $extracted_iv = hex2bin($extracted_iv_hex);
        $encryptedData = substr($payloadData, 32);
        $decrypted = openssl_decrypt($encryptedData, AES_128_CBC, $key, 0, $extracted_iv);
        return $decrypted;
    }

    static function transformData($decrypted_array, $civic_data) {
        $civic_data_array = array();
        foreach ($decrypted_array as $data) {
            $label_arr = explode(".", $data['label']);
            $idx = count($label_arr) - 1;
            $label = $label_arr[$idx];
            $civic_data_array[$label] = $data['value'];

        }

        if (array_key_exists('userId', $civic_data)) {
            $civic_data_array['userId'] = $civic_data['userId'];
        }
        return $civic_data_array;
    }

    /**
     * @return mixed
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param mixed $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return mixed
     */
    public function getIat()
    {
        return $this->iat;
    }

    /**
     * @param mixed $iat
     */
    public function setIat($iat)
    {
        $this->iat = $iat;
    }

    /**
     * @return mixed
     */
    public function getExp()
    {
        return $this->exp;
    }

    /**
     * @param mixed $exp
     */
    public function setExp($exp)
    {
        $this->exp = $exp;
    }

    /**
     * @return string
     */
    public function getIss(): string
    {
        return $this->iss;
    }

    /**
     * @param string $iss
     */
    public function setIss(string $iss)
    {
        $this->iss = $iss;
    }           // The Issuer

    /**
     * @return mixed
     */
    public function getAud()
    {
        return $this->aud;
    }

    /**
     * @param mixed $aud
     */
    public function setAud($aud)
    {
        $this->aud = $aud;
    }

    /**
     * @return mixed
     */
    public function getSub()
    {
        return $this->sub;
    }

    /**
     * @param mixed $sub
     */
    public function setSub($sub)
    {
        $this->sub = $sub;
    }

    /**
     * @return mixed
     */
    public function getPrivateJWK()
    {
        return $this->privateJWK;
    }

    /**
     * @param mixed $privateJWK
     */
    public function setPrivateJWK($privateJWK)
    {
        $this->privateJWK = $privateJWK;
    }

    /**
     * @return mixed
     */
    public function getPublicJWK()
    {
        return $this->publicJWK;
    }

    /**
     * @param mixed $publicJWK
     */
    public function setPublicJWK($publicJWK)
    {
        $this->publicJWK = $publicJWK;
    }

    /**
     * @return mixed
     */
    public function getCivicSecret()
    {
        return $this->civicSecret;
    }

    /**
     * @param mixed $civicSecret
     */
    public function setCivicSecret($civicSecret)
    {
        $this->civicSecret = $civicSecret;
    }   // Civic Secret (used to generate Authentication Header)

    /**
     * @return string
     */
    public function getSipURL(): string
    {
        return $this->sipURL;
    }

    /**
     * @param string $sipURL
     */
    public function setSipURL(string $sipURL)
    {
        $this->sipURL = $sipURL;
    }

    /**
     * @return mixed
     */
    public function getKeyFromFile()
    {
        return $this->keyFromFile;
    }/**
 * @param mixed $keyFromFile
 */
    public function setKeyFromFile($keyFromFile)
    {
        $this->keyFromFile = $keyFromFile;
    }



}
