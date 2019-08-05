<?php

class RaceController
{
    /**
     * @return array
     * Create race
     */
    public function Create(){
        // If three active race exists - return error
        if(Race::threeActiveExists()){
            return ['success'=>false, 'errors'=>["There are three active races already exists"]];
        }
        $race = new Race();
        return $race->save();
    }

    /**
     * @return array
     * Get the  active race info
     */
    public function GetActive(){
        return Race::getActiveRaces();
    }

    /**
     * @return array
     * Get stats for last five races
     * First three places for every race
     */
    public function GetLastFive(){
        return Race::getLastFiveResults();
    }

    /**
     * @return bool
     * Move horses for every active race for 10 seconds
     */
    public function Progress(){
        return Race::progress();
    }
}
