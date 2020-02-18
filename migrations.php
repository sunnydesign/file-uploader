<?php

use Illuminate\Database\Capsule\Manager as DB;

$migrations['20200211'] = function () {
    $info = "Uploaded files tables";

    // templates schema
    DB::schema(DB_CONNECTION)->create('files', function ($table) {
        $table->increments('id');
        $table->uuid('uuid')->index();
        $table->uuid('client_uuid');
        $table->text('name')->nullable();
        $table->text('path')->nullable();
        $table->string('hash');
        $table->string('mime')->nullable();
        $table->integer('size')->nullable();
        $table->timestampsTz();
    });

    return $info;
};