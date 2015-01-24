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


    public function getWalkingRandom($sourcePos, $destinationPos)
    {
        //select sources and destinations based on input
        $source = Area::where('grid_index', '>=', $sourcePos)
            ->orderBy('grid_index')
            ->first();


        if ($destinationPos == 0) {
            $destinationStart = $source->grid_index - self::ROW_WIDTH * self::OFFSET - self::OFFSET;
            if ($destinationStart < 0) {
                $destinationStart = 0;
            }
        } else {
            $destinationStart = $destinationPos;
        }


        $destinationEnd = $source->grid_index + self::ROW_WIDTH * self::OFFSET + self::OFFSET;
        if ($destinationEnd > $this->lastGridIndex) {
            $destinationEnd = $this->lastGridIndex;
        }

        $destinationsAll = Area::where('grid_index', '>=', $destinationStart)
            ->where('grid_index', '<=', $destinationEnd)
            ->orderBy('grid_index')
            ->get();


        $destinationArray = array();

        foreach ($destinationsAll as $destination) {
            $lowerColIndex = $source->grid_index % self::ROW_WIDTH - self::OFFSET;
            $higherColIndex = $source->grid_index % self::ROW_WIDTH + self::OFFSET;
            //check for column bound
            if ($destination->grid_index % self::ROW_WIDTH > $lowerColIndex
                && $destination->grid_index % self::ROW_WIDTH < $higherColIndex
            ) {
                $destinationItem = array();
                $destinationItem['grid_index'] = $destination->grid_index;
                $destinationItem['latitude'] = $destination->latitude;
                $destinationItem['longitude'] = $destination->longitude;

                $destinationArray[] = $destinationItem;
            }

        }


        $nextSourcePos = $source->grid_index + 1;

        $destinations = array_slice($destinationArray, 0, 10);


        $isDestinationEmpty = false;
        if (count($destinationArray) > 10) {
            $nextDestinationPos = $destinationArray[10]['grid_index'];
        } else {
            $isDestinationEmpty = true;
        }


        if (count($destinations) == 0) {
            // if empty destinations

            return Redirect::to('map-walking-random/' . $nextSourcePos . '/0');
        } else {


        }

        //form the request URL
        $param['source'] = $source;
        $param['destinations'] = $destinations;
        $param['mode'] = 'WALKING';

        if (!$isDestinationEmpty) {
            $param['nextUrl'] = asset('map-walking-random/' . $sourcePos . '/' . $nextDestinationPos);
        } else {
            if ($sourcePos < $this->size) {
                $param['nextUrl'] = asset('map-walking-random/' . $nextSourcePos . '/0');
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
                    $distance->save();
                }
                $col++;
            }
            $row++;
        }

        return 1;

    }

    public function getFlush()
    {
        Session::flush();
    }


}