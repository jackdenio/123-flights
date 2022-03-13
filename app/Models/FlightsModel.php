<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use \GuzzleHttp\Exception\RequestException  as GuzzleReqException;

class FlightsModel extends Model
{

    public static function showFlights(){

        try {
            $base = 'http://prova.123milhas.net/api/flights';
            $client = new \GuzzleHttp\Client();
            $request = $client->request('GET', $base);
            $json = $request->getBody()->getContents();
            return $json;
        } catch (GuzzleReqException $e) {
            throw ($e);
        }        

    }

}

  