<?php
    require_once dirname(__FILE__).'/config.php';
    require_once dirname(__FILE__).'/class/IRVoteManager.php';

    session_start();
    $helper = new IRVoteManager($config);
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
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css" >
    <!-- Optional theme -->
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">

    <link rel="stylesheet" href="css/style.css" >

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="js/bootstrap.min.js"></script>
</head>
<body>

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
        <div class="row12">
            <h3>How do you rate your expirience?</h3>
        </div>
        <div class="row row12">
            <div class="col-sm-1"></div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=1 />1
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=2 />2
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=3 />3
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=4 />4
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=5 />5
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=6 />6
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=7 />7
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=8 />8
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=9 />9
            </div>
            <div class="col-sm-1">
                <input type="radio" name="rate" value=10 />10
            </div>
            <div class="col-sm-1"></div>
        </div>
        <div class="row row12">
            <div class="col-sm-1"></div>
            <div class="col-sm-3">
                Terrible
            </div>
            <div class="col-sm-4">
                Nothing special
            </div>
            <div class="col-sm-3">
                Amazing
            </div>
            <div class="col-sm-1"></div>
        </div>
        <ul class="list-group">
            <li class="list-group-item">
                <textarea name="message" placeholder="Add a personal note"></textarea>
            </li>
            <li class="list-group-item">
                <input name="media_file" type="file" />
                Upload photo
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

</body>
</html>
