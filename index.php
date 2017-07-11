<?php
    require_once dirname(__FILE__).'/config.php';
    require_once dirname(__FILE__).'/class/IRVotesManager.php';

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

    <div class="container">
    <?php if ($action == 'blocked') : ?>

        <div class="media">
            <div class="media-left">
                <img class="media-object" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
            </div>
            <div class="media-body">
                <h4 class="media-heading"><?php echo $vote['name'] ?></h4>
            </div>
        </div>
        <div class="row12">
            <p>
                At this moment we cannot accept rewiews for this. Please inform the person responsible.
            </p>
            <p>
                We are sorry for any inconvience!
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
                        <button class="btn btn-success">Submit</button>
                    </li>
                </ul>
            </form>
        </div>

    <?php elseif ($action == 'vote_added') : ?>

        <div class="media">
            <div class="media-left">
                <img class="media-object" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
            </div>
            <div class="media-body">
                <h4 class="media-heading"><?php echo $vote['name'] ?></h4>
            </div>
        </div>
        <div class="row12">
            <p>
                Thank You for Your feedback!
            </p>
        </div>
        <div class="alert alert-success" role="alert">
            Your rate <?php echo $_POST['rate'] ?>
        </div>
        <div class="progress">
            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $vote['avg_rate'] ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $vote['avg_rate'] ?>%;">
                <span class="sr-only">60% Complete</span>
            </div>
        </div>
        <div class="alert alert-info" role="alert">
            Current Average <?php echo round($vote['avg_rate']/10, 1); ?>
        </div>
        <div class="row12">
            <p>
                As you requested, the manager has been notified
            </p>
        </div>

        <script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

          ga('create', 'UA-101739744-1', 'auto');
          ga('set', 'dimension1'/*entity*/,         '<?php echo $vote['catalog_key'] ?>' );
          ga('set', 'dimension2'/*'qrcode*/,        '<?php echo $vote['name'] ?>' );
          ga('set', 'dimension3'/*informManager*/,  '<?php echo isset($_POST['notify_manager']) ? 1 : 0 ?>' );
          ga('set', 'metric1' /*rate*/,             '<?php echo $_POST['rate'] ?>' );
          ga('send', 'pageview');

        </script>

    <?php elseif ($action == 'vote_review') : ?>

        <div class="media">
            <div class="media-left">
                <img class="media-object" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
            </div>
            <div class="media-body">
                <h4 class="media-heading"><?php echo $vote['name'] ?></h4>
            </div>
        </div>
        <div class="progress">
            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $vote['avg_rate'] ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $vote['avg_rate'] ?>%;">
                <span class="sr-only">60% Complete</span>
            </div>
        </div>
        <div class="alert alert-info" role="alert">
            Current Average <?php echo round($vote['avg_rate']/10, 1); ?>
        </div>

    <?php elseif ($action == 'form_submit') : ?>

        <div class="media">
            <div class="media-left">
                <img class="media-object" src="<?php echo $vote['media_data'] ?>" alt="<?php echo $vote['name'] ?>">
            </div>
            <div class="media-body">
                <h4 class="media-heading"><?php echo $vote['name'] ?></h4>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="nounce" value="<?php echo $nounce; ?>" />
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4>How do you rate your expirience?</h4>
                </div>
                
            </div>
            <div class="row">
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=1 />1
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=2 />2
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=3 />3
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=4 />4
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=5 />5
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=6 />6
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=7 />7
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=8 />8
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=9 />9
                    </label>
                </div>
                <div class="col-xs-1 col-sm-1">
                    <label class="rating">
                        <input type="radio" name="rate" value=10 />10
                    </label>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-3 col-sm-3">
                    Terrible
                </div>
                <div class="col-xs-5 col-sm-5">
                    Nothing special
                </div>
                <div class="col-xs-2 col-sm-2 text-right">
                    Amazing
                </div>
            </div>
            <ul class="list-group">
                <li class="list-group-item">
                    <textarea name="message" placeholder="Add a personal note"></textarea>
                </li>
                <li class="list-group-item">
                    Upload photo
                    <img class="media-file-preview" style="display:none" />
                    <input name="media_file" type="file" />
                </li>
                <li class="list-group-item">
                    <input name="notify_manager" type="checkbox" />
                    Notify the manager
                </li>
                <li class="list-group-item">
                    <input class="text" name="user_info" placeholder="Add contact email or phone" />
                </li>
                <li class="list-group-item">
                    <button name="submit_vote" class="btn btn-success">Submit</button>
                </li>
            </ul>
        </form>

    <?php endif; ?>
    </div>

</body>
</html>
