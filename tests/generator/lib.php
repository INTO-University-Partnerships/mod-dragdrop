<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');

class mod_dragdrop_generator extends testing_module_generator {

    /**
     * create a new dragdrop instance
     * @throws coding_exception
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass dragdrop course module record
     */
    public function create_instance($record = null, array $options = null) {
        global $DB;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }

        // module data
        $record->modulename = 'dragdrop';
        $record->module = $DB->get_field('modules', 'id', array('name' => 'dragdrop'));
        $record->section = 0;
        $record->visible = 1;

        // instance data
        if (!isset($record->startdate)) {
            $record->startdate = time();
        }
        if (!isset($record->header)) {
            $record->header = array(
                'format' => FORMAT_HTML,
                'text' => '<p>Header goes here</p>'
            );
        }
        if (!isset($record->footer)) {
            $record->footer = array(
                'format' => FORMAT_HTML,
                'text' =>'<p>Footer goes here</p>'
            );
        }
        if (!isset($record->instruction)) {
            $record->instruction = '<p>Instruction goes here</p>';
        }
        if (!isset($record->num_attempts)) {
            $record->num_attempts = 3;
        }
        if (!isset($record->feedback_correct)) {
            $record->feedback_correct = '<p>Feedback goes here</p>';
        }
        if (!isset($record->hint)) {
            $record->hint = '<p>Hint goes here</p>';
        }

        return parent::create_instance($record, $options);
    }

}
