<?php
namespace dragdrop;

defined('MOODLE_INTERNAL') || die();

class word_block implements entity{

    protected $_get_model;

    public function __construct($get_model) {
        $this->_get_model = $get_model;
    }

    public function get_all($instanceid, $params = null) {
        global $DB;
        return $DB->get_records('dragdrop_word_block', array('dragdropid' => $instanceid), 'timecreated, wordblock');
    }

    public function get($id) {
        global $DB;
        return $DB->get_record('dragdrop_word_block', array('id' => $id), '*', MUST_EXIST);
    }

    public function save($instanceid, $data, $now, $id=0) {
        global $DB;
        $data['timemodified'] = $now;
        $data['wordblock'] = clean_param(trim($data['wordblock']), PARAM_TEXT);
        if ($id) {
            $this->must_exist($id);
            $data['id'] = $id;
            $DB->update_record('dragdrop_word_block', (object)$data);
            return $id;
        }
        $data['dragdropid'] = $instanceid;
        $data['timecreated'] = $now;
        return (integer)$DB->insert_record('dragdrop_word_block', (object)$data);
    }

    public function delete($id) {
        global $DB;
        $this->must_exist($id);
        $transaction = $DB->start_delegated_transaction();

        $counts = $this->get_count_of_words_in_sentences_containing_block($id);
        $DB->delete_records('dragdrop_word_block', array('id'=>$id));
        $DB->delete_records('dragdrop_sentence_word_block', array('wordblockid'=>$id));

        // delete sentences if this was the only block
        foreach ($counts as $sentenceid => $count) {
            if ($count == 1) {
                $DB->delete_records('dragdrop_sentence', array('id' => $sentenceid));
            }
        }

        $transaction->allow_commit();
    }

    protected function get_count_of_words_in_sentences_containing_block($blockid) {
        global $DB;
        $sql = "SELECT a.id, a.sentenceid, a.wordblockid
                FROM {dragdrop_sentence_word_block} a
                JOIN {dragdrop_sentence_word_block} b
                ON a.sentenceid=b.sentenceid
                WHERE b.wordblockid=:id";
        $records = $DB->get_records_sql($sql, array('id' => $blockid));

        $sentences = array();
        foreach ($records as $record) {
            if (!array_key_exists($record->sentenceid, $sentences)) {
                $sentences[$record->sentenceid] = 0;
            }
            $sentences[$record->sentenceid]++;
        }
        return $sentences;
    }

    /**
     * Ensures that a word block with the given id exists
     * @param $id
     */
    public function must_exist($id) {
        global $DB;
        $DB->get_field('dragdrop_word_block', 'id', array(
            'id' => $id,
        ), MUST_EXIST);
    }
}