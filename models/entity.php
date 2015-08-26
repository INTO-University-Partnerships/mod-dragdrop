<?php
namespace dragdrop;

defined('MOODLE_INTERNAL') || die();

interface entity {

    /**
     * dependency injection so models can access each other if required
     * @param $get_model
     */
    public function __construct($get_model);

    /**
     * get all of the records for the given instance
     * @param int $instanceid The module instance
     * @param array $params
     * @return array
     */
    public function get_all($instanceid, $params = null);

    /**
     * get a single instance of the entity
     * @param $id
     * @return mixed
     */
    public function get($id);

    /**
     * create or update an instance
     * @param int $instanceid
     * @param array $data
     * @param int $now
     * @param int $id
     * @return int
     */
    public function save($instanceid, $data, $now, $id=0);

    /**
     * delete an instance
     * @param $id
     * @return mixed
     */
    public function delete($id);

}
