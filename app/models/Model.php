<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/14/2018
 * Time: 6:18 PM
 */

/**
 * Class Model
 * Model abstract class
 * This class should be inherited by all models
 * contains basic common methods for any model
 */
abstract class Model
{
    /**
     * @var Database
     * Instance of database object
     */
    protected $db;

    /**
     * @var int
     * Table ID - if object is new id = 0
     */
    protected $id = 0;

    /**
     * @var array
     * Object data to save
     */
    protected $data = [];

    /**
     * @var array
     * List of errors
     */
    protected $errors = [];

    /**
     * @var array
     * Allowed fields for search
     */
    protected static $searchFields = [];

    /**
     * @var array
     * Allowed fields for order
     */
    protected static $sortFields = [];

    /**
     * @var string
     * Table (model) name
     */
    protected static $name;

    protected function __construct()
    {
        $this->db = Database::instance();
    }

    /**
     * @return mixed
     * Common method for insert and update
     */
    protected function insertOrUpdate()
    {
        if($this->id > 0){
            return $this->db->updateById(static::$name, $this->id, $this->data);
        } else {
            return $this->db->insert(static::$name, $this->data);
        }
    }

    /**
     * @param $id
     * @return mixed
     * removes item by id
     * in case that we use SOFT_DELETE just sets deleted flag to true
     * in other case removes data from table but before that stores data to session
     * (in case that we need undo action)
     */
    public static function delete($id)
    {
        if(SOFT_DELETE) {
            return Database::instance()->updateById(static::$name, $id, ['deleted'=>true, 'deletedAt'=>date('Y-m-d H:i:s')]);
        } else {
            $undo = static::$name.'_undo';
            if(!isset($_SESSION[$undo])){
                $_SESSION[$undo] = [];
            }
            $_SESSION[$undo][] = self::getDataById($id);
            return Database::instance()->deleteById(static::$name, $id);
        }
    }

    /**
     * @param $search
     * @param $offset
     * @param $limit
     * @param string $order
     * @param string $direction
     * @return mixed
     * Searches the table depending on the parameters
     */
    protected static function search($search, $offset, $limit, $order='id', $direction=DESC)
    {
        $where = '';
        $data = [
            'offset'=>$offset,
            'limit'=>$limit
        ];
        $order = in_array($order, static::$sortFields) ? $order : 'id';
        $direction = strtoupper($direction) == ASC ? ASC : DESC; // to be sure it is valid

        if($search != '') {
            $first = true;
            foreach (static::$searchFields as $field) {
                $data[$field] = "%$search%";
                if($first){
                    $where .= ' WHERE (';
                } else {
                    $where .= ' OR';
                }
                $where .= " `$field` LIKE :$field";
                $first = false;
            }
            $where .= ')';
        }

        // ignore soft deleted fields
        if(SOFT_DELETE) {
            $where .= $where == '' ? ' WHERE' : ' AND';
            $where .= ' `deleted` = 0';
        }

        $name = static::$name;
        $sql = "SELECT * FROM `$name`{$where} ORDER BY $order $direction LIMIT :offset, :limit";
        return Database::instance()->run($sql, $data);
    }

    /**
     * @param $id
     * @return bool
     * Returns row data for $id
     */
    protected static function getDataById($id) {
        $name = static::$name;
        $where = '';
        if(SOFT_DELETE) {
            $where .= 'AND `deleted` = 0';
        }
        $sql = "SELECT * FROM `$name` WHERE id = :id {$where} LIMIT 1";
        $data = Database::instance()->run($sql, ['id'=>$id]);
        if(count($data) == 1){
            return $data[0];
        }
        return false;
    }
}
