<?php

class CacheableModel extends Phalcon\Mvc\Model
{
    protected static $_loadedOnce = array();

    protected static function _getKey($functionName, $parameters = array())
    {
        $uniqueKey = array($functionName);
        if($parameters != null) {
            if(!is_array($parameters)) {
                $parameters = array($parameters);
            }
            foreach ($parameters as $key => $value) {
                if (is_scalar($value)) {
                    $uniqueKey[] = $key . ':' . $value;
                } else {
                    if (is_array($value)) {
                        $uniqueKey[] = $key . ':[' . self::_getKey('parameter', $value) .']';
                    }
                }
            }
        }
        return md5(join('_', $uniqueKey));
    }

    private static function _getCached($key) {
        if (!isset(self::$_loadedOnce[$key])) { // Check memory
            self::$_loadedOnce[$key] = FileCache::getCache($key);
        }
        return self::$_loadedOnce[$key];
    }

    public static function find($parameters=null)
    {
        $key = self::_getKey('find', $parameters);
        if(($cached = self::_getCached($key)) !== null) {
            return $cached;
        }
        $data = parent::find($parameters);
        self::$_loadedOnce[$key] = $data;
        FileCache::setCache($key, $data);
        return $data;
    }

    public static function findFirst($parameters=null)
    {
        $key = self::_getKey('findAll', $parameters);
        if(($cached = self::_getCached($key)) !== null) {
            return $cached;
        }
        $data = parent::findFirst($parameters);
        self::$_loadedOnce[$key] = $data;
        FileCache::setCache($key, $data);
        return $data;
    }
}
