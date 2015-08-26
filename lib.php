<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @global moodle_database $DB
 * @param object $obj
 * @param mod_dragdrop_mod_form $mform
 * @return integer
 */
function dragdrop_add_instance($obj, mod_dragdrop_mod_form $mform = null) {
    global $DB;
    $obj->timecreated = $obj->timemodified = time();
    $obj->header = (isset($obj->header) && array_key_exists('text', $obj->header)) ? $obj->header['text'] : null;
    $obj->footer = (isset($obj->footer) && array_key_exists('text', $obj->footer)) ? $obj->footer['text'] : null;
    $obj->id = $DB->insert_record('dragdrop', $obj);
    return $obj->id;
}

/**
 * @global moodle_database $DB
 * @param object $obj
 * @param mod_dragdrop_mod_form $mform
 * @return boolean
 */
function dragdrop_update_instance($obj, mod_dragdrop_mod_form $mform) {
    global $DB;
    $obj->id = $obj->instance;
    $obj->timemodified = time();
    $obj->header = (isset($obj->header) && array_key_exists('text', $obj->header)) ? $obj->header['text'] : null;
    $obj->footer = (isset($obj->footer) && array_key_exists('text', $obj->footer)) ? $obj->footer['text'] : null;
    $success = $DB->update_record('dragdrop', $obj);
    return $success;
}

/**
 * @global moodle_database $DB
 * @param integer $id
 * @return boolean
 */
function dragdrop_delete_instance($id) {
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('dragdrop', array('id' => $id));
    $DB->delete_records('dragdrop_word_block', array('dragdropid' => $id));
    $DB->delete_records('dragdrop_sentence', array('instanceid'=>$id));
    $transaction->allow_commit();
    return true;
}

/**
* @param string $feature
* @return boolean
*/
function dragdrop_supports($feature) {
    $support = array(
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_ADVANCED_GRADING => false,
        FEATURE_CONTROLS_GRADE_VISIBILITY => false,
        FEATURE_PLAGIARISM => false,
        FEATURE_COMPLETION_HAS_RULES => false,
        FEATURE_NO_VIEW_LINK => false,
        FEATURE_IDNUMBER => false,
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_ARCHETYPE => false,
        FEATURE_MOD_INTRO => false,
        FEATURE_MODEDIT_DEFAULT_COMPLETION => false,
        FEATURE_COMMENT => false,
        FEATURE_RATE => false,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_SHOW_DESCRIPTION => false,
    );
    if (!array_key_exists($feature, $support)) {
        return null;
    }
    return $support[$feature];
}


/**
 * Returns all other caps used in module
 * @return array
 */
function dragdrop_get_extra_capabilities() {
    return array(
        'moodle/site:accessallgroups',
    );
}
