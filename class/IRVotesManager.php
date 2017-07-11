<?php

require_once dirname(__FILE__).'/IRMSSqlDataSource.php';

/**
 * Description of IRUserVote
 *
 * @author Oleg
 */
class IRVoteManager {

    CONST STATUS_ACTIVE = 1;

    private $_sessionValue;

    private $_dataSource;
    private $_voteKey;
    private $_vote;

    function __construct($config) {
        $this->_dataSource = new IRMSSqlDataSource($config);
        $this->_sessionValue = date('Y-m-d');
//        if ($connection = IRDBManager::datasource()->getDBConnection()) {
//            //
//        } else {
//            $output .= IRDBManager::getNoDbError();
//            return $output;
//        }
    }

    public function getAction() {
        $result = 'choose_vote';

        if (isset($_GET['object'])) {

            $this->_voteKey = $_GET['object'];
            if ($this->_vote = $this->getVote()) {
                if ($this->_vote['status_id'] == 0) {
                    $result = 'blocked';
                } else {
                    $sessionKey = 'submited_'.$this->_voteKey;
                    if (array_key_exists('submit_vote', $_POST)) {
                        //
                        $nounce = $_POST['nounce'];
                        $rate = $_POST['rate'];
                        $message = $_POST['rate'];
                        $notifyManager = isset($_POST['notify_manager']) ? 1 : 0;;
                        $userInfo = $_POST['user_info'];
                        $data = array(
                            'object_id' => $this->_vote['id'],
                            'rate' => $rate,
                            'message' => $message,
                            'notify_manager' => $notifyManager,
                            'user_info' => $userInfo,
                        );
                        if (is_uploaded_file($_FILES['media_file']['tmp_name'])) {
                            $check = getimagesize($_FILES["media_file"]["tmp_name"]);
                            if ($check !== false) {
                                $type = $_FILES['media_file']['type'];
                                $imageData = file_get_contents($_FILES['media_file']['tmp_name']);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($imageData);
                                $data['media_data'] = $base64;
                            }
                        }
                        //print_r($check);
                        if (empty($rate)) {
                            // one more
                            $result = 'form_submit';
                        } else {
                            $this->addRate($data);
                            $_SESSION[$sessionKey] = $this->_sessionValue;

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
            }
        }
        return $result;
    }

    public function getNounce() {
        $result = substr( md5( date('h:i:s') ), rand(1, 10), 10 );
        $_SESSION['submit_nounce'] = $result;
        return $result;
    }

    protected function addRate($data) {
        //var_dump()
        $this->_dataSource->setTable('[dbo].[Vote_Object_Rate]')->createRecord($data, true);
    }
}
