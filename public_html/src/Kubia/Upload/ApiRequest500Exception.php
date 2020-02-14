<?php

namespace Kubia\Upload;

class ApiRequest500Exception extends ApiRequestException
{
    protected $code = 500;
    protected $message = 'Internal server error';
}
