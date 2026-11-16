<?php
declare(strict_types=1);
namespace UsosProxy;
final class Source {
    public static function read(string $url): string {
        $dir = sys_get_temp_dir() . '/usos-proxy-' . hash('sha256', __DIR__);
        if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new \RuntimeException('Cache unavailable');
        $path = $dir . '/' . hash('sha256',$url);
        $lock = fopen($path.'.lock','c');
        if (!$lock || !flock($lock,LOCK_EX)) throw new \RuntimeException('Cache unavailable');
        try {
            clearstatcache(true,$path);
            $age = is_file($path) ? time()-filemtime($path) : PHP_INT_MAX;
            if ($age < 3600) return file_get_contents($path);
            try {
                $body = '';
                $ch = curl_init($url);
                curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_WRITEFUNCTION=>static function($ch,$chunk) use (&$body) { if(strlen($body)+strlen($chunk)>10*1024*1024) return 0; $body.=$chunk; return strlen($chunk); }]);
                $ok = curl_exec($ch);
                $status = curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
                curl_close($ch);
                if (!$ok || $status!==200) throw new \RuntimeException('Upstream unavailable');
                $parsed = \Sabre\VObject\Reader::read($body);
                if (!$parsed instanceof \Sabre\VObject\Component\VCalendar) throw new \RuntimeException('Invalid calendar');
                $temp = tempnam($dir,'write-');
                try {
                    if (file_put_contents($temp,$body)===false || !rename($temp,$path)) throw new \RuntimeException('Cache write failed');
                } finally { if (is_file($temp)) unlink($temp); }
                return $body;
            } catch (\Throwable $e) {
                if ($age<=86400) return file_get_contents($path);
                throw new \RuntimeException('Calendar unavailable');
            }
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
