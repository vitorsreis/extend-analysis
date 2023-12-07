<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis;

use Exception;
use VSR\Extend\Analysis;

class Request
{
    protected $routeKey;

    protected $parent_id = [];

    protected $profile = [];

    protected $profileCount = 0;

    protected $error = [];

    protected $extra = [];

    /**
     * @var callable
     */
    protected $beforeSave;

    /**
     * @param string $routeKey Route key
     * @param array $options Options
     */
    public function __construct($routeKey, $options = [ 'autoSave' => true, 'autoError' => true])
    {
        $this->routeKey = $routeKey;
        $this->start('profile');
        !empty($options['autoError']) && set_error_handler([$this, 'error'], E_ALL);
        !empty($options['autoError']) && set_exception_handler([$this, 'error']);
        !empty($options['autoSave']) && register_shutdown_function([$this, 'save']);
    }

    public function __destruct()
    {
        unset($this->profile, $this->parent_id, $this->error, $this->extra, $this->beforeSave);
    }

    /**
     * Start new activity
     * @param string $title
     * @return static
     */
    public function start($title)
    {
        $id = count($this->profile);
        $parent_id = !$this->parent_id ? -1 : end($this->parent_id);

        $this->profile[$id] = [
            'parent_id' => $parent_id,
            'title' => $title,
            'start' => microtime(true),
            'end' => null,
            'duration' => null,
            'extra' => null,
            'error' => []
        ];
        $this->profileCount++;
        $this->parent_id[] = $id;

        return $this;
    }

    /**
     * Stop current activity
     * @param mixed $extra Extra info about activity
     * @return static
     */
    public function stop($extra = null)
    {
        if (empty($this->parent_id)) {
            return $this;
        }

        $id = array_pop($this->parent_id);
        $this->profile[$id]['extra'] = $extra;
        $this->profile[$id]['end'] = $end = microtime(true);
        $this->profile[$id]['duration'] = $end - $this->profile[$id]['start'];
        return $this;
    }

    /**
     * Add error to current activity
     * @param int|Exception $error
     * @param string $message
     * @param string $file
     * @param int $line
     * @return $this
     */
    public function error($error = null, $message = null, $file = null, $line = null)
    {
        if (is_object($error)) {
            $e = $error;
            $error = $e->getCode();
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
        }

        $error = [
            'code' => $error,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ];

        $this->error[] = $error;
        $this->profile[count($this->profile) - 1]['error'][] = $error;
        return $this;
    }

    /**
     * Extra info about request
     * @param mixed $extra
     * @return $this
     */
    public function extra($extra)
    {
        $this->extra[] = $extra;
        return $this;
    }

    /**
     * @param callable $callback Return false to cancel save, or return data to save
     * @return $this
     */
    public function onBeforeSave($callback)
    {
        $this->beforeSave = $callback;
        return $this;
    }

    /**
     * @return int|false Request ID
     */
    public function save()
    {
        # Stop all
        while ($this->parent_id) {
            $this->stop();
        }

        $request = [
            'route_key' => $this->routeKey,
            'start' => $this->profile[0]['start'],
            'end' => $this->profile[0]['end'],
            'duration' => $this->profile[0]['end'] - $this->profile[0]['start'],

            'http_code' => !empty(http_response_code()) ? http_response_code() : null,
            'method' => !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null,
            'url' => !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null,
            'ip' => $this->getIP(),
            'referer' => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'useragent' => !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,

            'get' => $_GET ?: null,
            'post' => $_POST ?: null,
            'raw_post' => file_get_contents('php://input') ?: null,
            'files' => $_FILES ?: null,
            'cookies' => $_COOKIE ?: null,
            'server' => $_SERVER ?: null,
            'headers' => headers_list() ?: null,
            'inc_files' => get_included_files() ?: null,

            'memory' => memory_get_peak_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'error' => $this->error ?: null,
            'extra' => $this->extra ?: null,

            'profile' => $this->profile,
            'profile_count' => $this->profileCount
        ];
        $beforeSave = isset($this->beforeSave) && is_callable($this->beforeSave) ? $this->beforeSave : false;
        $this->__destruct();

        try {
            $request = $beforeSave ? call_user_func($this->beforeSave, $request) : $request;
            if (!$request) {
                return false;
            }

            # Save request
            return Analysis::getModel()->processRequest($request);
        } finally {
            unset($beforeSave, $request, $profile);
        }
    }

    private function getIP()
    {
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return null;
    }
}
