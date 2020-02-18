<?php

namespace Kubia\Upload;

use Kubia\Logger\Logger;
use Kubia\Upload\ApiRequestException;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Http\Request;
use Kubia\Upload\File;
use Kubia\Upload\ApiRequest400Exception;
use Kubia\Upload\ApiRequest404Exception;
use Kubia\Upload\ApiRequest405Exception;
use Kubia\Upload\ApiRequest500Exception;

class Upload
{
    public $storage;
    public $api_url;
    public $method;
    public $allowed_size = MAX_ALLOWED_SIZE;
    public $allowed_types = [
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
    ];

    public function __construct($storage)
    {
        $this->api_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->storage = realpath(BASE_DIR . '/../' . $storage);
    }

    /**
     *  Get link for upload file
     */
    function getLink(): void
    {
        $this->checkMethod(['GET']);

        $client_id = $_GET['client_id'] ?? null;

        if(!$client_id) {
            $error = [
                'code' => 101,
                'message' => 'Missing parameter',
                'field' => 'client_id'
            ];
            throw new ApiRequest400Exception(json_encode($error));
        }

        // create record in DB
        $file = new File();
        $file->client_id = $client_id;
        $file->save();

        // set response
        $url = $this->api_url . '/' . $file->hash;
        $this->response(['url' => $url]);
    }

    /**
     * Get uploaded file
     *
     * @param string $hash
     * @throws ApiRequest404Exception
     */
    function getFile(string $hash): void
    {
        $file = File::whereHash($hash)->first();

        if(!$file || $file->path === null || !is_file($file->path))
            throw new ApiRequest404Exception();

        // return uploaded file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'. $file->name . '"');
        header('Content-Length: ' . $file->size);
        readfile($file->path);
    }

    /**
     * Upload file
     *
     * @param string $hash
     * @throws ApiRequest400Exception
     * @throws ApiRequest404Exception
     * @throws ApiRequest500Exception
     */
    function uploadFile(string $hash): void
    {
        $file = File::whereHash($hash)->first();

        if(!$file || $file->path !== null)
            throw new ApiRequest404Exception();

        if(!isset($_FILES) || empty($_FILES))
            throw new ApiRequest400Exception();

        // upload files
        foreach ($_FILES as $key => $uploaded_file) {
            if ($uploaded_file['error'] == 0) {
                // validate file by mime type
                $this->validation($uploaded_file);

                try {
                    $first_dir_name = mb_substr($hash, 0, 3);
                    $second_dir_name = mb_substr($hash, 3, 3);
                    $filename = mb_substr($hash, 6);

                    $first_dir_path = $this->storage . '/' . $first_dir_name;
                    $second_dir_path = $first_dir_path . '/' . $second_dir_name;
                    $destination_file = $second_dir_path . '/' . $filename;

                    // save file into storage
                    if (!file_exists($first_dir_path))
                        mkdir($first_dir_path);
                    if (!file_exists($second_dir_path))
                        mkdir($second_dir_path);

                    move_uploaded_file($uploaded_file['tmp_name'], $destination_file);

                    // save into DB
                    $file->path = $destination_file;
                    $file->mime = $uploaded_file['type'];
                    $file->size = $uploaded_file['size'];
                    $file->name = $uploaded_file['name'];
                    $file->save();

                    $response = [
                        'uuid' => $file->uuid
                    ];
                    $this->response($response);
                } catch (\Throwable $e) {
                    throw new ApiRequest500Exception($e->getMessage(), $e->getCode());
                }
            } else {
                $error = [
                    'code' => 999,
                    'message' => 'No file uploaded'
                ];
                throw new ApiRequest400Exception(json_encode($error));
            }
        }
    }

    /**
     * Validation uploaded file by mime type and by size
     *
     * @param array $uploaded_file
     * @throws ApiRequest400Exception
     */
    public function validation(array $uploaded_file): void
    {
        if(!in_array($uploaded_file['type'], $this->allowed_types)) {
            $error = [
                'code' => 101,
                'message' => 'Bad format file'
            ];
            throw new ApiRequest400Exception(json_encode($error));
        }

        if($uploaded_file['size'] > $this->allowed_size) {
            $error = [
                'code' => 102,
                'message' => 'File too large'
            ];
            throw new ApiRequest400Exception(json_encode($error));
        }
    }

    /**
     * Parsing uri and routing
     *
     * @throws ApiRequest404Exception
     * @throws ApiRequest405Exception
     */
    function router(): void
    {
        $uri = explode('?', $_SERVER['REQUEST_URI']);
        $uri_exploded = explode('/', $uri[0]);

        try {
            if ($uri_exploded[1] === '')
                throw new ApiRequest404Exception();

            if ($uri_exploded[1] === 'link') {
                $this->getLink();
            } else {
                if ($this->method === 'GET')
                    $this->getFile($uri_exploded[1]);
                elseif ($this->method === 'POST')
                    $this->uploadFile($uri_exploded[1]);
                else
                    throw new ApiRequest405Exception();
            }
        } catch(ApiRequest400Exception $e) {
            $this->error400($e);
        } catch(ApiRequestException $e) {
            $this->error($e);
        }
    }

    /**
     * Error 400 response
     *
     * @param ApiRequest400Exception $e
     */
    public function error400(ApiRequest400Exception $e): void
    {
        http_response_code($e->getCode());

        $response = [
            'error' => [
                json_decode($e->getMessage())
            ]
        ];

        $this->response($response);
    }

    /**
     * Error response
     *
     * @param ApiRequestException $e
     */
    public function error(ApiRequestException $e): void
    {
        Logger::stdout($e->getMessage(), '','', 'uploader', 1);
        http_response_code($e->getCode());
        $this->response();
    }

    /**
     * Response in json
     *
     * @param array|null $response
     */
    public function response(?array $response): void
    {
        header('Content-Type: application/json');
        if($response)
            echo json_encode($response);
    }

    /**
     * Check request method
     *
     * @param array $allowed_methods
     * @throws ApiRequest405Exception
     */
    public function checkMethod(array $allowed_methods): void
    {
        if(!in_array($this->method, $allowed_methods))
            throw new ApiRequest405Exception();
    }
}