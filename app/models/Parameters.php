<?php

class Parameters extends Phalcon\Mvc\Model
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
    public $apiName;

    /**
     * @var string
     */
    public $valueType;

    public function initialize()
    {
        $this->hasMany('id', 'WatchlistsParameters', 'parameters_id', array(
            'foreignKey' => array(
                'action' => Relation::ACTION_CASCADE
            )
        ));
    }
}
