<?php

/**
 * Class Tasks
 * Model for task manipulations
 */
class Race extends Model
{
    protected static $searchFields = ['finished'];
    protected static $sortFields = ['id'];
    protected static $name = 'race';
    private static $allowedFields = ['id', 'time', 'finished'];
    private static $createFields = [
        'time',
        'finished',
    ];

    /**
     * Race table fields
     */
    public $time;
    public $finished;


    public function __construct( $data = [])
    {
        if(!key_exists('time', $data)){
            $data['time'] = 0;
        }
        if(!key_exists('finished', $data)){
            $data['finished'] = false;
        }
        parent::__construct();
        foreach ($data as $key => $val) {
            if(in_array($key, static::$allowedFields)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * @return array
     * Saves prepared data to database
     */
    public function save()
    {
        $this->data = [];
        $this->errors = [];
        foreach (static::$createFields as $field) {
            $this->data[$field] = $this->$field;
        }

        $isNew = $this->id == 0;

        if(count($this->errors) == 0) {
            $result = $this->insertOrUpdate();
            if($result){
                if($this->id == 0) {
                    $this->id = intval($result);
                }
            } else {
                $this->errors[] = "Unable to save";
            }
        }

        // if the race is just created - add horses
        if($isNew && count($this->errors)==0){
            $randomHorses = Horse::GetRandomEight();
            foreach ($randomHorses as $horseInfo){
                $horse = new Horse($horseInfo);
                $horse->inRace = true;
                $horse->save();
                $horseInRace = new HorseInRace([
                    'raceId' => $this->GetId(),
                    'horseId' => $horse->GetId()
                ]);
                $horseInRace->save();
            }
        }
        return ['success'=>count($this->errors) == 0, 'errors'=>$this->errors];
    }

    /**
     * @return bool
     * Checks if three active races already exists
     */
    public static function threeActiveExists(){
        $where = 'WHERE `finished` = 0';

        // ignore soft deleted fields
        if(SOFT_DELETE) {
            $where .= $where == '' ? ' WHERE' : ' AND';
            $where .= ' `deleted` = 0';
        }

        $name = static::$name;
        $sql = "SELECT COUNT(*) as cnt FROM `$name` {$where}";
        $data = Database::instance()->run($sql);
        if(count($data) == 1){
            return intval($data[0]->cnt) >= 3;
        }
        return true;
    }
    /**
     * @param $id
     * @return bool|Race
     * Get task by id
     * if task found, returns Race object
     */
    public static function getById($id)
    {
        $data = self::getDataById($id);
        return $data === false ? false : new Race($data);
    }


    /**
     * @return array
     * Get the  active race info
     */
    public static function getActiveRaces(){
        $where = 'WHERE `finished` = 0';

        // ignore soft deleted fields
        if(SOFT_DELETE) {
            $where .= $where == '' ? ' WHERE' : ' AND';
            $where .= ' `deleted` = 0';
        }

        $name = static::$name;
        $sql = "SELECT `id`, `time`, `finished` FROM `$name` {$where} ORDER BY `id` DESC";
        $data = Database::instance()->run($sql);
        $races = [];
        foreach ($data as $race){
            $race->horses = HorseInRace::getByRaceId($race->id);
            $races[] = $race;
        }
        return $races;
    }


    /**
     * @return array
     * Get stats for last five races
     * First three places for every race
     */
    public static function getLastFiveResults(){
        $where = 'WHERE `finished` = 1';

        // ignore soft deleted fields
        if(SOFT_DELETE) {
            $where .= $where == '' ? ' WHERE' : ' AND';
            $where .= ' `deleted` = 0';
        }

        $name = static::$name;
        $sql = "SELECT `id`, `time`, `finished` FROM `$name` {$where} ORDER BY `id` DESC";
        $data = Database::instance()->run($sql);
        $races = [];
        foreach ($data as $race){
            $race->horses = HorseInRace::getByRaceId($race->id, 3);
            $races[] = $race;
        }
        return $races;
    }

    /**
     * @return bool
     * Move horses for every active race for 10 seconds
     */
    public static function progress(){
        $activeRaces = self::getActiveRaces();

        foreach ($activeRaces as $raceInfo){
            $race = new Race($raceInfo);
            $race->finished = true;
            foreach ($raceInfo->horses as $horseInfo){
                if($horseInfo->finishTime == 0){
                    $horseInRace = HorseInRace::getById($horseInfo->id);
                    $horseInRace->addTenSeconds($race->time);
                    $race->finished &= $horseInRace->finishTime > 0;
                }
            }
            $race->time += 10;
            $race->save();
        }

        return true;
    }
}
