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
    public $users_id;

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
        $this->belongsTo('etsyusers_id', 'EtsyUsers', 'id', array(
            'foreignKey' => array(
                'message' => 'The etsy user does not exist in the app'
            )
        ));
        $this->hasMany('id', 'WatchlistsParameters', 'watchlists_id', array(
            'foreignKey' => array(
                'action' => Relation::ACTION_CASCADE
            )
        ));
    }
}
