<?php

namespace Kubia\Upload;

class ApiRequest403Exception extends ApiRequestException
{
    protected $code = 403;
    protected $message = 'Forbiden';
}
