<?php

class WatchlistsListings extends Phalcon\Mvc\Model
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $watchlists_id;

    /**
     * @var integer
     */
    public $listing_id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $image_thumb_url;

    /**
     * @var string
     */
    public $image_url;

    /**
     * @var string
     */
    public $shop_loginname;

    /**
     * @var string
     */
    public $shop_url;

    /**
     * @var string
     */
    public $currency_code;

    /**
     * @var string
     */
    public $price;

    /**
     * @var int
     */
    public $creation;

    /**
     * @var int
     */
    public $is_emailed;

    /**
     * @var int
     */
    public $is_viewed;

    public function initialize()
    {
        $this->belongsTo('watchlists_id', 'Watchlists', 'id', array(
            'foreignKey' => array(
                'message' => 'The watchlist does not exist'
            )
        ));
    }
}
