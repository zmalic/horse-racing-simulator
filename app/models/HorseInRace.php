<?php

/**
 * Class Tasks
 * Model for task manipulations
 */
class HorseInRace extends Model
{
    protected static $name = 'horseInRace';
    private static $allowedFields = ['id', 'raceId', 'horseId', 'metersCrossed', 'finishTime'];
    private static $createFields = [
        'raceId',
        'horseId',
        'metersCrossed',
        'finishTime',
    ];

    /**
     * Horse table fields
     */
    public $raceId;
    public $horseId;
    public $metersCrossed = 0;
    public $finishTime = 0;

    private $currentTime;


    public function __construct($data = [])
    {
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
        return ['success'=>count($this->errors) == 0, 'errors'=>$this->errors];
    }

    /**
     * @param $time
     * Adds 10 seconds for a horse in the race
     */
    public function addTenSeconds($time){
        $horse = Horse::getById($this->horseId);
        $this->currentTime = $time;
        for ($i = 0; $i < 10; $i++){
            if(!$this->addSecond($horse)){
                break;
            }
        }
        if( $this->metersCrossed >= 1500){
            $this->metersCrossed = 1500;
            $this->finishTime = $this->currentTime;
            $horse->inRace = false;
            $horse->save();
        }
        $this->save();
    }

    /**
     * @param $horse
     * @return bool
     * Calculate coverage every second
     * If the race is finished returns false else returns true
     */
    private function addSecond($horse){
        $addMeters = 5 + $horse->speed;
        // Slow down
        if($this->metersCrossed > $horse->endurance * 100) {
            // A jockey slows the horse down by 5 m/s, but this effect is reduced by the horse's strength * 8 as a percentage
            $addMeters -= 5 - (5 * $horse->strength * 8) / 100;
        }
        $this->metersCrossed += $addMeters;
        $this->currentTime++;
        return $this->metersCrossed < 1500;
    }

    /**
     * @param $id
     * @return bool|HorseInRace
     * Get task by id
     * if task found, returns HorseInRace object
     */
    public static function getById($id)
    {
        $data = self::getDataById($id);
        return $data === false ? false : new HorseInRace($data);
    }

    /**
     * @param $raceId
     * @param int $limit
     * @return mixed
     * Get ordered horse infos by race ID
     */
    public static function getByRaceId($raceId, $limit = 8){
        $where = 'WHERE `raceId` = ' . intval($raceId);

        // ignore soft deleted fields
        if(SOFT_DELETE) {
            $where .= $where == '' ? ' WHERE' : ' AND';
            $where .= ' `deleted` = 0';
        }

        $name = static::$name;
        $sql = "SELECT `$name`.`id`, `$name`.`horseId`, `horse`.`horseName`, `$name`.`metersCrossed`, `$name`.`finishTime`, ";
        $sql .= "IF(`$name`.`finishTime`=0,1000000,`$name`.`finishTime`) as orderHelper ";
        $sql .= "FROM `$name`  ";
        $sql .= "LEFT JOIN `horse` ON `$name`.`horseId` = `horse`.`id` ";
        $sql .= "{$where} ";
        $sql .= "ORDER BY orderHelper ASC, metersCrossed DESC ";
        $sql .= "LIMIT 0, $limit";
        return Database::instance()->run($sql);
    }

    /**
     * @return mixed
     * Get best result with horse details
     */
    public static function getBestTimeEver(){
        $name = static::$name;
        $sql = "SELECT * ";
        $sql .= "FROM `$name`  ";
        $sql .= "LEFT JOIN `horse` ON `$name`.`horseId` = `horse`.`id` ";
        $sql .= "WHERE finishTime > 0 ";
        $sql .= "ORDER BY finishTime ASC ";
        $sql .= "LIMIT 0, 1";
        $result = Database::instance()->run($sql);
        return $result;
    }
}
