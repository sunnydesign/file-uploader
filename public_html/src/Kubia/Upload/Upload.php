<?php

namespace Kubia\Upload;

use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Http\Request;
use Kubia\Upload\File;
use Kubia\Upload\ApiRequest400Exception;
use Kubia\Upload\ApiRequest404Exception;
use Kubia\Upload\ApiRequest405Exception;
use Kubia\Upload\ApiRequest500Exception;
use Kubia\Upload\ApiRequestException;

class Upload
{
    public $storage;
    public $api_url;
    public $method;
    public $allowed_size = MAX_ALLOWED_SIZE;
    public $allowed_types = [
        // Image formats
        'jpg|jpeg|jpe'                 => 'image/jpeg',
        'gif'                          => 'image/gif',
        'png'                          => 'image/png',
        'bmp'                          => 'image/bmp',
        'tif|tiff'                     => 'image/tiff',
        'ico'                          => 'image/x-icon',

        // Video formats
        'asf|asx'                      => 'video/x-ms-asf',
        'wmv'                          => 'video/x-ms-wmv',
        'wmx'                          => 'video/x-ms-wmx',
        'wm'                           => 'video/x-ms-wm',
        'avi'                          => 'video/avi',
        'divx'                         => 'video/divx',
        'flv'                          => 'video/x-flv',
        'mov|qt'                       => 'video/quicktime',
        'mpeg|mpg|mpe'                 => 'video/mpeg',
        'mp4|m4v'                      => 'video/mp4',
        'ogv'                          => 'video/ogg',
        'webm'                         => 'video/webm',
        'mkv'                          => 'video/x-matroska',

        // Text formats
        'txt|asc|c|cc|h'               => 'text/plain',
        'csv'                          => 'text/csv',
        'tsv'                          => 'text/tab-separated-values',
        'ics'                          => 'text/calendar',
        'rtx'                          => 'text/richtext',
        'css'                          => 'text/css',
        'htm|html'                     => 'text/html',

        // Audio formats
        'mp3|m4a|m4b'                  => 'audio/mpeg',
        'ra|ram'                       => 'audio/x-realaudio',
        'wav'                          => 'audio/wav',
        'ogg|oga'                      => 'audio/ogg',
        'mid|midi'                     => 'audio/midi',
        'wma'                          => 'audio/x-ms-wma',
        'wax'                          => 'audio/x-ms-wax',
        'mka'                          => 'audio/x-matroska',

        // Misc application formats
        'rtf'                          => 'application/rtf',
        'js'                           => 'application/javascript',
        'pdf'                          => 'application/pdf',
        'swf'                          => 'application/x-shockwave-flash',
        'class'                        => 'application/java',
        'tar'                          => 'application/x-tar',
        'zip'                          => 'application/zip',
        'gz|gzip'                      => 'application/x-gzip',
        'rar'                          => 'application/rar',
        '7z'                           => 'application/x-7z-compressed',
        'exe'                          => 'application/x-msdownload',

        // MS Office formats
        'doc'                          => 'application/msword',
        'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
        'wri'                          => 'application/vnd.ms-write',
        'xla|xls|xlt|xlw'              => 'application/vnd.ms-excel',
        'mdb'                          => 'application/vnd.ms-access',
        'mpp'                          => 'application/vnd.ms-project',
        'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',
        'xlsx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm'                         => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xlsb'                         => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xltx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'xltm'                         => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xlam'                         => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'pptx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptm'                         => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'ppsx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppsm'                         => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'potx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'potm'                         => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'ppam'                         => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'sldx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'sldm'                         => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',

        // OpenOffice formats
        'odt'                          => 'application/vnd.oasis.opendocument.text',
        'odp'                          => 'application/vnd.oasis.opendocument.presentation',
        'ods'                          => 'application/vnd.oasis.opendocument.spreadsheet',
        'odg'                          => 'application/vnd.oasis.opendocument.graphics',
        'odc'                          => 'application/vnd.oasis.opendocument.chart',
        'odb'                          => 'application/vnd.oasis.opendocument.database',
        'odf'                          => 'application/vnd.oasis.opendocument.formula',

        // WordPerfect formats
        'wp|wpd'                       => 'application/wordperfect',

        // iWork formats
        'key'                          => 'application/vnd.apple.keynote',
        'numbers'                      => 'application/vnd.apple.numbers',
        'pages'                        => 'application/vnd.apple.pages',
    ];

    public function __construct($storage)
    {
        $this->api_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $this->method = $_SERVER['REQUEST_METHOD'];

        $this->storage = realpath(BASE_DIR . '/' . $storage);
    }

    /**
     *  Get link for upload file
     */
    function getLink(): void
    {
        $this->checkMethod(['GET']);

        $client_id = $_GET['client_id'] ?? null;

        if(!$client_id)
            throw new ApiRequest400Exception('Missing parameter `client_id`');

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
                    $destination_dir = $this->storage . '/' . $hash;
                    $destination_file = $destination_dir . '/' . $uploaded_file['name'];

                    // save file into storage
                    mkdir($destination_dir);
                    move_uploaded_file($uploaded_file['tmp_name'], $destination_file);

                    // save into DB
                    $file->path = $destination_file;
                    $file->mime = $uploaded_file['type'];
                    $file->size = $uploaded_file['size'];
                    $file->name = $uploaded_file['name'];
                    $file->save();

                    $response = ['success' => true];
                    $this->response($response);
                } catch (\Throwable $e) {
                    throw new ApiRequest500Exception($e->getMessage(), $e->getCode());
                }
            } else {
                throw new ApiRequest400Exception('No file uploaded');
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
        if(!in_array($uploaded_file['type'], $this->allowed_types))
            throw new ApiRequest400Exception('Bad format file');

        if($uploaded_file['size'] > $this->allowed_size)
            throw new ApiRequest400Exception('File too large');
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
        } catch(ApiRequestException $e) {
            $this->error($e);
        }
    }

    /**
     * Error response
     *
     * @param ApiRequestException $e
     */
    public function error(ApiRequestException $e): void
    {
        http_response_code($e->getCode());
        $response = [
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]
        ];
        $this->response($response);
    }

    /**
     * Response in json
     *
     * @param array $response
     */
    public function response(array $response): void
    {
        header('Content-Type: application/json');
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