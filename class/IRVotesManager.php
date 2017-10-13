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
    private $_rateImage;
    private $_sessionId;

    function __construct($config) {
        $this->_dataSource = new IRMSSqlDataSource($config);
        $this->_sessionValue = date('Y-m-d');
        if ($connection = $this->_dataSource->getDBConnection()) {
            //
        } else {
            throw new Exception('No db connection');
        }
        $this->_sessionId = session_id();
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
                        $tag = $_POST['tag'];
                        $notifyManager = isset($_POST['notify_manager']) ? 1 : 0;
                        $userInfo = '';
                        if ($notifyManager) {
                            $userInfo .= isset($_POST['user_name']) ? "Name: {$_POST['user_name']};" : '';
                            $userInfo .= isset($_POST['user_info']) ? "Contact: {$_POST['user_info']};" : '';
                            $userInfo .= isset($_POST['user_room']) ? "Info: {$_POST['user_room']};" : '';
                        }

                        $data = array(
                            'object_id' => $this->_vote['id'],
                            'rate' => $rate,
                            'message' => $message,
                            'notify_manager' => $notifyManager,
                            'user_info' => $userInfo,
                            'user_id' => $this->_sessionId,
                            'tag' => $tag,
                        );
                        if (is_uploaded_file($_FILES['media_file']['tmp_name'])) {
                            //copy($_FILES['media_file']['tmp_name'], 'd:/test1.jpeg');
                            $check = getimagesize($_FILES["media_file"]["tmp_name"]);
                            if ($check !== false) {
                                $this->_rateImage = $_FILES['media_file'];
                                $this->_rateImageType = $_FILES['media_file']['type'];
                                $type = $this->_rateImageType;
                                $imageData = file_get_contents($_FILES['media_file']['tmp_name']);
                                $this->_rateImageData = base64_encode($imageData);
                                $base64 = 'data:' . $type . ';base64,' . $this->_rateImageData;
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
                        
                        if ($this->_vote['wait_time']>0) {
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
                // get Config
                $tSql = "SELECT * from [dbo].[Vote_User_Config] where [Owner_User] = '{$result['owner_user']}'";
                $result['vote_config'] = $this->_dataSource->queryRow($tSql);
                $result['vote_config'] = is_array($result['vote_config']) ? array_change_key_case($result['vote_config']) : $result['vote_config'];
                // get AVG & last rate
                $nextVotePeriod = isset($result['vote_config']) && isset($result['vote_config']['next_vote_period']) ? (int)$result['vote_config']['next_vote_period'] : 0;
                $tSql = 
                    "SELECT avg([Rate]*10) avg_rate,
                            (select top 1 [Rate] from [dbo].[Vote_Object_Rate] where [User_Id] is not null AND [User_Id] = '{$this->_sessionId}' AND [Object_Id] = ar.[Object_Id] order by id desc) last_rate,
                            (select top 1 [DateTime_Created] from [dbo].[Vote_Object_Rate] where [User_Id] is not null AND [User_Id] = '{$this->_sessionId}' AND [Object_Id] = ar.[Object_Id] order by id desc) last_rate_date,
                            (select top 1 DATEDIFF( minute, SYSDATETIME(), dateadd(hour, {$nextVotePeriod}, [DateTime_Created])) from [dbo].[Vote_Object_Rate] where [User_Id] is not null AND [User_Id] = '{$this->_sessionId}' AND [Object_Id] = ar.[Object_Id] order by id desc) wait_time
                       from [dbo].[Vote_Object_Rate] ar
                       where [Object_Id] = {$result['id']}
                       group by [Object_Id]";
                $rateInfo = $this->_dataSource->queryRow($tSql);
                $result['avg_rate'] = isset($rateInfo['avg_rate']) ? $rateInfo['avg_rate'] : 0;
                $result['last_rate'] = isset($rateInfo['last_rate']) ? $rateInfo['last_rate'] : null;
                $result['wait_time'] = isset($rateInfo['wait_time']) ? max($rateInfo['wait_time']/60, 0) : 0;
                $result['wait_time'] = ceil($result['wait_time']);

                $tags = array();
                if (isset($result['tag_words']) && trim($result['tag_words']) > '') {
                    foreach(explode(',', $result['tag_words']) as $tag) {
                        if (trim($tag) > '') {
                            $tags[] = trim($tag);
                        }
                    }
                }
                $result['tags'] = $tags;
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

        return $this->_dataSource->setTable('[dbo].[Vote_Object_Rate]')->createRecord($data, true);
    }

    function notifyVoteSubscribers($vote, $rateRow) {
        $rateRow = array_change_key_case($rateRow);
        $rate = $rateRow['rate'];
        $__entityKey = trim($vote['vote_config']['entity_key']);
        $__catalogKey = trim($vote['vote_config']['catalog_key']);
        $markInformIfAbove = (int)$vote['vote_config']['inform_if_above'];
        $markInformIfBelow = (int)$vote['vote_config']['inform_if_below'];
        if ($markInformIfAbove && $rate >= $markInformIfAbove && !empty($rateRow['message'])) {

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
            //var_dump($params);die();
            if ( $result = $webService->sendData('put', $url, $params) ) {
                $response['temp'][] = 'Send post executed';
            } else {
                $response['errors'][] = $webService->lastError['message'];
                $response['temp'][] = $webService->lastError['message'];
            }
        }
        if ( ($markInformIfBelow && $rate <= $markInformIfBelow) || $rateRow['notify_manager'] == 1) {

            // send email
            $text = "<div style='max-width:240px'>"
                      . "<b>'{$vote['name']}'</b> was rated <b>{$rate}</b><br/>"
                      . "<br/>"
                      . "{$rateRow['message']}<br/>"
                      . (($rateRow['media_data'] > '') ? '<table width="100%" style="width:240px"><tr><td><img style="max-width:100%;height:auto" src="cid:attached_place" /></td></tr></table>': '')
                      . "<br/>"
                      . implode('<br/>', explode(';', $rateRow['user_info']))
                      . "<br/>"
                      . "<br/>"
                      . "Thank you,<br/>"
                      . "Review10 Support<br/>"
                  . "</div>";
            $params = array(
                'email' => $vote['vote_config']['manager_email'],
                'text'  => $text,
                'subject' => 'New user review',
            );
            if ($rateRow['media_data'] > '') {
                //$text .= "<img src='{$rateRow['media_data']}' />";
                $params['embed_file'] = array(
                    'name' => $this->_rateImage['name'],
                    'path' => $this->_rateImage['tmp_name'],
                );
            }
            if ( $result = $this->sendMail($params) ) {
                // 'Send mail executed';
            } else {
                //$response['errors'][] = $webService->lastError['message'];
                //$response['temp'][] = $webService->lastError['message'];
            }
        }
    }

    function sendMail($params) {
        $email = isset($params['email']) ? $params['email'] : '';
        $emails = explode(',', $email);
        foreach ($emails as $key=>$email) {
            $email = trim($email);
            if (empty($email)) {
                unset($emails[$key]);
            }
        }
        $text = isset($params['text']) ? $params['text'] : null;
        $subject = isset($params['subject']) ? $params['subject'] : $this->_config['mail']['subject'];
        if (!empty($emails) && $text) {
            $username = $this->_config['mail']['smtp']['username'];
            $password = $this->_config['mail']['smtp']['password'];

            require_once (dirname(__FILE__)."/3rdparty/phpmailer/class.phpmailer.php");

            $mail = new PHPMailer();

            if (isset($this->_config['mail']['smtp'])) {
                $mail->IsSMTP();                // set mailer to use SMTP
                $mail->SMTPAuth = true;         // turn on SMTP authentication
                $mail->SMTPSecure = "tls";
                $mail->Host = $this->_config['mail']['smtp']['hostname']; // specify main and backup server
                $mail->Port = $this->_config['mail']['smtp']['port'];
                $mail->Username = $username;    // SMTP username
                $mail->Password = $password;    // SMTP password
            }
                
            $mail->CharSet = 'UTF-8';

            $mail->From = $this->_config['mail']['from'];
            $mail->FromName = $this->_config['mail']['fromName'];
            foreach($emails as $email) {
                $mail->AddAddress( $email, '' );
            }
            if ($email = $this->_config['mail']['admin-mail']) {
                $mail->addBCC( $email, '' );
            }

            if (isset($params['embed_file'])) {
                $mail->addEmbeddedImage($params['embed_file']['path'], 'attached_place', $params['embed_file']['name']);
            }

            $mail->WordWrap = 50;                                 // set word wrap to 50 characters
            $mail->IsHTML(true);                                  // set email format to HTML

            $mail->Subject = $subject;
            $mail->Body    = $text;
            $mail->AltBody = strip_tags($text);

            if ($mail->Send()) {
                //br()->log()->writeLn('New mail sent to ' . implode(',', $emails));
                return array( 'mail_sent' => 1 );
            } else {
                $error = 'Mailer Error: ' . $mail->ErrorInfo;
                print_r($error);
                //throw new Exception( $error );
            }
        } else {
            //throw new Exception('We can not send mail because mail address or text is empty');
        }
    }
}
