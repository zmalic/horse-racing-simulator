<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/14/2018
 * Time: 12:08 AM
 */

/**
 * Class Tasks
 * Model for task manipulations
 */
class Tasks extends Model
{
    protected static $searchFields = ['title', 'description'];
    protected static $sortFields = ['createdAt', 'priority', 'dueDate'];
    protected static $name = 'tasks';
    private static $allowedFields = ['id', 'title', 'description', 'priority', 'done', 'dueDate'];
    private static $createFields = [
        'title',
        'description',
        'priority',
        'done',
        'dueDate',
        'createdAt',
        'updatedAt',
        'completedAt',
        'deleted'
    ];

    /**
     * Task table fields
     */
    public $title;
    public $description;
    public $priority;
    public $done;
    public $dueDate;
    public $createdAt;
    public $updatedAt;
    public $completedAt;
    public $deleted;

    /**
     * @var bool
     * Is done changed or not
     */
    private $changedDoneFlag = false;

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
     * @param $search
     * @param int $offset
     * @param int $limit
     * @param string $order
     * @param string $direction
     * @return mixed
     * Returns results of searching
     */
    public static function get($search, $offset=0, $limit=10, $order='id', $direction=DESC)
    {
        return parent::search($search, $offset, $limit, $order, $direction);
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
            if(!$this->validate($field)) {
                continue;
            }
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
     * @param $field
     * @return bool
     * Simple validation by fields
     */
    private function validate($field)
    {
        switch ($field){
            case 'title':
                $len = strlen($this->$field);
                if ($len < 3 || $len > 20){
                    $this->errors[] = "Title must be between 3 and 20 characters long";
                    return false;
                }
                break;
            case 'description':
                $len = strlen($this->$field);
                if ($len > 300){
                    $this->errors[] = "Description is too long";
                    return false;
                }
                break;
            case 'dueDate':
                $dueDate = new DateTime($this->$field);
                $now = new DateTime();
                if($dueDate < $now ) {
                    $this->errors[] = "The due date can not be in the past.";
                    return false;
                }
                break;
            case 'createdAt':
                if($this->id == 0){
                    $this->$field = date('Y-m-d H:i:s');
                } else {
                    return false;
                }
                break;
            case 'updatedAt':
                if($this->id != 0 && !$this->changedDoneFlag){
                    $this->$field = date('Y-m-d H:i:s');
                } else {
                    return false;
                }
                break;
            case 'completedAt':
            case 'deleted':
                return false;
                break;

        }
        return true;
    }

    /**
     * @param $status
     * @return mixed
     * Change done status of the task
     */
    public function setDoneStatus($status)
    {
        $this->done = $status;
        $this->data = ['done'=>$this->done];
        if($status) {
            $this->completedAt = date('Y-m-d H:i:s');
            $this->data['completedAt'] = $this->completedAt;
        }
        return $this->insertOrUpdate();
    }

    /**
     * @param $id
     * @return bool|Tasks
     * Get task by id
     * if task found, returns Tasks object
     */
    public static function getById($id)
    {
        $data = self::getDataById($id);
        return $data === false ? false : new Tasks($data);
    }

    /**
     * @return int
     * Returns count of possible undo deletes
     */
    public static function undoCount(){
        if(SOFT_DELETE) {
            $name = static::$name;
            $sql = "SELECT `id` FROM `$name` WHERE `deleted` = 1";
            $data = Database::instance()->run($sql);
            if($data !== false) {
                return count($data);
            }
        } elseif(isset($_SESSION['tasks_undo'])) {
            return count($_SESSION['tasks_undo']);
        }
        return 0;
    }

    /**
     * @return int|mixed
     * Undo for the last deleted task
     */
    public static function undo(){
        if(SOFT_DELETE) {
            $name = static::$name;
            $sql = "SELECT `id` FROM `$name` WHERE `deleted` = 1 ORDER BY `deletedAt` LIMIT 1 ";
            $data = Database::instance()->run($sql);
            if($data !== false) {
                $id = $data[0]->id;
                return Database::instance()->updateById(static::$name, $id, ['deleted'=>false]);
            }
        } elseif(isset($_SESSION['tasks_undo']) && count($_SESSION['tasks_undo']) > 0) {
            $last = array_pop($_SESSION['tasks_undo']);
            $last->id = 0;
            $task =new Tasks($last);
            $response = $task->save();
            return $response['success'];
        }
        return 0;
    }
}
