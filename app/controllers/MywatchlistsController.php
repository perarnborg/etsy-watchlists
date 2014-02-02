<?php
class MywatchlistsController extends ControllerBase
{
    public function initialize()
    {
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $this->config = $config->etsy;
        $etsyuser_id = $this->session->get('auth')['etsyuser_id'];
        $this->currentEtsyUser = EtsyUsers::findFirst($etsyuser_id);
        $this->parameters = Parameters::find();
    }

    public function indexAction()
    {
        $this->view->setTemplateAfter('main');
        $this->view->etsyUser = $this->currentEtsyUser;
        $this->view->watchlists = $this->currentEtsyUser->watchlists;
        $this->view->categories = $this->listCategories();
        $this->view->countries = $this->listCountries();
        $this->view->parameters = $this->parameters;
    }

    public function searchAction()
    {
        $keywords = isset($_GET['keywords']) ? $_GET['keywords'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $shipsto = isset($_GET['shipsto']) ? $_GET['shipsto'] : false;
        $listings = $this->searchListings($keywords, $category, $shipsto);
        echo json_encode($listings);
        die();
    }

    public function categoriesAction($categoryName = '')
    {
        $categories = $this->listCategories($categoryName);
        echo json_encode($categories);
        die();
    }

    private function searchListings($keywords, $category, $shipsto) {
        $url = 'https://openapi.etsy.com/v2/listings/active?limit=40&includes=MainImage,Shop';
        if($shipsto) {
            $url .= ',ShippingInfo';
        }
        if($keywords) {
            $url .= '&keywords=' . $keywords;
        }
        if($category) {
            $url .= '&category=' . $category;
        }
        $oauth = new OAuth($this->config->api_key, $this->config->api_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->setToken($this->currentEtsyUser->etsy_token, $this->currentEtsyUser->etsy_secret);
        $listings = array();
        try {
            $data = $oauth->fetch($url, null, OAUTH_HTTP_METHOD_GET);
            $json = $oauth->getLastResponse();
            $listings = json_decode($json)->results;
            if($shipsto) {
                $filteredResults = array();
                foreach($results as $result) {
                    foreach($result->ShippingInfo as $shipping) {
                        if($shipping->destination_country_id == null || $shipping->destination_country_name == $shipsto) {
                            array_push($filteredResults, $result);
                        }
                    }
                }
                $listings = $filteredResults;
            }
        } catch (OAuthException $e) {
            error_log($e->getMessage());
            error_log(print_r($oauth->getLastResponse(), true));
            error_log(print_r($oauth->getLastResponseInfo(), true));
        }
        return $listings;
    }

    private function listCategories($categoryName = '') {
        $url = 'https://openapi.etsy.com/v2/taxonomy/categories/'.$categoryName;
        $oauth = new OAuth($this->config->api_key, $this->config->api_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->setToken($this->currentEtsyUser->etsy_token, $this->currentEtsyUser->etsy_secret);
        $categories = array();
        try {
            $data = $oauth->fetch($url, null, OAUTH_HTTP_METHOD_GET);
            $json = $oauth->getLastResponse();
            $categories = json_decode($json)->results;
        } catch (OAuthException $e) {
            error_log($e->getMessage());
            error_log(print_r($oauth->getLastResponse(), true));
            error_log(print_r($oauth->getLastResponseInfo(), true));
        }
        return $categories;
    }

    private function listCountries() {
        $url = 'https://openapi.etsy.com/v2/countries';
        $oauth = new OAuth($this->config->api_key, $this->config->api_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $oauth->setToken($this->currentEtsyUser->etsy_token, $this->currentEtsyUser->etsy_secret);
        $countries = array();
        try {
            $data = $oauth->fetch($url, null, OAUTH_HTTP_METHOD_GET);
            $json = $oauth->getLastResponse();
            $countries = json_decode($json)->results;
        } catch (OAuthException $e) {
            error_log($e->getMessage());
            error_log(print_r($oauth->getLastResponse(), true));
            error_log(print_r($oauth->getLastResponseInfo(), true));
        }
        return $countries;
    }
}
