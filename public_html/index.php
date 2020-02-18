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

defined('BASE_DIR') or define('BASE_DIR', __DIR__);

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
        mkdir(UPLOAD_DIR, 0777);

    $u = new Upload();
    $u->router();

} catch (\Throwable $e) {
    if(ENV_DEV === true)
          dd($e);
}
