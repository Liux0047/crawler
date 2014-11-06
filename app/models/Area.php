<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 11/6/2014
 * Time: 2:16 PM
 */

class Area extends Eloquent{

    protected $table = 'area';
    protected $primaryKey  = 'area_id';

    public function distances () {
        return $this->hasMany('Distance', 'source');
    }

} 