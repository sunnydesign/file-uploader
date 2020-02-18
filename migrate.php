<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;

// Config
$config = __DIR__ . '/config.php';
$config_env = __DIR__ . '/config.env.php';

if (is_file($config)) {
    require_once $config;
} elseif (is_file($config_env)) {
    require_once $config_env;
}

$migrations = [];

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

include __DIR__ . '/migrations.php';

function execute($id) {
    global $migrations;
    echo 'Executing migration: '.$id.': ';
    try {
        $info = call_user_func($migrations[$id]);

        DB::connection(DB_CONNECTION)->table(DB_SCHEMA . '.migrations')->insertOrIgnore([
            'key' => $id,
            'info' => $info,
            'created_at' => DB::connection(DB_CONNECTION)->raw('now()')
        ]);

        echo $info."\n";
    } catch (Exception $e) {
        echo 'Ошибка: ',  $e->getMessage(), "\n";
    }
}

if (!DB::schema(DB_CONNECTION)->hasTable(DB_SCHEMA . '.migrations')) {
    $info = "Таблица миграций";
    DB::schema(DB_CONNECTION)->create(DB_SCHEMA . '.migrations', function ($table) {
        $table->increments('id');
        $table->string('key')->unique();
        $table->string('info');
        $table->boolean('is_harmful')->default(false);
        $table->timestamps();
    });
}

$options = getopt("n:");

if (empty($options['n'])) {
    foreach ($migrations as $key => $migration) {
        if(stripos($key,'harmful') !== false) {
            throw new \Exception("Migrations consists harmful entries, please, run script manually.");
        }

        $migrated = DB::connection(DB_CONNECTION)->table(DB_SCHEMA . '.migrations')->where('key', $key)->get();

        if ($migrated->isEmpty()){
            execute($key);
        }
    }
} else {
    execute($options['n']);
}