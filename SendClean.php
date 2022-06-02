<?php
require_once 'SendClean/Messages.php';
require_once 'SendClean/Settings.php';
require_once 'SendClean/Accounts.php';
require_once 'SendClean/Exceptions.php';

class SendClean {

    public $apikey;
    public $owner_id;
    public $ch;
    public $debug = false;

    public static $error_map = array(
        "ValidationError" => "SendClean_ValidationError",
        "Invalid_Key" => "SendClean_Invalid_Key"
    );

    public function __construct($owner_id=null,$apikey=null,$SendCleanTES_APP_DOMAIN=null) {
        if(!$apikey) throw new SendClean_Error('You must provide a SendClean Token');
        if(!$owner_id) throw new SendClean_Error('You must provide a SendClean Owner id');
        if(!$SendCleanTES_APP_DOMAIN) throw  new SendClean_Error('You must provide a SendClean TES DOMAIN');
        $this->apikey = $apikey;
        $this->owner_id = $owner_id;

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'SendClean-PHP/1.0.11');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);

        $this->root = "https://api.$SendCleanTES_APP_DOMAIN/v1.0/";
        $this->messages = new SendClean_Messages($this);
        $this->settings = new SendClean_Settings($this);
        $this->accounts = new SendClean_Accounts($this);
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function call($url, $params) {
        $params['token'] = $this->apikey;
        $params['owner_id'] = $this->owner_id;
        $params = json_encode($params);
        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $this->root . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new SendClean_HttpError("API call to $url failed: " . curl_error($ch));
        }
        $result = json_decode($response_body, true);
        if($result === null) throw new SendClean_Error('We were unable to decode the JSON response from the SendClean API: ' . $response_body);

        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }

    public function castError($result) {
        if($result['status'] !== 'error' || !$result['name']) throw new SendClean_Error('We received an unexpected error: ' . json_encode($result));

        $class = (isset(self::$error_map[$result['name']])) ? self::$error_map[$result['name']] : 'SendClean_Error';
        return new $class($result['message'], $result['code']);
    }

    public function log($msg) {
        if($this->debug) error_log($msg);
    }
}


