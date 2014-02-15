<?php
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\Resultset;
class Watchlists extends CacheableModel
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $etsy_users_id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $email_interval;

    /**
     * @var int
     */
    public $last_emailed;

    /**
     * @var int
     */
    public $last_checked;

    /**
     * @var int
     */
    public $created;

    public function initialize()
    {
        $this->belongsTo('etsy_users_id', 'EtsyUsers', 'id', array(
            'foreignKey' => array(
                'message' => 'The etsy user does not exist in the app'
            )
        ));
        $this->hasMany('id', 'WatchlistsParameters', 'watchlists_id', array(
            'foreignKey' => array(
                'action' => Relation::ACTION_CASCADE
            )
        ));
        $this->hasMany('id', 'WatchlistsListings', 'watchlists_id', array(
            'foreignKey' => array(
                'action' => Relation::ACTION_CASCADE
            )
        ));
    }

    public function setListings($listings, &$newListingsCount = 0)
    {
        if(count($listings) == 0) {
            return;
        }
        $lastListingCreation = $listings[count($listings) - 1]->creation;
        $listingsExisting = $this->getWatchlistsListings(array(
            "creation > " . ($lastListingCreation - 1)
        ));
        foreach($listings as $listing) {
            $listingExists = false;
            foreach($listingsExisting as $key=>$listingExisting) {
                if($listingExisting->listing_id == $listing->listing_id ) {
                    $listingExists = true;
                }
            }
            if(!$listingExists) {
                $listingNew = new WatchlistsListings();
                $listingNew->watchlists_id = $this->id;
                $listingNew->listing_id = $listing->listing_id;
                $listingNew->title = $listing->title;
                $listingNew->url = $listing->url;
                $listingNew->image_thumb_url = $listing->image_thumb_url;
                $listingNew->image_url = $listing->image_url;
                $listingNew->shop_loginname = $listing->shop_loginname;
                $listingNew->shop_url = $listing->shop_url;
                $listingNew->currency_code = $listing->currency_code;
                $listingNew->price = $listing->price;
                $listingNew->creation = $listing->creation;
                $listingNew->is_viewed = 0;
                $listingNew->is_emailed = 0;
                if($listingNew->save() == false) {
                    throw new Exception('Could not update listing: ' . implode(' | ', $listingNew->getMessages()));
                }
                $newListingsCount++;
            }
        }
    }

    public function setParameters($parameters)
    {
        $parametersExisting = $this->getWatchlistsParameters();
        $existingOldParameterIndexes = array();
        foreach($parameters as $parameter) {
            $parameterExists = false;
            foreach($parametersExisting as $index=>$parameterExisting) {
                if($parameterExisting->parameters_id == $parameter->parameters_id ) {
                    $parameterExists = true;
                    array_push($existingOldParameterIndexes, $index);
                    $parameterExisting->value = $parameter->value;
                    $parameterExisting->title = $parameter->title;
                    if($parameterExisting->save() == false) {
                        throw new Exception('Could not update parameter: ' . implode(' | ', $parameterExisting->getMessages()));
                    }
                }
            }
            if(!$parameterExists) {
                $parameterNew = new WatchlistsParameters();
                $parameterNew->watchlists_id = $this->id;
                $parameterNew->parameters_id = $parameter->parameters_id;
                $parameterNew->value = $parameter->value;
                $parameterNew->title = $parameter->title;
                if($parameterNew->save() == false) {
                    throw new Exception('Could not create parameter: ' . implode(' | ', $parameterNew->getMessages()));
                }
            }
        }
        foreach($parametersExisting as $index=>$parameterExisting) {
            if(!in_array($index, $existingOldParameterIndexes)) {
                $parameterExisting->delete();
            }
        }
    }
}
