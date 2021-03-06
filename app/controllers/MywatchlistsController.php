<?php
use Phalcon\Mvc\Model\Resultset;
class MywatchlistsController extends ControllerBase
{
    public function initialize()
    {
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $this->config = $config->etsy;
        $auth = $this->session->get('auth');
        $etsyuser_id = $auth['etsyuser_id'];
        $this->currentEtsyUser = EtsyUsers::findFirst($etsyuser_id);
        $this->parameters = Parameters::find();
    }

    public function indexAction()
    {
        $this->view->setTemplateAfter('main');
        $this->view->etsyUser = $this->currentEtsyUser;
        $watchlists = Watchlists::find(array(
            "etsy_users_id = :etsy_users_id:",
            "bind" => array("etsy_users_id" => $this->currentEtsyUser->id)
        ));
        $watchlistListings = array();
        foreach($watchlists as $watchlist) {
            $watchlistListings[$watchlist->id] = $watchlist->getWatchlistsListings(array(
                'limit' => 4,
                'order' => 'creation DESC'
            ));
        }
        $this->view->watchlists = $watchlists;
        $this->view->watchlistListings = $watchlistListings;
    }

    public function watchlistAction($watchlistId = 0)
    {
        $this->view->setTemplateAfter('main');
        $this->view->etsyUser = $this->currentEtsyUser;
        if($watchlistId) {
            $watchlist = Watchlists::findFirst($watchlistId);
            if($watchlist->etsy_users_id == $this->currentEtsyUser->id) {
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 32;
                $format = isset($_GET['format']) ? (int)$_GET['format'] : 'html';
                $watchlistsListings = $watchlist->getWatchlistsListings(array(
                    "order" => "creation DESC",
                    "limit" => array("number" => $pageSize, "offset" => $offset)
                ));
                if($format == 'json') {
                    $json = '[';
                    foreach($watchlistsListings as $listing){
                        if(strlen($json) > 1) {
                            $json .= ',';
                        }
                        $json .= json_encode($listing);
                    };
                    $json .= ']';
                    echo $json;
                    die();
                }
                $this->view->currentWatchlist = $watchlist;
                $this->view->currentWatchlistParameters = $watchlist->watchlistsParameters;
                $this->view->currentWatchlistListings = $watchlistsListings;
                $this->view->offset = $offset;
            }
        }
        $this->view->watchlists = watchlists::find(array(
            "etsy_users_id = :etsy_users_id:",
            "bind" => array("etsy_users_id" => $this->currentEtsyUser->id)
        ));
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

    public function setlistingsasviewedAction($watchlistId = 0)
    {
        if($watchlistId) {
            $watchlist = Watchlists::findFirst($watchlistId);
            if($watchlist->etsy_users_id == $this->currentEtsyUser->id) {
                $phql = "Update WatchlistsListings SET is_viewed = 1 WHERE WatchlistsListings.watchlists_id = :watchlists_id:";
                $result = $this->modelsManager->executeQuery($phql, array('watchlists_id' => $watchlistId));
            }
            WatchlistsListings::clearWatchlistCache($watchlistId);
        }
        die();
    }

    public function saveAction()
    {
        $watchlistInput = isset($_POST['watchlist']) ? json_decode($_POST['watchlist']) : null;
        $errorMessage = false;
        if($watchlistInput && $watchlistInput->name) {
            $watchlist = new Watchlists();
            $watchlist->etsy_users_id = $this->currentEtsyUser->id;
            if($watchlistInput->id) {
                $watchlist = Watchlists::findFirst(array($watchlistInput->id, "users_id =".$this->currentEtsyUser->id));
            } else {
                $watchlist->last_checked = new Phalcon\Db\RawValue('now()');;
                $watchlist->created = new Phalcon\Db\RawValue('now()');;
            }
            $watchlist->name = $watchlistInput->name;
            $watchlist->email_interval =
            $watchlistInput->email_interval;
            if($watchlist->save() == false) {
                $errorMessage = 'Could not save watchlist: ' . implode(' | ', $watchlist->getMessages());
            } else {
                try {
                    // Set parameters
                    $watchlist->setParameters($watchlistInput->watchlists_parameters);
                    // Set listings if new watchlist
                    $watchlist->setListings($watchlistInput->watchlists_listings);
                    if($watchlistInput->id) {
                        Watchlists::clearGetCache($watchlistInput->id);
                        // Clear WP and WL cache if updated
                    } else {
                        Watchlists::flushCache();
                    }
                    echo $watchlist->id;
                    die();
                } catch(Exception $ex) {
                    if(!$watchlistInput->id) {
                        $watchlist->delete();
                    }
                    $errorMessage = $ex->getMessage();
                }
            }
        }
        if(!$errorMessage) {
            $errorMessage = "Could not save watchlist";
        }
        throw new Exception($errorMessage);
        die();
    }

    public function categoriesAction($categoryName = '')
    {
        $categories = $this->listCategories($categoryName);
        echo json_encode($categories);
        die();
    }

    private function searchListings($keywords, $category, $shipsto) {
        return EtsyApi::searchListings($this->config->api_key, $this->config->api_secret, $this->currentEtsyUser->etsy_token, $this->currentEtsyUser->etsy_secret, $keywords, $category, $shipsto);
    }

    private function listCategories($categoryName = '') {
        return EtsyApi::listCategories($this->config->api_key, $this->config->api_secret, $this->currentEtsyUser->etsy_token, $this->currentEtsyUser->etsy_secret, $categoryName);
    }

    private function listCountries() {
        return EtsyApi::listCountries($this->config->api_key, $this->config->api_secret, $this->currentEtsyUser->etsy_token, $this->currentEtsyUser->etsy_secret);
    }
}
