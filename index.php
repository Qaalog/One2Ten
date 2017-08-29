<?php
    require_once dirname(__FILE__).'/config.php';
    require_once dirname(__FILE__).'/class/IRVotesManager.php';

    session_cache_expire(60*24);
    session_start();
    try {
        $helper = new IRVotesManager($config);
    } catch (Exception $e) {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 '.$e->getMessage());
        echo 'Service Temporarily Unavailable';
        exit;
    }

    $action = $helper->getAction();
    switch ($action) {
        case 'choose_vote':
        case 'wrong_vote':
            // Enter 4 digit code

            break;

        case 'form_submit':
            $vote = $helper->getVote();
            $nounce = $helper->getNounce();

            break;

        case 'vote_review':
            $vote = $helper->getVote();

            break;

        default:
            $vote = $helper->getVote();
            break;
    }

    //var_dump($nounce, $vote, $_POST, $_SESSION);
?>
<!DOCTYPE html>
<html>
<head>
    <title>One2ten :: <?php echo $action ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/bootstrap.min.css" >
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/style.css" >

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/scripts.js"></script>
</head>
<body>
    <div id="no-js">Please enable JavaScript in your browser</div>
    <div class="container text-center" style="display: none;">
    <?php if ($action == 'blocked') : ?>

        <header class="vote-header inlined text-left">
          <div class="table-block">
            <div class="table-row">
              <?php if ($vote['media_data']) : ?>
              <div class="table-col optimal-width">
                <div class="img-wrap img-circle inlined">
                  <img class="abs-c" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
                </div>
              </div>            
              <?php endif; ?>
              <div class="table-col">
                <h1 class="inlined"><?php echo $vote['name'] ?></h1>
              </div>
            </div>
          </div>
        </header>

        <div class="text-center">
            <p>
                At this moment we cannot accept reviews for this. Please inform the person responsible.
            </p>
            <p>
                We are sorry for any inconvenience!
            </p>
        </div>

    <?php elseif ($action == 'choose_vote' || $action == 'wrong_vote') : ?>

        <?php if ($action == 'wrong_vote') : ?>
            <div class="alert alert-warning alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <strong>Warning!</strong> You entered wrong code
            </div>
        <?php endif; ?>
        <div class="row12">
            <form>
                <ul class="list-group">
                    <li class="list-group-item">
                        <input name="object" placeholder="Enter your 4-digit code" />
                    </li>
                    <li class="list-group-item">
                        <button class="btn-custom">Submit</button>
                    </li>
                </ul>
            </form>
        </div>

    <?php elseif ($action == 'vote_added') : ?>
        
        <header class="vote-header inlined text-left">
          <div class="table-block">
            <div class="table-row">
              <?php if ($vote['media_data']) : ?>
              <div class="table-col optimal-width">
                <div class="img-wrap img-circle inlined">
                  <img class="abs-c" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
                </div>
              </div>            
              <?php endif; ?>
              <div class="table-col">
                <h1 class="inlined"><?php echo $vote['name'] ?></h1>
              </div>
            </div>        
          </div>
        </header>

        <h3 class="text-center"><strong>Thank You For Your Feedback !</strong></h3>
        
        <div class="clearfix rate-result">
            <div class="number-wrap">
                <div class="number-radio">1</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">2</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">3</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">4</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">5</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">6</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">7</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">8</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">9</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">10</div>
            </div>
            
            <div class="rate-yours">
                You Rated
                <strong><?php echo $_POST['rate'] ?></strong>               
            </div>

            <div class="rate-current">
                <strong><?php echo round($vote['avg_rate']/10, 1); ?></strong>
                Current Average
            </div>
        </div>

        <?php if (isset($_POST['notify_manager'])) : ?>
          <div class="text-center">
            <p>As you requested, the manager has been notified</p>
           </div>
        <?php endif; ?>

        <!-- <button class="btn-custom">Close</button> -->

        <script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

          ga('create', 'UA-101739744-1', 'auto');
          ga('set', 'dimension1'/*entity*/,        '<?php echo $vote['vote_config']['entity_key'] ?>' );
          ga('set', 'dimension2'/*'qrcode*/,       '<?php echo $vote['name'] ?>' );
          ga('set', 'dimension3'/*informManager*/, '<?php echo isset($_POST['notify_manager']) ? 1 : 0 ?>' );
          ga('set', 'dimension4'/*userMail*/,      '<?php echo $vote['owner_user'] ?>' );
          ga('set', 'metric1'   /*rate*/,          '<?php echo $_POST['rate'] ?>' );
          ga('set', 'metric2'   /*rateNr*/,        1 );
          ga('send', 'pageview');

        </script>

    <?php elseif ($action == 'vote_review') : ?>

        <header class="vote-header inlined text-left">
          <div class="table-block">
            <div class="table-row">
              <?php if ($vote['media_data']) : ?>
              <div class="table-col optimal-width">
                <div class="img-wrap img-circle inlined">
                  <img class="abs-c" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
                </div>
              </div>            
              <?php endif; ?>
              <div class="table-col">
                <h1 class="inlined"><?php echo $vote['name'] ?></h1>
              </div>
            </div>
          </div>
        </header>

        <div class="text-center">
            <h3><strong>You have already reviewed this</strong></h3>
        </div>

        <div class="clearfix rate-result">
            <div class="number-wrap">
                <div class="number-radio">1</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">2</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">3</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">4</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">5</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">6</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">7</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">8</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">9</div>
            </div>
            <div class="number-wrap">
                <div class="number-radio">10</div>
            </div>

            <?php if ($vote['last_rate']) : ?>
            <div class="rate-yours">
                You Rated
                <strong><?php echo $vote['last_rate'] ?></strong>
            </div>
            <?php endif; ?>

            <div class="rate-current">
                <strong><?php echo round($vote['avg_rate']/10, 1); ?></strong>
                Current Average
            </div>
        </div>

        <?php if ($vote['vote_config'] && $vote['vote_config']['next_vote_period'] && $vote['wait_time']>0) : ?>
        <div class="text-center">
            <p>
                You can make a new review in <?php echo $vote['wait_time']; ?> hour(s)
            </p>
        </div>
        <?php endif; ?>
        

    <?php elseif ($action == 'form_submit') : ?>

        <header class="vote-header inlined text-left">
          <div class="table-block">
            <div class="table-row">
              <?php if ($vote['media_data']) : ?>
              <div class="table-col optimal-width">              
                <div class="img-wrap img-circle inlined">
                  <img class="abs-c" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
                </div>            
              </div>
              <?php endif; ?>
              <div class="table-col">
                <h1 class="inlined"><?php echo $vote['name'] ?></h1>
              </div>
            </div> 
          </div>
        </header>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="nounce" value="<?php echo $nounce; ?>" />

            <div class="step-first">            
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <h3><em>How do you rate your experience?</em></h3>
                    </div>
                </div>
                <div class="clearfix">
                    <div class="number-wrap">
                        <input id="1" type="radio" name="rate" value=1>
                        <label class="number-radio" for="1">1</label>
                    </div>
                    <div class="number-wrap">
                        <input id="2" type="radio" name="rate" value=2>
                        <label class="number-radio" for="2">2</label>
                    </div>
                    <div class="number-wrap">
                        <input id="3" type="radio" name="rate" value=3>
                        <label class="number-radio" for="3">3</label>
                    </div>
                    <div class="number-wrap">
                        <input id="4" type="radio" name="rate" value=4>
                        <label class="number-radio" for="4">4</label>
                    </div>
                    <div class="number-wrap">
                        <input id="5" type="radio" name="rate" value=5>
                        <label class="number-radio" for="5">5</label>
                    </div>
                    <div class="number-wrap">
                        <input id="6" type="radio" name="rate" value=6>
                        <label class="number-radio" for="6">6</label>
                    </div>
                    <div class="number-wrap">
                        <input id="7" type="radio" name="rate" value=7>
                        <label class="number-radio" for="7">7</label>
                    </div>
                    <div class="number-wrap">
                        <input id="8" type="radio" name="rate" value=8>
                        <label class="number-radio" for="8">8</label>
                    </div>
                    <div class="number-wrap">
                        <input id="9" type="radio" name="rate" value=9>
                        <label class="number-radio" for="9">9</label>
                    </div>
                    <div class="number-wrap">
                        <input id="10" type="radio" name="rate" value=10>
                        <label class="number-radio" for="10">10</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-3 text-left">
                        Terrible
                    </div>
                    <div class="col-xs-6 text-center">
                        Nothing special
                    </div>
                    <div class="col-xs-3 text-right">
                        Amazing
                    </div>
                </div>
                <span class="btn-custom rate-ready">Next</span>
            </div>
            
            <div class="step-last">
                <div class="upload-wrap">
                    <div class="input-wrap">
                        <textarea class="input" name="message" placeholder="Add a personal note"></textarea>

                        <input id="media_file" class="btn-upload" name="media_file" type="file" accept="image/*" capture />
                        <label for="media_file">Choose a file</label>
                    </div>
                    <div class="file-wrap">
                        <span class="close-file"></span>
                        <img class="media-file-preview" style="display: none" />
                    </div>
                </div>
                <div class="switcher text-left">
                    <div class="switch inlined">
                        <input id="notify_manager" name="notify_manager" type="checkbox" />
                        <label for="notify_manager" class="slider"></label>
                    </div>
                    <span class="inlined">Notify the manager</span>
                </div>
                <div class="if-notify">
                    <div class="input-wrap">
                        <input class="input" name="user_name" placeholder="Name" />
                    </div>
                    <div class="input-wrap">
                        <input class="input" name="user_room" placeholder="Room nr" />
                    </div>
                    <div class="input-wrap">
                        <input class="input" name="user_info" placeholder="Contact phone or email" />
                    </div>
                </div>                
                <button name="submit_vote" class="btn-custom">Submit</button>
            </div>
        </form>

    <?php endif; ?>
    </div>

</body>
</html>
