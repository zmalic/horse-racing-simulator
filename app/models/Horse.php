<?php

/**
 * Class Tasks
 * Model for task manipulations
 */
class Horse extends Model
{
    protected static $searchFields = ['inRace'];
    protected static $sortFields = ['id', 'endurance'];
    protected static $name = 'horse';
    private static $allowedFields = ['id', 'horseName', 'speed', 'strength', 'endurance', 'inRace'];
    private static $createFields = [
        'horseName',
        'speed',
        'strength',
        'endurance',
        'inRace'
    ];

    /**
     * Horse table fields
     */
    public $horseName;
    public $speed;
    public $strength;
    public $endurance;
    public $inRace;


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
     * @param $id
     * @return bool|Horse
     * Get task by id
     * if task found, returns Horse object
     */
    public static function getById($id)
    {
        $data = self::getDataById($id);
        return $data === false ? false : new Horse($data);
    }

    /**
     * @return mixed
     * Get eight random horses that are still not in the active race
     */
    public static function GetRandomEight(){
        $data = [
            'offset'=>0,
            'limit'=>8
        ];

        $where = 'WHERE `inRace` = 0';

        // ignore soft deleted fields
        if(SOFT_DELETE) {
            $where .= $where == '' ? ' WHERE' : ' AND';
            $where .= ' `deleted` = 0';
        }

        $name = static::$name;
        $sql = "SELECT * FROM `$name`{$where} ORDER BY RAND() LIMIT :offset, :limit";
        return Database::instance()->run($sql, $data);
    }
}
