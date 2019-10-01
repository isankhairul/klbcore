<?php namespace Klb\Core;

use RuntimeException;

/**
 * Class PUpload
 *
 * @package App
 */
class PUpload
{
    public $maxUpload = 5;
    /**
     * @var
     */
    public $filePath;
    /**
     * @var
     */
    private $callback;

    /**
     * @return mixed
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return int
     */
    public function getMaxUpload()
    {
        return $this->maxUpload;
    }

    /**
     * @param int $maxUpload
     */
    public function setMaxUpload($maxUpload)
    {
        $this->maxUpload = (int)$maxUpload;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param callable $callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @throws RuntimeException
     * @return string
     * @return bool $remove
     */
    public function store($remove = false)
    {
        // Make sure file is not cached (as it happens for example on iOS devices)
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        /*
        // Support CORS
        header("Access-Control-Allow-Origin: *");
        // other CORS headers if any...
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
        */
        // 5 minutes execution time
        set_time_limit($this->maxUpload * 60);
        // Uncomment this one to fake upload time
        // usleep(5000);
        // Settings
        $targetDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "plupload";
        //$targetDir = 'uploads';
        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = $this->maxUpload * 3600; // Temp file age in seconds
        // Create target dir
        if (!file_exists($targetDir)) {
            mkdir($targetDir);
        }
        // Get a file name
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } else if (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                throw new RuntimeException('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}.part") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    if (is_file($tmpfilePath)) unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }
        // Open temp file
        if (!$out = fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
            throw new RuntimeException('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                throw new RuntimeException('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }
            // Read binary input stream and append it to temp file
            if (!$in = fopen($_FILES["file"]["tmp_name"], "rb")) {
                throw new RuntimeException('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = fopen("php://input", "rb")) {
                throw new RuntimeException('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        if (is_resource($out)) fclose($out);
        if (is_resource($in)) fclose($in);
        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
        }
        $this->filePath = $filePath;
        if (null !== ($callback = $this->getCallback())) {
            $callback($this, $_FILES);
        }
        if (true === $remove && \is_file($this->filePath)) unlink($this->filePath);

        // Return Success JSON-RPC response
        return '{"jsonrpc" : "2.0", "result" : "OK", "id" : "id", "file" : "' . $filePath . '"}';
    }

}
