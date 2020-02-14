<?php

namespace Kubia\Upload;

class ApiRequest404Exception extends ApiRequestException
{
    protected $code = 404;
    protected $message = 'Not found';
}
