<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ElementsTable extends Table
{

    public function initialize(array $config)
    {
        $this->hasMany('CocktailElements')
            ->setForeignKey('element_id');
    }
}