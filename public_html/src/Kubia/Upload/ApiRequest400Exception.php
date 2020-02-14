<?php

namespace Kubia\Upload;

class ApiRequest400Exception extends ApiRequestException
{
    protected $code = 400;
    protected $message = 'Bad request';
}
