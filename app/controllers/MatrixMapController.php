<?php

/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 11/6/2014
 * Time: 2:15 PM
 */
class MatrixMapController extends BaseController
{
    const MATRIX_SIZE = 10;
    private $size = 986;

    public function getReadFile()
    {
        $dataStr = file_get_contents(public_path() . DIRECTORY_SEPARATOR . 'sub_coord_min.json');
        $dataArray = json_decode($dataStr, true);

        foreach ($dataArray as $name => $data) {
            $coordinates = explode(",", $data);
            $area = new Area;
            $area->area_name = $name;
            $area->latitude = $coordinates[0];
            $area->longitude = $coordinates[1];
            $area->save();
        }
    }

    public function getMapMatrix($sourcePos, $destinationPos)
    {
        //select sources and destinations based on input
        $sources = Area::where('area_id', '>=', $sourcePos * self::MATRIX_SIZE)
            ->where('area_id', '<', $sourcePos * self::MATRIX_SIZE + self::MATRIX_SIZE)
            ->get();

        $destinations = Area::where('area_id', '>=', $destinationPos * self::MATRIX_SIZE)
            ->where('area_id', '<', $destinationPos * self::MATRIX_SIZE + self::MATRIX_SIZE)
            ->get();

        //form the request URL
        //$content = $this->requestGoogleMap($sources, $destinations, "transit");
        $param['sources'] = $sources;
        $param['destinations'] = $destinations;
        $param['mode'] = 'WALKING';
        if ($destinationPos <= $this->size) {
            $nextDestinationPos = $destinationPos + 1;
            $nextSourcePos = $sourcePos;
        } else {
            $nextDestinationPos = 0;
            $nextSourcePos = $sourcePos + 1;
        }

        if ($sourcePos <= $this->size) {
            $param['nextUrl'] = asset('map-matrix/' . $nextSourcePos . '/' . $nextDestinationPos);
        } else {
            $param['nextUrl'] = NULL;
        }

        return View::make('distance-matrix', $param);

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
                if ($result->status == "OK"){
                    $distance = new Distance;
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


}
