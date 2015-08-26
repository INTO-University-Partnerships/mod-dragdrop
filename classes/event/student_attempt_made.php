<?php

namespace mod_dragdrop\event;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/mod_dragdrop_base_event.php';

class student_attempt_made extends mod_dragdrop_base_event {

    protected function init() {
        $this->data['objecttable'] = 'dragdrop_attempt';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }


    public function get_description() {
        $name = $this->other['module']['mod_instance']['name'];
        $userid = $this->other['entity']['userid'];
        $a = array(
            'name' => $name,
            'userid' => $userid
        );
        return get_string('log:student_attempt_made_description', 'mod_dragdrop', (object) $a);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('log:student_attempt_made', 'mod_dragdrop');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $id = $this->other['entity']['dragdropid'];
        $userid = $this->other['entity']['userid'];
        return new \moodle_url("/dragdrop/{$id}#/attempts/{$userid}");
    }

}
