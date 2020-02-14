<?php

namespace Kubia\Upload;

class ApiRequest405Exception extends ApiRequestException
{
    protected $code = 405;
    protected $message = 'Method not allowed';
}
