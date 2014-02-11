<?php
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
        $watchlists = $this->currentEtsyUser->watchlists;
        $watchlistListings = array();
        foreach($watchlists as $watchlist) {
            $watchlistListings[$watchlist->id] = $watchlist->getWatchlistsListings(array('limit' => 4));
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
            $watchlistsListings = $watchlist->getWatchlistsListings(array("order" => "creation DESC"));
            $this->view->currentWatchlist = $watchlist;
            $this->view->currentWatchlistParameters = $watchlist->watchlistsParameters;
            $this->view->currentWatchlistListings = $watchlistsListings;
            // Set unviewed to viewed
            $unviewedListings = $watchlistsListings->filter(function($listing){
                if($listing->is_viewed == 0) {
                   return $listing;
                }
            });
            if(count($unviewedListings) > 0) {
                $phql = "Update WatchlistsListings SET is_viewed = 1 WHERE WatchlistsListings.watchlists_id = :watchlists_id:";
                $result = $this->modelsManager->executeQuery($phql, array('watchlists_id' => $watchlist->id));
            }
        }
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

    public function saveAction()
    {
        $watchlistInput = isset($_POST['watchlist']) ? json_decode($_POST['watchlist']) : null;
        $errorMessage = false;
        if($watchlistInput) {
            $watchlist = new Watchlists();
            $watchlist->etsy_users_id = $this->currentEtsyUser->id;
            $watchlist->last_checked = new Phalcon\Db\RawValue('now()');;
            $watchlist->created = new Phalcon\Db\RawValue('now()');;
            if($watchlistInput->id) {
                $watchlist = Watchlists::findFirst(array($watchlistInput->id, "users_id =".$this->currentEtsyUser->id));
            }
            $watchlist->name = $watchlistInput->name;
            if($watchlist->save() == false) {
                $errorMessage = 'Could not watchlist: ' . implode(' | ', $watchlist->getMessages());
            } else {
                try {
                    // Set parameters
                    $watchlist->setParameters($watchlistInput->watchlists_parameters);
                    // Set listings if new watchlist
                    $watchlist->setListings($watchlistInput->watchlists_listings);
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
