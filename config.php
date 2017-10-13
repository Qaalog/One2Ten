<?php

$envGaCompanyId = getenv("ga_company_id");

$envDbServer = getenv("webservice_db_server");
$envDbName   = getenv("webservice_db_name");
$envDbUser   = getenv("webservice_db_user");
$envDbPassword = getenv("webservice_db_password");
$envWebserviceUrl = getenv("webservice_tol_ws_url");

$config = array(
    'server_name'   => empty($envDbServer) ? 'toldb-dev.database.windows.net' : $envDbServer,
    'database_name' => empty($envDbName)   ? 'one2ten_dev' : $envDbName,
    'user_name'     => empty($envDbUser)   ? 'ptadeu' : $envDbUser,
    'user_password' => empty($envDbPassword) ? 'L%%3v3r4g3' : $envDbPassword,
    'driver_type'   => 'sqlsrv',
    //
    //'webservice_url' => 'http://teamol.co/testap/',
    'webservice_url' => empty($envWebserviceUrl) ? 'http://tolws-dev.azurewebsites.net/' : $envWebserviceUrl,
    'ga_company_id'  => empty($envGaCompanyId) ? 'UA-101739744-1' : $envGaCompanyId,
    //
    'mail' => array(
        'from' => 'support@review10.net',
        'fromName' => 'Review10',
        'subject' => 'New user review',
        'smtp' => array(
//            'hostname' => 'smtp.gmail.com',
//            'port'      => '587',
//            'username' => 'support@leverage.pt',
//            'password' => 'L!!3v3r4g3',
            'hostname' => 'smtp.sendgrid.net',
            'port'     => '25',
            'username' => 'azure_a6cdaf5e3b668181c0a8d8b572f2ecab@azure.com',
            'password' => 'L%%3v3r4g3',
        ),
//        'admin-mail' => 'olukashov@itera-research.com',
    ),
);

