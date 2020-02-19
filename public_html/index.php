<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Libs
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use Kubia\Upload\Upload;

// Config
$config = __DIR__ . '/../config.php';
$config_env = __DIR__ . '/../config.env.php';

if (is_file($config)) {
    require_once $config;
} elseif (is_file($config_env)) {
    require_once $config_env;
}

try {
    // DB connection init
    $db = new DB();

    $db->addConnection([
        "driver"   => DB_DRIVER,
        "host"     => DB_HOST,
        "database" => DB_DATABASE,
        "username" => DB_USER,
        "password" => DB_PASSWORD,
        "schema"   => DB_SCHEMA,
        "strict"   => false
    ], DB_CONNECTION);

    $db->setEventDispatcher(new Dispatcher(new Container()));
    $db->setAsGlobal();
    $db->bootEloquent();
    if(ENV_DEV === true)
        $db::connection(DB_CONNECTION)->enableQueryLog();
    
    // Make storage dir
    if (!file_exists(UPLOAD_DIR))
        mkdir(UPLOAD_DIR);

    // Set allowed file types to upload
    $settings = [
        'allowed_types' => [
            'jpg|jpeg|jpe'     => 'image/jpeg',
            'gif'              => 'image/gif',
            'png'              => 'image/png',
            'bmp'              => 'image/bmp',
            'tif|tiff'         => 'image/tiff',
            'pdf'              => 'application/pdf',
            'txt|asc|c|cc|h'   => 'text/plain',
            'rtf'              => 'application/rtf',
            'doc'              => 'application/msword',
            'docx'             => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xla|xls|xlt|xlw'  => 'application/vnd.ms-excel',
            'xlsx'             => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'odt'              => 'application/vnd.oasis.opendocument.text',
            'ods'              => 'application/vnd.oasis.opendocument.spreadsheet'
        ]
    ];

    $u = new Upload($settings);
    $u->router();

} catch (\Throwable $e) {
    // Set ENV_DEV as TRUE in DEVELOPMENT mode only
    if(ENV_DEV === true)
          dd($e);
}
