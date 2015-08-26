<?php

defined('MOODLE_INTERNAL') || die;

class restore_dragdrop_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('dragdrop', '/activity/dragdrop');
        $paths[] = new restore_path_element('words', '/activity/dragdrop/words');
        $paths[] = new restore_path_element('feedback', '/activity/dragdrop/feedback');
        $paths[] = new restore_path_element('sentences', '/activity/dragdrop/sentences');
        $paths[] = new restore_path_element('sentence_words', '/activity/dragdrop/sentences/sentence_words');
        return $this->prepare_activity_structure($paths);
    }

    protected function process_dragdrop($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timecreated = $data->timemodified = time();
        $newitemid = $DB->insert_record('dragdrop', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_words($data) {
        global $DB;
        $data = (object)$data;
        $data->dragdropid = $this->get_new_parentid('dragdrop');
        $data->timecreated = $data->timemodified = time();
        $newitemid = $DB->insert_record('dragdrop_word_block', $data);
        $oldid = $data->id;
        $this->set_mapping('word_blocks', $oldid, $newitemid);
    }

    protected function process_feedback($data) {global $DB;
        $data = (object)$data;
        $data->dragdropid = $this->get_new_parentid('dragdrop');
        $data->timecreated = $data->timemodified = time();
        $DB->insert_record('dragdrop_feedback', $data);
    }

    protected function process_sentences($data) {
        global $DB;
        $data = (object)$data;
        $data->instanceid = $this->get_new_parentid('dragdrop');
        $data->timecreated = $data->timemodified = time();
        $newitemid = $DB->insert_record('dragdrop_sentence', $data);
        $this->set_mapping('sentences', $data->id, $newitemid);
    }

    protected function process_sentence_words($data) {
        global $DB;
        $data = (object)$data;
        $data->sentenceid = $this->get_new_parentid('sentences');
        $data->wordblockid = $this->get_mappingid('word_blocks', $data->wordblockid);
        $data->timecreated = $data->timemodified = time();
        $DB->insert_record('dragdrop_sentence_word_block', $data);
    }
}
