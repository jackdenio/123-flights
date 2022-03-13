<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FlightsModel;

class FlightsController extends Controller
{  

    public function organizeFlights(){
    
        $json = FlightsModel::showFlights();

        if ($json) {

            $flightsCollection = collect(json_decode($json, true));
            $inbound = $flightsCollection->where('outbound', false);
            $outbound = $flightsCollection->where('inbound', false);

            $flightGrouping = $this->getFlightsGrouping($outbound, $inbound, $flightsCollection);          

            return [
                "status"=>true, 
                //"flights"=>$flightsCollection,
                "groups"=>$flightGrouping['groups'],
                "totalGroups"=>$flightGrouping['totalGroups'],
                "totalFlights"=>$flightGrouping['totalFlights'],
                "cheapestPrice"=>$flightGrouping['cheapestPrice'],
                "cheapestGroup"=>$flightGrouping['cheapestGroup']
            ];

        } else {

            return [
                "status"=>false, 
                "erros"=> "Oops, devido ao mau tempo em nossa estação de dados não foi possível recuperar nenhum voo no momento =[."
            ];            
        }

    }

    private function getFlightsGrouping($outbound, $inbound, $flightsCollection)
    {
        if (!empty($outbound) || !empty($inbound)) {

        $outboundDatas = $this->getFlightsIDs($outbound);
        $inboundDatas = $this->getFlightsIDs($inbound);

        $grouping = $this->buildGrouping($outboundDatas, $inboundDatas);
        $flightsGrouping = $this->buildFlightsGroupings($grouping);

        return $flightsGrouping;

        } else {

            return [
                "status"=>false, 
                "erros"=> "Parece que uma nevasca está impedindo que acessemos nossa nuvem de dados. Gentileza, tente mais tarde."
            ];            
        }            
    }
    
    
    private function getFlightsIDs($flights)
    {
        $flightsIDs = array();

        foreach ($flights as $flight) {
            $flightsIDs[$flight['fare']][$flight['price']][] = array('id' => $flight['id']);
        }

        return $flightsIDs;
    }
    
    private function buildGrouping($outboundDatas, $inboundDatas)
    {
        $grouping = array();
        $groupID = 0;

        foreach ($outboundDatas as $fare => $outboundFare) {
            $inboundFare = $inboundDatas[$fare];
            $groupingFare = $this->buildGroupingFare($groupID, $outboundFare, $inboundFare);
            $grouping = array_merge($grouping, $groupingFare);

        }

        if (!empty($grouping)) {
            usort($grouping, function($groupA, $groupB) { 
                return ($groupA['totalPrice'] <=> $groupB['totalPrice']); 
            });	

        } else {

            return [
                "status"=>false, 
                "erros"=> "Voos indisponíveis no momento. Gentileza tentar mais tarde !"
            ];            
        }  

        return $grouping;
    }
    
    private function buildGroupingFare($groupID, $outboundFare, $inboundFare)
    {
        $groupingFare = array();
        $outboundPrices = array_keys($outboundFare);
        $inboundPrices = array_keys($inboundFare);

        foreach ($outboundPrices as $outboundPrice) {
            foreach ($inboundPrices as $inboundPrice) {
                $grouping = array();
                $grouping['uniqueId'] = ++$groupID;
                $grouping['totalPrice'] = $outboundPrice + $inboundPrice;
                $grouping['outbound'] = $outboundFare[$outboundPrice];
                $grouping['inbound'] = $inboundFare[$inboundPrice];

                $groupingFare[] = $grouping;
            }
        }

        return $groupingFare;
    }
    
    private function buildFlightsGroupings($grouping)
    {
        $flightGrouping = array();

        $flightGrouping['groups'] = $grouping;
        $flightGrouping['totalGroups'] = count($grouping);
        $flightGrouping['totalFlights'] = $this->getTotalUsedFlights($grouping);
        $flightGrouping['cheapestPrice'] = $grouping[0]['totalPrice'];
        $flightGrouping['cheapestGroup'] = $grouping[0]['uniqueId'];

        return $flightGrouping;
    }
    
    private function getTotalUsedFlights($grouping)
    {
        $usedFlights = array();

        foreach ($grouping as $group) {
            $outbound = array_map(function($flight) { return $flight['id']; }, $group['outbound']);
            $inbound = array_map(function($flight) { return $flight['id']; }, $group['inbound']);
            
            $usedFlights = array_merge($usedFlights, $outbound, $inbound);
        }

        $usedFlights = array_unique($usedFlights);

        return count($usedFlights);
    }    


}
