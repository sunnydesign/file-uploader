<?php

namespace Kubia\Upload;

use Kubia\Upload\ApiRequestException;
use Symfony\Component\VarDumper\VarDumper;
use Kubia\Upload\File;
use Kubia\Upload\ApiRequest400Exception;
use Kubia\Upload\ApiRequest403Exception;
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

    /**
     * Upload constructor
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->api_url = UPLOAD_HOST;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->storage = UPLOAD_DIR;
        if(isset($settings['allowed_types']) && is_array($settings['allowed_types']))
            $this->allowed_types = $settings['allowed_types'];
    }

    /**
     *  Get link for upload file
     *
     * @throws ApiRequest400Exception
     * @throws ApiRequest405Exception
     */
    function getLink(): void
    {
        $this->checkMethod(['GET']);

        $client_uuid = $_SERVER['HTTP_X_USER_UUID'] ?? null;

        if(!$client_uuid) {
            $error = [
                'code' => 101,
                'message' => 'Missing parameter',
                'field' => 'X-USER-UUID'
            ];
            throw new ApiRequest400Exception(json_encode($error));
        }

        // create record in DB
        $file = new File();
        $file->client_uuid = $client_uuid;
        $file->save();

        // set response
        $url = $this->api_url . '/' . $file->hash;
        $this->response(['url' => $url]);
    }

    /**
     * Get uploaded file
     *
     * @param string $hash
     * @throws ApiRequest403Exception
     * @throws ApiRequest404Exception
     */
    function getFile(string $hash): void
    {
        $file = File::whereHash($hash)->first();

        if(!$file || $file->path === null || !is_file($file->path))
            throw new ApiRequest404Exception();

        $client_uuid = $_SERVER['HTTP_X_USER_UUID'] ?? null;

        if($client_uuid && $client_uuid !== $file->client_uuid)
            throw new ApiRequest403Exception();

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
     * @throws ApiRequest403Exception
     * @throws ApiRequest404Exception
     * @throws ApiRequest500Exception
     */
    function uploadFile(string $hash): void
    {
        $file = File::whereHash($hash)->first();

        if(!$file || $file->path !== null)
            throw new ApiRequest404Exception();

        if(!isset($_FILES) || empty($_FILES)) {
            $error = [
                'code' => 999,
                'message' => 'No file uploaded'
            ];
            throw new ApiRequest400Exception(json_encode($error));
        }

        $client_uuid = $_SERVER['HTTP_X_USER_UUID'] ?? null;

        if($client_uuid && $client_uuid !== $file->client_uuid)
            throw new ApiRequest403Exception();

        // upload files
        foreach ($_FILES as $key => $uploaded_file) {
            if ($uploaded_file['error'] == 0) {
                // validate file by mime type
                $this->validation($uploaded_file);

                try {
                    $destination_path = $this->getDestinationPath($hash);

                    // save file into storage
                    move_uploaded_file($uploaded_file['tmp_name'], $destination_path);

                    // save into DB
                    $file->path = $destination_path;
                    $file->mime = $uploaded_file['type'];
                    $file->size = $uploaded_file['size'];
                    $file->name = $uploaded_file['name'];

                    if($file->save()) {
                        $response = [
                            'uuid' => $file->uuid
                        ];
                        $this->response($response);
                    } else {
                        $error = [
                            'code' => 998,
                            'message' => 'File not saved'
                        ];
                        throw new ApiRequest400Exception(json_encode($error));
                    }
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
     * Get destination path to save file
     *
     * @param string $hash
     * @return string
     */
    public function getDestinationPath(string $hash): string
    {
        $first_dir_name = mb_substr($hash, 0, 3);
        $second_dir_name = mb_substr($hash, 3, 3);
        $filename = mb_substr($hash, 6);

        $first_dir_path = $this->storage . '/' . $first_dir_name;
        $second_dir_path = $first_dir_path . '/' . $second_dir_name;
        $destination_path = $second_dir_path . '/' . $filename;

        if (!file_exists($first_dir_path))
            mkdir($first_dir_path);
        if (!file_exists($second_dir_path))
            mkdir($second_dir_path);

        return $destination_path;
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
