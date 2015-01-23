<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/23/2015
 * Time: 12:30 PM
 */
class WalkingRandom extends BaseController
{
    const MATRIX_SIZE = 10;
    const ROW_WIDTH = 168;
    const OFFSET = 15;

    private $size = 9468;
    private $lastGridIndex = 15712;
    private $batch = 0;


    public function getWalkingRandom($sourcePos)
    {
        //select sources and destinations based on input
        $sources = Area::where('grid_index', '>=', $sourcePos)
            ->orderBy('grid_index')
            ->take(10)
            ->get();


        if (!Session::has('destinationArray')) {
            $destinationStart = $sources->first()->grid_index - self::ROW_WIDTH * self::OFFSET - self::OFFSET;
            if ($destinationStart < 0) {
                $destinationStart = 0;
            }

            $destinationEnd = $sources->last()->grid_index + self::ROW_WIDTH * self::OFFSET + self::OFFSET;
            if ($destinationEnd > $this->lastGridIndex) {
                $destinationEnd = $this->lastGridIndex;
            }

            $destinationsAll = Area::where('grid_index', '>=', $destinationStart)
                ->where('grid_index', '<=', $destinationEnd)
                ->orderBy('grid_index')
                ->get();

            foreach ($destinationsAll as $destination) {
                $lowerColIndex = $sources->first()->grid_index - 15;
                $higherColIndex = $sources->last()->grid_index + 15;
                //check for column bond
                if ($destination->grid_index % self::ROW_WIDTH > $lowerColIndex
                    && $destination->grid_index % self::ROW_WIDTH < $higherColIndex) {
                    $destinationItem = array();
                    $destinationItem['grid_index'] = $destination->grid_index;
                    $destinationItem['latitude'] = $destination->latitude;
                    $destinationItem['longitude'] = $destination->longitude;

                    $destinationArray[] = $destinationItem;
                }

            }

            Session::put('destinationArray', $destinationArray);
        }

        $destinationArray = Session::get('destinationArray');
        $destinations = array_splice($destinationArray, 0, 10);
        Session::forget('destinationArray');
        if (count($destinationArray) > 0) {
            Session::put('destinationArray', $destinationArray);

        }


        //form the request URL
        $param['sources'] = $sources;
        $param['destinations'] = $destinations;
        $param['mode'] = 'WALKING';

        $nextSourcePos = $sources->last()->grid_index + 1;
        if (Session::has('destinationArray')) {
            $param['nextUrl'] = asset('map-walking-random/' . $sourcePos);
        } else {
            if ($sourcePos < $this->size) {
                $param['nextUrl'] = asset('map-walking-random/' . $nextSourcePos);
            } else {
                $param['nextUrl'] = NULL;
            }
        }


        return View::make('distance-random-matrix', $param);

    }

    public function recordDistance()
    {
        $sources = json_decode(Input::get('sources'));
        $destinations = json_decode(Input::get('destinations'));

        $row = 0;
        foreach ($sources as $source) {
            $col = 0;
            foreach ($destinations as $destination) {
                $rows = json_decode(Input::get('results'));
                $result = $rows[$row]->elements[$col];
                if ($result->status == "OK") {
                    $distance = new DistanceRandom;
                    $distance->source = $source;
                    $distance->destination = $destination;
                    $distance->mode = Input::get('mode');
                    $distance->distance = $result->distance->value;
                    $distance->duration = $result->duration->value;
                    $distance->batch = $this->batch;
                    $distance->save();
                }
                $col++;
            }
            $row++;
        }

        return 1;

    }

    public function getFlush(){
        Session::flush();
    }


}