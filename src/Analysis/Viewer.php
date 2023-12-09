<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis;

use VSR\Extend\Analysis;

class Viewer
{
    public static function execute()
    {
        switch (isset($_GET['d5whub-extend-analysis']) ? $_GET['d5whub-extend-analysis'] : null) {
            case 'file':
                if (!isset($_REQUEST['file'])) {
                    http_response_code(400);
                    exit;
                }
                static::outputFile($_REQUEST['file']);
                exit;

            case 'viewer-data':
                static::outputViewerData();
                exit;

            case 'request-data':
                static::outputRequestData();
                exit;

            default:
                self::outputFile('index.html');
                exit;
        }
    }

    private static function gzip($data)
    {
        if (!function_exists('gzencode')) {
            return $data;
        }

        if (empty($_SERVER['HTTP_ACCEPT_ENCODING']) || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
            return $data;
        }

        $data = gzencode($data);
        header('Content-Encoding: gzip');
        header('Content-Length: ' . strlen($data));
        return $data;
    }

    private static function outputFile($file)
    {
        $dir = realpath(__DIR__ . '/Viewer');
        $file = realpath("$dir/$file");

        if (substr($file, 0, strlen($dir)) !== $dir) {
            http_response_code(403);
            exit;
        }

        if (!file_exists($file)) {
            http_response_code(404);
            exit;
        }

        $etag = "W/\"" . md5_file($file) . "\"";

        # Cache
        $cache = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
        if ($cache === $etag) {
            http_response_code(304);
            exit;
        }

        $mimeType = [
            'html' => 'text/html',
            'vue' => 'text/html',
            'js' => 'application/javascript'
        ];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeType = isset($mimeType[$ext]) ? $mimeType[$ext] : 'text/plain';
        $content = file_get_contents($file);

        header("Content-Type: $mimeType; charset=utf-8");
        header('Content-Length: ' . strlen($content));
        header('ETag: ' . $etag);
        # header('Cache-Control: public, max-age=31536000');
        echo static::gzip($content);
        exit;
    }

    private static function outputViewerData()
    {
        $data = json_encode(
            Analysis::getModel()->getViewerData($_REQUEST),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Length: ' . strlen($data));
        echo static::gzip($data);
        exit;
    }

    private static function outputRequestData()
    {
    }
}
