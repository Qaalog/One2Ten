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
);

