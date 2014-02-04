<?php
use Phalcon\Mvc\Model\Relation;
class Watchlists extends Phalcon\Mvc\Model
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

    public function setListings($listings)
    {
        if(count($listings) == 0) {
            return;
        }
        $listingsOld = $this->watchlistsListings->filter(function($listing) {
            if ($listing->creation >= $listings[count($listings)]->creation) {
                return $listing;
            }
        });
        foreach($listings as $listing) {
            $listingExists = false;
            foreach($listingsOld as $listingOld) {
                if($listingsOld->listing_id == $listing->listing_id ) {
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
                if($listingNew->save() == false) {
                    throw new Exception('Could not update listing: ' . implode(' | ', $listingNew->getMessages()));
                }
            }
        }
    }

    public function setParameters($parameters)
    {
        $parametersOld = $this->watchlistsParameters;
        $existingOldParameterIndexes = array();
        foreach($parameters as $parameter) {
            $parameterExists = false;
            foreach($parametersOld as $index=>$parameterOld) {
                if($parametersOld->parameters_id == $parameter->parameter_id ) {
                    $parameterExists = true;
                    array_push($existingOldParameterIndexes, $index);
                    $parametersOld->value = $parameter->value;
                    $parametersOld->title = $parameter->title;
                    if($parametersOld->save() == false) {
                        throw new Exception('Could not update parameter: ' . implode(' | ', $parametersOld->getMessages()));
                    }
                }
            }
            if(!$parameterExists) {
                $parameterNew = new WatchlistsParameters();
                $parameterNew->watchlists_id = $this->id;
                $parameterNew->parameters_id = $parameter->parameters_id;
                $parameterNew->value = $parameter->value;
                if($parameterNew->save() == false) {
                    throw new Exception('Could not create parameter: ' . implode(' | ', $parameterNew->getMessages()));
                }
            }
        }
        foreach($parametersOld as $index=>$parametersOld) {
            if(!in_array($index, $existingOldParameterIndexes)) {
                $parametersOld->delete();
            }
        }
    }
}
