<?php

require_once dirname(__FILE__).'/IRMSSqlDataSource.php';

/**
 * Description of IRUserVote
 *
 * @author Oleg
 */
class IRVotesManager {

    CONST STATUS_ACTIVE = 1;
    CONST POST_TYPE_ID = 7;
    CONST NOUNCE_KEY = 'submit_nounce';

    private $_sessionValue;

    private $_dataSource;
    private $_voteKey;
    private $_vote;
    private $_config = array();
    private $_rateImageType;
    private $_rateImageData;

    function __construct($config) {
        $this->_dataSource = new IRMSSqlDataSource($config);
        $this->_sessionValue = date('Y-m-d');
        if ($connection = $this->_dataSource->getDBConnection()) {
            //
        } else {
            throw new Exception('No db connection');
        }
        $this->_config = $config;
    }

    public function getAction() {
        $result = 'choose_vote';

        if (isset($_GET['object'])) {

            $this->_voteKey = $_GET['object'];
            if ($this->_voteKey == '*******') {
                session_destroy();
                header('Location: /');
            }

            if ($this->_vote = $this->getVote()) {
                if ($this->_vote['status_id'] == 0) {
                    $result = 'blocked';
                } else {
                    $sessionKey = 'submited_'.$this->_voteKey;
                    $nounce = $_POST['nounce'];
                    if (array_key_exists('submit_vote', $_POST) && $nounce == $_SESSION[self::NOUNCE_KEY]) {
                        //
                        $nounce = $_POST['nounce'];
                        $rate = $_POST['rate'];
                        $message = $_POST['message'];
                        $notifyManager = isset($_POST['notify_manager']) ? 1 : 0;
                        $userInfo = isset($_POST['user_name']) ? "Name: {$_POST['user_name']};" : '';
                        $userInfo .= isset($_POST['user_room']) ? "Room: {$_POST['user_room']};" : '';
                        $userInfo .= isset($_POST['user_info']) ? "Contact: {$_POST['user_info']};" : '';
                        $data = array(
                            'object_id' => $this->_vote['id'],
                            'rate' => $rate,
                            'message' => $message,
                            'notify_manager' => $notifyManager,
                            'user_info' => $userInfo,
                        );
                        if (is_uploaded_file($_FILES['media_file']['tmp_name'])) {
                            //copy($_FILES['media_file']['tmp_name'], 'd:/test1.jpeg');
                            $check = getimagesize($_FILES["media_file"]["tmp_name"]);
                            if ($check !== false) {
                                $this->_rateImageType = $_FILES['media_file']['type'];
                                $type = $this->_rateImageType;
                                $imageData = file_get_contents($_FILES['media_file']['tmp_name']);
                                $this->_rateImageData = base64_encode($imageData);
                                $base64 = 'data:image/' . $type . ';base64,' . $this->_rateImageData;
                                $data['media_data'] = $base64;
                            }
                        }
                        //print_r($check);
                        if (empty($rate)) {
                            // one more
                            $result = 'form_submit';
                        } else {
                            $rateRow = $this->addRate($data);
                            $this->notifyVoteSubscribers($this->_vote, $rateRow);
                            $_SESSION[$sessionKey] = $this->_sessionValue;
                            $_SESSION[self::NOUNCE_KEY] = $this->getNounce();

                            $result = 'vote_added';
                        }
                            
                    } else {
                        
                        if ($_SESSION[$sessionKey] == $this->_sessionValue) {
                            $result = 'vote_review';
                        } else {
                            $result = 'form_submit';
                        }
                    }
                }
            } else {
                $result = 'wrong_vote';
            }
        }
        return $result;
    }

