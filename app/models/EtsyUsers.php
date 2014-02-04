<?php

use Phalcon\Mvc\Model\Validator\Uniqueness as UniquenessValidator;
use Phalcon\Mvc\Model\Relation;

class EtsyUsers extends CacheableModel
{
    public function validation()
    {
        $this->validate(new UniquenessValidator(array(
            'field' => 'etsyid',
            'message' => 'Sorry, That Etsy id is already taken'
        )));
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }

    public function initialize()
    {
        $this->hasMany('id', 'Watchlists', 'etsy_users_id', array(
            'foreignKey' => array(
                'action' => Relation::ACTION_CASCADE
            )
        ));
    }
}
