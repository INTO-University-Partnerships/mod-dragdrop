<?php
namespace dragdrop;

defined('MOODLE_INTERNAL') || die();

class sentence_words implements entity {

    protected $_get_model;

    public function __construct($get_model) {
        $this->_get_model = $get_model;
    }

    /**
     * Get a sentence of word blocks
     * @param $id
     * @return array
     */
    public function get($id) {
        global $DB;
        $wordblocks = $DB->get_records('dragdrop_sentence_word_block',
            array('sentenceid' => $id),
            'position',
            'id, wordblockid, xcoord, ycoord');
        $return = new \stdClass();
        $return->sentence = $DB->get_record('dragdrop_sentence', array('id' => $id));
        array_walk($wordblocks, function(&$block) {
            $block->left = $block->xcoord;
            $block->top = $block->ycoord;
            unset($block->xcoord);
            unset($block->ycoord);
        });
        $return->wordblocks = array_values($wordblocks);
        return $return;
    }

    /**
     * Get all word blocks for all sentences in the activity
     * @param $instanceid
     * @param $params
     * @return array
     */
    public function get_all($instanceid, $params = null) {
        global $DB;
        $sql = "SELECT sw.*
                FROM {dragdrop_sentence_word_block} sw
                JOIN {dragdrop_sentence} s
                ON s.id = sw.sentenceid
                WHERE dragdropid = :id";
        return $DB->get_records_sql($sql, array('id' => $instanceid));
    }


    public function save($instanceid, $data, $now, $id = 0) {
        $sentence_model = call_user_func($this->_get_model, 'sentence');
        if (!$id) {
            $sentence = array(
                'mark' => 0
            );
            $id = $sentence_model->save($instanceid, $sentence, $now);
        } else {
            $sentence_model->must_exist($id);
        }
        $this->create_sentence_of_words($data['wordblocks'], $id, $now);
        return $id;
    }

    protected function create_sentence_of_words($words, $sentenceid, $now) {
        global $DB;

        //existing word blocks
        $existing = $DB->get_records(
            'dragdrop_sentence_word_block',
            array('sentenceid' => $sentenceid),
            '',
            'wordblockid, id, position, xcoord, ycoord, sentenceid'
        );

        foreach ($words as $word) {
            $todb = array(
                'position' => (int)$word->position,
                'xcoord' => (int)$word->left,
                'ycoord' => (int)$word->top,
                'wordblockid' => (int)$word->wordblockid
            );
            // if this word block already belongs to this sentence
            if (array_key_exists($word->wordblockid, $existing)) {

                // if values have changed
                if (array_diff_assoc($todb, (array)$existing[$word->wordblockid])) {
                    $todb['id'] = $existing[$word->wordblockid]->id;
                    $DB->update_record('dragdrop_sentence_word_block', (object)$todb);
                }
                unset($existing[$word->wordblockid]);
            } else {
                $todb['sentenceid'] = $sentenceid;
                $todb['timecreated'] = $now;
                $DB->insert_record('dragdrop_sentence_word_block', (object)$todb);
            }
        }

        // delete any existing word blocks not submitted
        if (!empty($existing)) {
            $in = implode(',', array_keys($existing));
            $DB->delete_records_select(
                'dragdrop_sentence_word_block',
                'sentenceid = :sentenceid AND wordblockid IN (' . $in . ')',
                array('sentenceid' => $sentenceid
                )
            );
        }
    }

    public function delete($id) {
        global $DB;
        $DB->delete_records('dragdrop_sentence_word_block', array('sentenceid' => $id));
    }
    /**
     * @param $id
     */
    public function must_exist($id) {
        global $DB;
        $DB->get_field('dragdrop_sentence', 'id', array(
            'id' => $id,
        ), MUST_EXIST);
    }
}
