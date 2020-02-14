<?php

namespace Kubia\Upload;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['uuid', 'client_id', 'name', 'path', 'hash', 'mime', 'size'];
    protected $guarded = ['id'];
    protected $dateFormat = 'Y-m-d H:i:sP';
    protected $connection = 'uploader';

    public static function boot()
    {
        parent::boot();

        self::creating(function ($file) {
            do {
                $uuid = Uuid::uuid4()->toString();
            } while (File::where('uuid', $uuid)->exists());

            $file->uuid = $uuid;
            $file->hash = hash('sha256', $uuid);
        });
    }
}
