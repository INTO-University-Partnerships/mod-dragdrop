<?php

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/mod/dragdrop/backup/moodle2/backup_dragdrop_stepslib.php';

class backup_dragdrop_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // empty
    }

    protected function define_my_steps() {
        $this->add_step(new backup_dragdrop_activity_structure_step('dragdrop_activity_structure', 'dragdrop.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // link to the list of pages
        $search = "/(" . $base . "\/mod\/dragdrop\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@DRAGDROPINDEX*$2@$', $content);

        // link to page view by moduleid
        $search = "/(" . $base . "\/mod\/dragdrop\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@DRAGDROPVIEWBYID*$2@$', $content);

        return $content;
    }
}