    public function getVote($voteKey = null) {
        $result = false;
        if (empty($voteKey)) {
            $voteKey = $this->_voteKey;
        }
        $tSql = "SELECT * from [dbo].[Vote_Object] where [Code] = N'{$voteKey}'";
        if ($object = $this->_dataSource->queryRow($tSql)) {
            $result = array_change_key_case( $object );
            if ($result['status_id'] == self::STATUS_ACTIVE) {
                // get AVG
                $tSql = "SELECT avg([Rate]*10) from [dbo].[Vote_Object_Rate] where [Object_Id] = {$result['id']}";
                $result['avg_rate'] = $this->_dataSource->queryValue($tSql);
                // get AVG
                $tSql = "SELECT * from [dbo].[Vote_Catalog_Config] where [Catalog_Key] = '{$result['catalog_key']}'";
                $result['vote_config'] = $this->_dataSource->queryRow($tSql);
                $result['vote_config'] = is_array($result['vote_config']) ? array_change_key_case($result['vote_config']) : $result['vote_config'];
            }
        }
        return $result;
    }

    public function getNounce() {
        $result = substr( md5( date('h:i:s') ), rand(1, 10), 10 );
        $_SESSION[self::NOUNCE_KEY] = $result;
        return $result;
    }

    protected function addRate($data) {
        //var_dump()
        return $this->_dataSource->setTable('[dbo].[Vote_Object_Rate]')->createRecord($data, true);
    }

    function notifyVoteSubscribers($vote, $rateRow) {
        $rateRow = array_change_key_case($rateRow);
        $rate = $rateRow['rate'];
        $__entityKey = trim($vote['entity_key']);
        $__catalogKey = trim($vote['catalog_key']);
        if ($rate >= 8 && !empty($rateRow['message'])) {
            // create new post
            //    1) User wrote text, made no photo     --> Show the rate and the text
            //    2) User made photo, wrote no text     --> Dont create post
            //    3) User made no photo, wrote no text  --> Dont create post
            //    4) User wrote text and made photo     --> Show the rate, text and photo
            require_once dirname(__FILE__).'/QaalogWebService.php';
            $webService = new QaalogWebService($this->_config['webservice_url']);

            $url = "post?__entityKey={$__entityKey}&__catalogKey={$__catalogKey}";
            //var_dump($url);
            $params = array(
                'message' => $rateRow['message'],
                'post_type_id' => self::POST_TYPE_ID,
                'attachment' => array(
                    'rate' => $rate,
                    'vote_object' => $vote['name']
                ),
            );
            if ($rateRow['media_data'] > '') {
                $params['media_data'] = array(
                    'content' => $this->_rateImageData,
                    'mime_type' => $this->_rateImageType,
                );
            }
            if ( $result = $webService->sendData('put', $url, $params) ) {
                $response['temp'][] = 'Send post executed';
            } else {
                $response['errors'][] = $webService->lastError['message'];
                $response['temp'][] = $webService->lastError['message'];
            }
        }
        if ($rate <= 4 || $rateRow['notify_manager'] == 1) {
            // send email
            require_once dirname(__FILE__).'/QaalogWebService.php';
            $webService = new QaalogWebService($this->_config['webservice_url']);

            $url = "user/sendMail?__entityKey={$__entityKey}&__catalogKey={$__catalogKey}";
            //var_dump($url);
            $text = "The place '{$vote['name']}' was rated by user on One2Ten<br/><br/>"
                  . " User rate:<br/>"
                  . "  - rate: {$rate}<br/>"
                  . "  - message: {$rateRow['message']}<br/>"
                  . " User info: {$rateRow['user_info']}<br/>";
            if ($rateRow['media_data'] > '') {
                $text .= "<img src='{$rateRow['media_data']}' />";
            }
            $params = array(
                'email' => $vote['vote_config']['manager_email'],
                'text' => $text,
            );
            if ( $result = $webService->sendData('post', $url, $params) ) {
                // 'Send mail executed';
            } else {
                //$response['errors'][] = $webService->lastError['message'];
                //$response['temp'][] = $webService->lastError['message'];
            }
        }
    }
}
