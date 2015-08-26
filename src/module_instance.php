<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

defined('MOODLE_INTERNAL') || die();

class module_instance {

    /**
     * class instance
     */
    private static $_instance;

    /**
     * the course object
     * @var stdClass
     */
    protected $_course;

    /**
     * the module instance object
     * @var stdClass
     */
    protected $_mod_instance;

    /**
     * course module object
     */
    protected $_cm;

    /**
     * constructor
     */
    private function __construct($id) {
        global $DB;
        if (!$mod_instance = $DB->get_record('dragdrop', array('id'=>$id))) {
            throw new NotFoundHttpException(get_string('moduleinstancedoesnotexist', 'error'));
        }
        if (!$cm = get_coursemodule_from_instance('dragdrop', $id)) {
            throw new NotFoundHttpException(get_string('invalidcoursemodule', 'error'));
        }
        if (!$course = $DB->get_record('course', array('id'=>$mod_instance->course))) {
            throw new NotFoundHttpException(get_string('invalidcourseid', 'error'));
        }
        $this->_cm = $cm;
        $this->_course = $course;
        $this->_mod_instance = $mod_instance;
    }

    /**
     * singleton accessor
     * @param $id
     * @return module_instance
     */
    public static function get_instance($id) {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }
        return new self($id);
    }

    /**
     * get the course from the module instance id
     * @return stdClass
     */
    public function get_course() {
        return $this->_course;
    }

    /**
     * get the instance from the module instance id
     * @return stdClass
     */
    public function get_mod_instance() {
        return $this->_mod_instance;
    }

    /**
     * course module
     * @return stdClass
     */
    public function get_cm() {
        return $this->_cm;
    }

    /**
     * context
     */
    public function get_context() {
        return \context_module::instance($this->_cm->id);
    }

    /**
     *
     * @return array
     */
    public function get_data_for_encoding() {
        return array(
            'course' => (array) $this->_course,
            'mod_instance' => (array) $this->_mod_instance,
            'cmid' => $this->_cm->id
        );
    }
}
