<?php

class WatchlistsParameters extends CacheableModel
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
    public $parameters_id;

    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $title;

    public function initialize()
    {
        $this->belongsTo('watchlists_id', 'Watchlists', 'id', array(
            'foreignKey' => array(
                'message' => 'The watchlist does not exist'
            )
        ));
        $this->belongsTo('parameters_id', 'Parameters', 'id', array(
            'foreignKey' => array(
                'message' => 'The parameter does not exist'
            )
        ));
    }

    public static function clearWatchlistCache($watchlistId) {
        $cache = self::getCache();
        if($cache) {
            // TODO: this and like wise in watchlist listings?
        }
    }
}
