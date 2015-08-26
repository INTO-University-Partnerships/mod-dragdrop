<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/dragdrop/backup/moodle2/restore_dragdrop_stepslib.php';

class restore_dragdrop_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // empty
    }

    protected function define_my_steps() {
        $this->add_step(new restore_dragdrop_activity_structure_step('dragdrop_activity_structure', 'dragdrop.xml'));
    }

    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('dragdrop', array(
            'header',
            'footer',
            'instruction',
            'feedback_correct',
            'hint'
        ));
        $contents[] = new restore_decode_content('dragdrop_feedback', array(
            'feedback'
        ));
        return $contents;
    }

    static public function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('DRAGDROPVIEWBYID', '/mod/dragdrop/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('DRAGDROPINDEX', '/mod/dragdrop/index.php?id=$1', 'course');
        return $rules;
    }
}
