<?php

class CacheableModel extends Phalcon\Mvc\Model
{
    protected static $_loadedOnce = array();

    protected static function _getKey($functionName, $parameters)
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
                        $uniqueKey[] = $key . ':[' . self::_createKey($value) .']';
                    }
                }
            }
        }
        return join('_', $uniqueKey);
    }

    private static function _getCached($key, $parameters) {
        if (!isset(self::$_loadedOnce[$key])) { // Check memory
            if(false && apc_exists(get_class(self).'_'.$key)) { // Check cache
                self::$_loadedOnce[$key] = apc_fetch($key);
            } else {
                return null;
            }
        }
        return self::$_loadedOnce[$key];
    }

    public static function find($parameters=null)
    {
        $key = self::_getKey('find', $parameters);
        if($cached = self::_getCached($key, $parameters) !== null) {
            return $cached;
        }
        $data = parent::find($parameters);
        self::$_loadedOnce[$key] = $data;
        if(false) {
            apc_store(get_class(self).'_'.$key, $data);
        }
        return $data;
    }

    public static function findFirst($parameters=null)
    {
        $key = self::_getKey('findAll', $parameters);
        if($cached = self::_getCached($key, $parameters) !== null) {
            return $cached;
        }
        $data = parent::findFirst($parameters);
        self::$_loadedOnce[$key] = $data;
        if(false) {
            apc_store(get_class(self).'_'.$key, $data);
        }
        return $data;
    }
}
