<?php

return [
    'EASEMOB_DOMAIN' => env('easemob_domain', ''),          //域名
    'ORG_NAME'       => env('easemob_org_name', ''),        //公司名称
    'APP_NAME'       => env('easemob_app_name', ''),        //应用名称
    'CLIENT_ID'       => env('easemob_client_id', ''),
    'CLIENT_SECRET'   => env('easemob_client_secret', ''),
    'TOKEN_PATH'     => env('token_path','easemob.token'),               //token储存的位置，默认不用修改
];
