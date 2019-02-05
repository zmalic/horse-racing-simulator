<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/13/2018
 * Time: 10:03 PM
 */

class TasksController
{
    /**
     * @param string $search
     * @param $order
     * @param $direction
     * @param int $start
     * @param int $limit
     * @return mixed
     * Get list of tasks depending on the parameters
     */
    public function search($search='', $order, $direction, $start=0, $limit=5)
    {
        return Tasks::get($search, $start, $limit, $order, $direction);
    }

    /**
     * @param $data
     * @return array
     * Create new task or save existing if id > 0
     */
    public function save($data)
    {
        $task = new Tasks($data);
        return $task->save();
    }

    /**
     * @param $id
     * @return mixed
     * Delete task
     */
    public function delete($id)
    {
        return Tasks::delete($id);
    }

    /**
     * @param $id
     * @param $status
     * @return bool|mixed
     * Change done status of task
     */
    public function status($id, $status)
    {
        $task = Tasks::getById($id);
        if($task !== false){
            return $task->setDoneStatus($status);
        }
        return false;
    }

    /**
     * @return int
     * Returns count of possible undo deletes
     */
    public function undoCount()
    {
        return Tasks::undoCount();
    }

    /**
     * @return int|mixed
     * Undo for the last deleted task
     */
    public function undo()
    {
        return Tasks::undo();
    }
}
