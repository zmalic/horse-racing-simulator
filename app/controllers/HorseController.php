<?php

class HorseController
{

    /**
     * @return mixed
     * Get the horse with the best time ever
     */
    public function BestTimeEver(){
        return HorseInRace::getBestTimeEver();
    }
}
