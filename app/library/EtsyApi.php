<?php
class EtsyApi {
	const API_BASE = 'https://openapi.etsy.com/v2/';

    private static function getCache(){
        $cache = false;
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $frontCache = new Phalcon\Cache\Frontend\Data(array(
            "lifetime" => 3500
        ));
        if($config->cache->method == 'memory') {
            $cache = new Phalcon\Cache\Backend\Memcache($frontCache, array(
                "host" => "localhost",
                "port" => "11211"
            ));
        } else if($config->cache->method == 'file') {
            $cache = new Phalcon\Cache\Backend\File($frontCache, array(
                "cacheDir" => "../app/cache/file/"
            ));
        }
        return $cache;
    }

    private static function getCacheKey($url){
        return md5(get_called_class().'_'.$url);
    }

    public static function parseListings($listingsResponse) {
        $listings = array();
        foreach ($listingsResponse as $listingResponse) {
            $listing = new stdClass();
            $listing->listing_id = $listingResponse->listing_id;
            $listing->title = $listingResponse->title;
            $listing->url = $listingResponse->url;
            $listing->image_thumb_url = $listingResponse->MainImage->url_75x75;
            $listing->image_url = $listingResponse->MainImage->url_170x135;
            $listing->shop_loginname = $listingResponse->Shop->login_name;
            $listing->shop_url = $listingResponse->Shop->url;
            $listing->currency_code = $listingResponse->currency_code;
            $listing->price = $listingResponse->price;
            $listing->creation = $listingResponse->creation_tsz;
            $listing->is_viewed = 0;
            array_push($listings, $listing);
        }
        return $listings;
    }

    public static function searchListings($apiKey, $apiSecret, $oauthToken, $oauthSecret, $keywords, $category, $shipsto) {
        $path = 'listings/active?limit=40&includes=MainImage,Shop';
        if($shipsto) {
            $path .= ',ShippingInfo';
        }
        if($keywords) {
            $path .= '&keywords=' . $keywords;
        }
        if($category) {
            $path .= '&category=' . $category;
        }
        $listings = self::makeRequest($apiKey, $apiSecret, $oauthToken, $oauthSecret, $path, true);
        if($shipsto) {
            $filteredResults = array();
            foreach($listings as $listing) {
                if(isset($listing->ShippingInfo)) {
                    foreach($listing->ShippingInfo as $shipping) {
                        if($shipping->destination_country_id == null || $shipping->destination_country_name == $shipsto) {
                            array_push($filteredResults, $listing);
                        }
                    }
                }
            }
            $listings = $filteredResults;
        }
        return $listings;
    }

    public static function listCategories($apiKey, $apiSecret, $oauthToken, $oauthSecret, $categoryName = '') {
        $path = 'taxonomy/categories/'.$categoryName;
        $categories = self::makeRequest($apiKey, $apiSecret, $oauthToken, $oauthSecret, $path);
        return $categories;
    }

    public static function listCountries($apiKey, $apiSecret, $oauthToken, $oauthSecret) {
        $path = 'countries';
        $countries = self::makeRequest($apiKey, $apiSecret, $oauthToken, $oauthSecret, $path);
        return $countries;
    }

	private static function makeRequest($apiKey, $apiSecret, $oauthToken, $oauthSecret, $path, $debug = false) {
        $url = self::API_BASE . $path;
        $cache = self::getCache();
        if($cache && $cached = $cache->get(self::getCacheKey($url))) {
            return $cached;
        }
        $oauth = new OAuth($apiKey, $apiSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->setToken($oauthToken, $oauthSecret);
        $results = array();
        try {
            $data = $oauth->fetch($url, null, OAUTH_HTTP_METHOD_GET);
            $json = $oauth->getLastResponse();
            $results = json_decode($json)->results;
        } catch (OAuthException $e) {
            if($debug) {
                error_log($e->getMessage());
                error_log(print_r($oauth->getLastResponse(), true));
                error_log(print_r($oauth->getLastResponseInfo(), true));
            }
        }
        if($cache) {
            $cache->save(self::getCacheKey($url), $results);
        }
        return $results;
	}
}
