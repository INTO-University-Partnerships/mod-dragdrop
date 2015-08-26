<?php

defined('MOODLE_INTERNAL') || die;

class backup_dragdrop_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $dragdrops = new backup_nested_element('dragdrop', array('id'), array(
            'name',
            'startdate',
            'header',
            'footer',
            'num_attempts',
            'instruction',
            'display_labels',
            'feedback_correct',
            'hint'
        ));
        $words = new backup_nested_element('words', array('id'), array(
            'wordblock',
            'tagid'
        ));
        $sentences = new backup_nested_element('sentences', array('id'), array(
            'mark'
        ));
        $sentence_words = new backup_nested_element('sentence_words', array('id'), array(
            'position',
            'xcoord',
            'ycoord',
            'wordblockid'
        ));
        $feedback = new backup_nested_element('feedback', array('id'), array(
            'attempt',
            'feedback'
        ));
        $dragdrops->set_source_table('dragdrop', array('id' => backup::VAR_ACTIVITYID));
        $dragdrops->add_child($words);
        $dragdrops->add_child($sentences);
        $dragdrops->add_child($feedback);
        $words->set_source_table('dragdrop_word_block', array('dragdropid' => backup::VAR_PARENTID));
        $sentences->set_source_table('dragdrop_sentence', array('instanceid' => backup::VAR_PARENTID));
        $feedback->set_source_table('dragdrop_feedback', array('dragdropid' => backup::VAR_PARENTID));
        $sentences->add_child($sentence_words);
        $sentence_words->set_source_table('dragdrop_sentence_word_block', array('sentenceid' => backup::VAR_PARENTID));
        return $this->prepare_activity_structure($dragdrops);
    }
}
