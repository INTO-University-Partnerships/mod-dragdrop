<?php

defined('MOODLE_INTERNAL') || die();

class dragdrop_model_manager {

    /**
     * namespace of the models
     */
    const NSPACE = 'dragdrop';

    /**
     * constructor
     */
    public function __construct() {
        // empty
    }

    /**
     * get an instance of a model
     * @param $name the name of the entity
     * @return mixed
     * @throws coding_exception
     */
    public static function get_model($name) {
        require_once(__DIR__ . '/../models/entity.php');

        // check that the file exists
        $file = __DIR__ . '/../models/' . $name . '.php';
        if (!file_exists($file)) {
            throw new coding_exception('File "'.$file.'" does not exist');
        }
        require_once($file);

        // check that the class exists
        $nsclass = self::NSPACE . "\\{$name}";
        if (!class_exists($nsclass)) {
            throw new coding_exception('Model "'.$name.'" does not exist');
        }

        // check the implementation
        $model = new $nsclass(array('dragdrop_model_manager', 'get_model'));
        if (!$model instanceof dragdrop\entity) {
            throw new coding_exception('Model '.$name.' does not implement entity interface');
        }

        return $model;
    }
}