<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis;

class Dashboard
{
    public static function dashboard()
    {
        if (isset($_GET['d5whub-extend-analysis']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
            $_REQUEST = array_merge($_REQUEST, $_POST);
        }

        switch (isset($_GET['d5whub-extend-analysis']) ? $_GET['d5whub-extend-analysis'] : null) {
            case 'public-file':
                static::loadPublicFile();
                exit;

            default:
                static::view();
                break;
        }
    }

    private static function outputStatus($status)
    {
        http_response_code($status);
        exit;
    }

    private static function outputFile($file, $mimeType = null)
    {
        $etag = "W/\"" . md5_file($file) . "\"";

        # Cache
        $cache = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
        if ($cache === $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }

        if (null === $mimeType) {
            $mimeType = [
                'html' => 'text/html',
                'vue' => 'application/vue',
                'js' => 'application/javascript'
            ];

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mimeType = isset($mimeType[$ext]) ? $mimeType[$ext] : 'text/plain';
        }

        header("Content-Type: $mimeType; charset=utf-8");
        header('Content-Length: ' . filesize($file));
        header('ETag: ' . $etag);
//        header('Cache-Control: public, max-age=31536000, immutable');
//        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        readfile($file);
        exit;
    }

    private static function view()
    {
        self::outputFile(__DIR__ . '/Dashboard/index.html');
    }

    private static function loadPublicFile()
    {
        if (!isset($_REQUEST['file'])) {
            self::outputStatus(400);
        }

        $dir = realpath(__DIR__ . '/Dashboard/public');
        $component = realpath("$dir/$_REQUEST[file]");

        if (substr($component, 0, strlen($dir)) !== $dir) {
            self::outputStatus(403);
        }

        if (!file_exists($component)) {
            self::outputStatus(404);
        }

        self::outputFile($component);
    }
}
