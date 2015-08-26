<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../course/moodleform_mod.php';

class mod_dragdrop_mod_form extends moodleform_mod {

    /**
     * definition
     */
    protected function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name
        $mform->addElement('text', 'name', get_string('dragdropname', 'dragdrop'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // header & footer
        foreach (array('header', 'footer') as $element) {
            $mform->addElement('editor', $element, get_string($element, 'dragdrop'), null, array(
                'maxfiles' => 0,
                'maxbytes' => 0,
                'trusttext' => false,
                'forcehttps' => false,
            ));
        }


        $mform->addElement('hidden', 'instruction', "");
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('instruction', PARAM_TEXT);
        } else {
            $mform->setType('instruction', PARAM_CLEANHTML);
        }

        require_once(__DIR__ . '/models/user_attempt.php');
        $mform->addElement('hidden', 'num_attempts', dragdrop\user_attempt::DEFAULT_NUM_ATTEMPTS);
        $mform->setType('num_attempts', PARAM_INT);

        $mform->addElement('hidden', 'feedback_correct', "");
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('feedback_correct', PARAM_TEXT);
        } else {
            $mform->setType('feedback_correct', PARAM_CLEANHTML);
        }

        $mform->addElement('hidden', 'hint', "");
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('hint', PARAM_TEXT);
        } else {
            $mform->setType('hint', PARAM_CLEANHTML);
        }


        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * @param array $default_values
     */
    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $header = $default_values['header'];
            $default_values['header'] = array(
                'format' => FORMAT_HTML,
                'text' => $header,
            );
            $footer = $default_values['footer'];
            $default_values['footer'] = array(
                'format' => FORMAT_HTML,
                'text' => $footer,
            );
        }
    }

}
