<?php
class FileCache {
    private static function cacheDir(){
      return $_SERVER['DOCUMENT_ROOT'].'/cache/file_TEMP';
    }
    public static function getCache($key, $timeToLive = 86400) {
        $value = null;
        if(is_dir(self::cacheDir()))
        {
            $cachefile = self::cacheDir().'/' . $key . '.txt';
            // Read cachfile content if it exists
            if(file_exists($cachefile) && filemtime($cachefile) + $timeToLive > time()){
              $fh = fopen($cachefile, 'r');
              if(!$fh) {
                throw new Exception('Can not open cache file');
              }
              $value = json_decode(fread($fh, filesize($cachefile)));
              fclose($fh);
            }
        }
        return $value;
    }
    public static function setCache($key, $value) {
        if(is_dir(self::cacheDir()))
        {
            $cachefile = self::cacheDir().'/' . $key . '.txt';
            $fh = fopen($cachefile, 'w');
            if(!$fh) {
              throw new Exception('Can not write cache file');
            }
            fwrite($fh, json_encode($value));
            fclose($fh);
            chmod($cachefile, 0777);
        }
    }
    public static function deleteCache($key) {
        if(is_dir(self::cacheDir()))
        {
            $cachefile = self::cacheDir().'/' . $key . '.txt';
            unlink($cachefile);
        }
    }
}
