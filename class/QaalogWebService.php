<?php

/**
 * Description of QaalogWebService
 *
 * @author Oleg
 */
class QaalogWebService {
    
    public $responseCode;
    public $lastError = array();
    private $serviceUrl;
    private static $_instance = array();

    function __construct($url) {
        $this->serviceUrl = $url;
    }

    function sendData( $method, $url, $data, $dataType = 'json', $params = array() ) {
        $url = $this->serviceUrl . $url;
        $result = $this->sendBy( $method, $url, $data, $dataType, $params );
        if ( $this->responseCode != 200 ) {
            $this->lastError['message'] = 'WebService response code: '.$this->responseCode.'. Ask admin for details.';
            $result = false;
        }
        if ( !empty($this->lastError['errno']) ) {
            $result = false;
        }
        return $result;
    }

    public function sendBy( $method, $url, $data, $dataType = 'json', $params = array() ) {

        $this->curl = curl_init();
        $this->lastError = array();

        $envelope = array();

        if (is_array($data)) {
            foreach ($data as $name => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $envelope[$name] = $value;
            }
        }
        //var_dump($url);

        if ($method == 'post' || $method == 'put' || $method == 'delete') {
            if ($dataType == 'json') {
                if ($method == 'post') {
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
                } elseif ($method == 'put') {
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT");
                } elseif ($method == 'delete') {
                    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                }                
                $requestString = json_encode($data);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $requestString);
                curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($requestString))
                );
            } else {
                curl_setopt($this->curl, CURLOPT_POST, 1);
                if (is_array($data)) {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $envelope);
                } else {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
                }
            }
        } else {
            if (is_array($data)) {
                $get = '';
                foreach ($envelope as $name => $value) {
                    $get .= $name . '=' . urlencode($value) . '&';
                }
            } else {
                $get = $data;
            }
            $get = rtrim($get, '&');
            if (preg_match('/[?]/', $url)) {
                $url = $url . '&' . $get;
            } else {
                $url = $url . '?' . $get;
            }
            curl_setopt($this->curl, CURLOPT_POST, 0);
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($this->curl, CURLOPT_USERAGENT, 'SugarConnector/1.4');
        curl_setopt($this->curl, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-GB; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3');

        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);

        foreach ($params as $name => $value) {
            curl_setopt($this->curl, $name, $value);
        }

        $response = curl_exec($this->curl);
        $this->lastError['errno'] = curl_errno($this->curl);
        $this->lastError['message']= curl_error($this->curl);
        //var_dump($curlErr, $curlMsg, $url);

        $this->responseCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        switch ($dataType) {
            case 'json':
            case 'jsonp':
                if ($json = json_decode($response)) {
                    $response = $json;
                } else
                if ($json = json_decode('{' . $response . '}')) {
                    $response = $json;
                }
                break;
        }

        return $response;
    }

    public static function GetInstance($url) {
        if ( isset(self::$_instance[$url]) ) {
            // Ok
        } else {
            self::$_instance[$url] = new QaalogWebService($url);
        }
        return self::$_instance[$url];
    }
}
