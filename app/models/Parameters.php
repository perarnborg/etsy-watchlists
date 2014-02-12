<?php
use Phalcon\Mvc\Model\Relation;
class Parameters extends CacheableModel
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $api_name;

    /**
     * @var string
     */
    public $value_type;

    public function initialize()
    {
        $this->hasMany('id', 'WatchlistsParameters', 'parameters_id', array(
            'foreignKey' => array(
                'action' => Relation::ACTION_CASCADE
            )
        ));
    }
}
