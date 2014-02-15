<?php

class CacheableModel extends Phalcon\Mvc\Model
{
    protected static $_loadedOnce = array();
    const DEFAULT_CACHE_LIFETIME = 3600;

    public static function getCache(){
        $cache = false;
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $frontCache = new Phalcon\Cache\Frontend\Data(array(
            "lifetime" => (defined(self::CACHE_LIFETIME) ? self::CACHE_LIFETIME : self::DEFAULT_CACHE_LIFETIME)
        ));
        if($config->cache == 'memory') {
            $cache = new Phalcon\Cache\Backend\Libmemcached($frontCache, array(
                "host" => "localhost",
                "port" => "11211"
            ));
        } else if($config->cache == 'file') {
            $cache = new Phalcon\Cache\Backend\File($frontCache, array(
                "cacheDir" => "../app/cache/file/"
            ));
        }
        return $cache;
    }

    public static function _getKey($functionName, $parameters = array())
    {
        $uniqueKey = array(get_called_class(), $functionName);
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

    private static function _getCached($key, $cache) {
        if (!isset(self::$_loadedOnce[$key])) { // Check memory
            if($cache) {
                if($cached = $cache->get($key);
            }
            self::$_loadedOnce[$key] = FileCache::getCache($key);
        }
        return self::$_loadedOnce[$key];
    }

    public static function find($parameters=null)
    {
        $cache = self::getCache();
        $key = self::_getKey('find', $parameters);
        if(($cached = self::_getCached($key, $cache)) !== null) {
            return $cached;
        }
        $data = parent::find($parameters);
        self::$_loadedOnce[$key] = $data;
        if($cache) {
            $cache->save($key, $data);
        }
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
        if($cache) {
            $cache->save($key, $data);
        }
        return $data;
    }
}
