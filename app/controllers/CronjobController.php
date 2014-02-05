<?php
class CronjobController extends ControllerBase
{
    public function initialize()
    {
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $this->config = $config->etsy;
        $this->parameters = Parameters::find();
    }

    public function indexAction()
    {

    	$watchlists = Watchlists::query()
		    ->where("last_checked < :one_hour_ago:")
		    ->bind(array("one_hour_ago" => time() - 3600))
		    ->limit(10)
		    ->order("last_checked")
		    ->execute();
		foreach($watchlists as $watchlist) {
			$etsyUser = EtsyUsers::findFirst($watchlist->etsy_users_id);
			$keywords = null;
			$category = null;
			$shipsto = null;
			foreach($watchlist->watchlistsParameters as $watchlistsParameter) {
				switch ($this->parameterApiName($watchlistsParameter->parameters_id)) {
					case 'keywords':
						$keywords = implode(',', explode(' ', $watchlistsParameter->value));
						break;
					case 'category':
						$category = $watchlistsParameter->value;
						break;
					case 'shipsto':
						$shipsto = $watchlistsParameter->value;
						break;
					default:
						var_dump($this->parameterApiName($watchlistsParameter->parameters_id));
						break;
				}
			}
			if($keywords) {
				$listings = EtsyApi::searchListings($this->config->api_key, $this->config->api_secret, $etsyUser->etsy_token, $etsyUser->etsy_secret, $keywords, $category, $shipsto);
				$listings = EtsyApi::parseListings($listings);
				$watchlist->setListings($listings);
			}
			$watchlist->last_checked = time();
//			$watchlist->save();
		}
    }

    public function emailAction()
    {

    	$watchlists = Watchlists::query()
		    ->where("email_interval > 0")
		    ->order("last_emailed")
		    ->execute();
		foreach($watchlists as $watchlist) {
			if($watchlist->last_emailed < time() - $watchlist->email_interval) {
				$etsyUser = EtsyUsers::findFirst($watchlist->etsy_user_id);
				$watchlists = WatchlistsListings::query()
				    ->where("watchlists_id > :watchlists_id:")
				    ->andWhere("is_emailed = 0")
				    ->bind(array("watchlists_id" => $watchlist->id))
				    ->order("creation")
				    ->execute();
				// Mark listings as emailed
				$phql = "Update WatchlistsListings SET is_emailed = 1 WHERE WatchlistsListings.watchlists_id = :watchlists_id:";
                $result = $this->modelsManager->executeQuery($phql, array('watchlists_id' => $watchlist->id));
                // Mark watchlist as emailed
				$watchlist->last_emailed = time();
				$watchlist->save();
				// TODO: SEND EMAIL to $etsyUser->email
			}
		}
    }

    private function parameterApiName($parameterId) {
    	foreach ($this->parameters as $parameter) {
    		if($parameter->id == $parameterId) {
    			return $parameter->api_name;
    		}
    	}
    }
}
