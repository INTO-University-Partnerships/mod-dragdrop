<?php
namespace dragdrop;

defined('MOODLE_INTERNAL') || die();

class sentence implements entity {

    protected $_get_model;

    public function __construct($get_model) {
        $this->_get_model = $get_model;
    }

    public function get($id) {
        global $DB;
        return $DB->get_record('dragdrop_sentence', array('id' => $id), '*', MUST_EXIST);
    }


    public function get_all($instanceid, $params = null) {
        global $DB;
        $sql = "SELECT sw.id as uniqueid, s.*, w.wordblock, w.id as wordblockid
                FROM {dragdrop_sentence} s
                LEFT JOIN {dragdrop_sentence_word_block} sw
                ON s.id = sw.sentenceid
                LEFT JOIN {dragdrop_word_block} w
                ON w.id = sw.wordblockid
                WHERE s.instanceid = :instanceid
                ORDER BY s.id, sw.position";

        $records = $DB->get_recordset_sql($sql, array('instanceid'=>$instanceid));

        // organise the data
        $sentences = array();
        foreach ($records as $record) {
            if (!array_key_exists($record->id, $sentences)) {
                $sentences[$record->id] = new \stdClass();
                $sentences[$record->id]->wordblocks = array();
                $sentences[$record->id]->id = $record->id;
            }
            if ($record->wordblock !== NULL) {
                $wordblock = array(
                    'wordblock' => $record->wordblock,
                    'wordblockid' => $record->wordblockid
                );
                $sentences[$record->id]->wordblocks[] = (object) $wordblock;
            }
        }
        return $sentences;
    }

    public function save($instanceid, $data, $now, $id=0) {
        global $DB;
        $data['timemodified'] = $now;
        if ($id) {
            $this->must_exist($id);
            $data['id'] = $id;
            $DB->update_record('dragdrop_sentence', (object) $data);
            return $id;
        }
        $data['instanceid'] = $instanceid;
        $data['timecreated'] = $now;
        return $DB->insert_record('dragdrop_sentence', (object) $data);
    }

    public function must_exist($id) {
        global $DB;
        $DB->get_field('dragdrop_sentence', 'id', array(
            'id' => $id,
        ), MUST_EXIST);
    }

    public function delete($id) {
        global $DB;
        $this->must_exist($id);
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('dragdrop_sentence', array('id' => $id));
        $DB->delete_records('dragdrop_sentence_word_block', array('sentenceid' => $id));
        $transaction->allow_commit();
    }
}