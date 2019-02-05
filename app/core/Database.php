<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/13/2018
 * Time: 11:49 PM
 */

/**
 * Wraper around PDO
 */
class Database
{
    /**
     * @var PDO
     * Original PDO Object
     * Public - in case we need some native PDO functionality
     */
    public $pdo;

    /**
     * @var
     * Only one instance per http request
     */
    protected static $instance;

    protected function __construct()
    {
        $options = [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
        ];
        $this->pdo = new PDO(DSN, DB_USER, DB_PASS, $options);
    }

    /**
     * @return Database
     * Use only one instance per http request (application)
     */
    public static function instance()
    {
        if (self::$instance === null)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $sql
     * @param array $args
     * @return mixed
     * Run prepared statements
     */
    public function run($sql, $args = [])
    {
        $sql = trim($sql);
        $isSelect = strtolower(substr( $sql, 0, 6 )) === "select";
        $isInsert = strtolower(substr( $sql, 0, 6 )) === "insert";
        $stmt = $this->pdo->prepare($sql);

        $assoc = $this->isAssoc($args);
        foreach ($args as $key => $val) {
            $parameter = $assoc ? ":$key" : $key + 1;
            // for some reason bool param does not work so we have to convert it to int
            $val = is_bool($val) ? ($val ? 1 : 0) : $val;
            $dataType = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($parameter, $val, $dataType);
        }
//        error_log($stmt->queryString);
//        error_log(json_encode($args));
        if($stmt->execute()) {
            if($isSelect){
                return $stmt->fetchAll();
            }
            if($isInsert){
                return $this->pdo->lastInsertId();
            }
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * @param $table
     * @param string $where
     * @param array $bind
     * @param int $limit
     * @return mixed
     * Delete items from $table depending on the parameters
     */
    public function delete($table, $where='', $bind=[], $limit = 0)
    {
        $where = $where == '' ? '' : " WHERE $where";
        $limit = intval($limit);
        $limit = $limit > 0 ? " LIMIT $limit" : '';
        $sql = "DELETE FROM `$table`{$where}{$limit};";
        return $this->run($sql, $bind);
    }

    /**
     * @param $table
     * @param $id
     * @return mixed
     * Delete item from $table where id is $id
     */
    public function deleteById($table, $id)
    {
        return $this->delete($table, "`id` = ?", [intval($id)], 1);
    }

    /**
     * @param $table
     * @param $data
     * @return mixed
     * insert new data into $table
     */
    public function insert($table, $data)
    {
        $fields = implode(array_keys($data), "`, `");
        $values = implode(array_keys($data), ", :");
        $sql = "INSERT INTO `$table` (`{$fields}`) VALUES (:{$values})";
        return $this->run($sql, $data);
    }

    /**
     * @param $table
     * @param $where
     * @param $whereParams
     * @param $data
     * @return mixed
     * update $table depending on the parameters
     */
    public function update($table, $where, $whereParams, $data)
    {
        $where = $where == '' ? '' : " WHERE $where";
        $values = '';
        $first = true;
        foreach ($data as $key=>$val) {
            $values .= $first ? '' : ', ';
            $values .= "$key=:$key";
            $first = false;
        }
        $sql = "UPDATE `$table` SET {$values}{$where}";
        return $this->run($sql, $whereParams + $data);
    }

    /**
     * @param $table
     * @param $id
     * @param $data
     * @return mixed
     * Update $table where id is $id
     */
    public function updateById($table, $id, $data)
    {
        return $this->update($table, '`id` = :id', ['id' => intval($id)], $data);
    }

    /**
     * @param array $arr
     * @return bool
     * Helper function
     * checks is array associative or not
     */
    private function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
