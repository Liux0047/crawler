<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 11/6/2014
 * Time: 2:25 PM
 */

class Distance extends Eloquent{

    protected $table = 'distance';
    protected $primaryKey  = 'distance_id';

    public function source(){
        return $this->belongsTo('Area', 'source');
    }

    public function destination(){
        return $this->belongsTo('Area', 'destination');
    }

} 