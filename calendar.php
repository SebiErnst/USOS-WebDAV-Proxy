<?php
declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';
header('Cache-Control: private, no-store');
try {
    $config = require __DIR__.'/config.php';
    $style = $_GET['initials'] ?? 'plain';
    if (!is_string($style) || ($style !== 'plain' && !isset(UsosProxy\Calendar::INITIAL_STYLES[$style]))) throw new InvalidArgumentException();
    $sources = [];
    if (isset($_GET['source'])) {
        if (isset($_GET['user_id']) || isset($_GET['key']) || !is_array($_GET['source']) || !count($_GET['source']) || count($_GET['source'])>20) throw new InvalidArgumentException();
        foreach ($_GET['source'] as $value) {
            if (!is_string($value) || !preg_match('/^([A-Za-z]{1,4}):(\d+):([A-Za-z0-9_-]{1,256})$/D',$value,$m)) throw new InvalidArgumentException();
            $sources[] = ['initials'=>strtoupper($m[1]),'id'=>$m[2],'key'=>$m[3]];
        }
    } else {
        $id = $_GET['user_id'] ?? '';
        $key = $_GET['key'] ?? '';
        if (!is_string($id) || !is_string($key) || !preg_match('/^\d+$/D',$id) || !preg_match('/^[A-Za-z0-9_-]{1,256}$/D',$key)) throw new InvalidArgumentException();
        $sources[] = ['initials'=>'','id'=>$id,'key'=>$key];
    }
    foreach ($sources as &$source) {
        $url = strtr($config['url'],['{user_id}'=>rawurlencode($source['id']),'{key}'=>rawurlencode($source['key'])]);
        $source['ics'] = UsosProxy\Source::read($url);
    }
    unset($source);
    $result = UsosProxy\Calendar::merge($sources,$config['abbreviations'],$style);
    header('Content-Type: text/calendar; charset=utf-8');
    echo $result;
} catch (InvalidArgumentException $e) {
    http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo 'Invalid calendar parameters.';
} catch (Throwable $e) {
    http_response_code(502); header('Content-Type: text/plain; charset=utf-8'); echo 'Calendar temporarily unavailable.';
}
