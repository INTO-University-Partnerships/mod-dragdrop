<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/entity.php';
require_once __DIR__ . '/../models/sentence_words.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class dragdrop_sentence_words_model_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_now;

    /**
     * @var dragdrop\sentence_words
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_now = time();
        $this->_cut = new dragdrop\sentence_words(array('dragdrop_model_manager', 'get_model'));
        $this->resetAfterTest(true);
    }


    /**
     * tests instantiation
     */
    public function test_sentence_instantiation() {
        $this->assertInstanceOf('dragdrop\\sentence_words', $this->_cut);
    }

    /**
     * tests creating a new word block
     * @global moodle_database $DB
     */
    public function test_save() {
        global $DB;
        $wordblocks = array(
            array('position' => 10, 'left' => 124, 'top' => 189, 'wordblockid' => 9),
            array('position' => 3, 'left' => 224, 'top' => 289, 'wordblockid' => 1),
            array('position' => 2, 'left' => 324, 'top' => 389, 'wordblockid' => 8),
            array('position' => 11, 'left' => 424, 'top' => 489, 'wordblockid' => 7),
            array('position' => 1, 'left' => 524, 'top' => 589, 'wordblockid' => 2),
            array('position' => 7, 'left' => 624, 'top' => 689, 'wordblockid' => 13),
            array('position' => 4, 'left' => 724, 'top' => 789, 'wordblockid' => 6),
            array('position' => 12, 'left' => 824, 'top' => 889, 'wordblockid' => 4),
            array('position' => 5, 'left' => 174, 'top' => 989, 'wordblockid' => 10),
            array('position' => 6, 'left' => 274, 'top' => 139, 'wordblockid' => 12),
            array('position' => 9, 'left' => 374, 'top' => 89, 'wordblockid' => 5),
            array('position' => 8, 'left' => 474, 'top' => 259, 'wordblockid' => 11),
        );

        $data = array(
            'wordblocks' => array_map(function ($array) { return (object)$array; }, $wordblocks)
        );
        $id = $this->_cut->save(45, $data, $this->_now);

        $returned_blocks = array_map(function ($array) {
            $array['xcoord'] = $array['left'];
            $array['ycoord'] = $array['top'];
            unset($array['top']);
            unset($array['left']);
            return $array;
        }, $wordblocks);


        foreach ($returned_blocks as $block) {
            $block['sentenceid'] = $id;
            $block['timecreated'] = $this->_now;
            $this->assertTrue($DB->record_exists('dragdrop_sentence_word_block', $block));
        }
    }

    /**
     * includes some word blocks that are already in the database
     */
    public function test_save_and_replace() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence_word_block' => array(
                    array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                    array(1, 7, 1, 1, 0, 0, $this->_now),
                    array(2, 10, 2, 1, 0, 0, $this->_now),
                    array(3, 4, 1, 1, 0, 0, $this->_now),
                    array(4, 2, 1, 1, 0, 0, $this->_now)
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 20, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now)
                ))
        ));

        $wordblocks = array(
            array('position' => 10, 'left' => 124, 'top' => 189, 'wordblockid' => 9),
            array('position' => 3, 'left' => 224, 'top' => 289, 'wordblockid' => 1),
            array('position' => 2, 'left' => 324, 'top' => 389, 'wordblockid' => 8),
            array('position' => 11, 'left' => 424, 'top' => 489, 'wordblockid' => 7),
            array('position' => 1, 'left' => 524, 'top' => 589, 'wordblockid' => 2),
            array('position' => 7, 'left' => 624, 'top' => 689, 'wordblockid' => 13),
            array('position' => 4, 'left' => 724, 'top' => 789, 'wordblockid' => 6),
            array('position' => 12, 'left' => 824, 'top' => 889, 'wordblockid' => 4),
            array('position' => 5, 'left' => 174, 'top' => 989, 'wordblockid' => 10),
            array('position' => 6, 'left' => 274, 'top' => 139, 'wordblockid' => 12),
            array('position' => 9, 'left' => 374, 'top' => 89, 'wordblockid' => 5),
            array('position' => 8, 'left' => 474, 'top' => 259, 'wordblockid' => 11),
        );

        $data = array(
            'wordblocks' => array_map(function ($array) { return (object)$array; }, $wordblocks)
        );
        $returned_blocks = array_map(function ($array) {
            $array['xcoord'] = $array['left'];
            $array['ycoord'] = $array['top'];
            unset($array['top']);
            unset($array['left']);
            return $array;
        }, $wordblocks);
        $id = $this->_cut->save(45, $data, $this->_now, 1);
        $this->assertEquals(13, $DB->count_records('dragdrop_sentence_word_block'));
        foreach ($returned_blocks as $block) {
            $block['sentenceid'] = $id;
            $block['timecreated'] = $this->_now;
            $this->assertTrue($DB->record_exists('dragdrop_sentence_word_block', $block));
        }
    }

    /**
     * test update where the record does not exist
     * @expectedException dml_missing_record_exception
     */
    public function test_save_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 20, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now)
                ))
        ));
        $data = array();
        $this->_cut->save(13, $data, $this->_now + 1, 3);
    }

    /**
     * test delete
     */
    public function test_delete() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => array(
                    array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'onesingleword', 13, $this->_now, $this->_now),
                    array(2, 'a series of words', 13, $this->_now, $this->_now)
                ),
                'dragdrop_sentence_word_block' => array(
                    array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                    array(1, 1, 1, 1, 0, 0, $this->_now),
                    array(2, 1, 2, 2, 0, 0, $this->_now),
                    array(3, 2, 1, 1, 0, 0, $this->_now),
                    array(4, 2, 2, 2, 0, 0, $this->_now),
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 20, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->delete(1);
        $this->assertEquals(2, $DB->count_records('dragdrop_sentence'));
        $this->assertEquals(2, $DB->count_records('dragdrop_sentence_word_block'));
        $this->assertEquals(2, $DB->count_records('dragdrop_word_block'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_sentence_word_block', array(
                'sentenceid' => 1,
            ))
        );
    }

    /**
     * test delete a sentence that does not exist
     */
    public function test_delete_a_word_block_that_does_not_exist() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_sentence_word_block' => array(
                array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                array(1, 1, 1, 1, 0, 0, $this->_now),
                array(2, 1, 2, 2, 0, 0, $this->_now),
                array(3, 2, 1, 1, 0, 0, $this->_now),
                array(4, 2, 2, 2, 0, 0, $this->_now),
            )
        )));
        $this->_cut->delete(3);
        $this->assertEquals(4, $DB->count_records('dragdrop_sentence_word_block'));
    }


    /**
     * test get a single sentence
     */
    public function test_get() {

        $w_columns = array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated');

        $w_data = array(
            0 => $w_columns,
            1 => array(1, 1, 1, 2, 0, 0, $this->_now),
            2 => array(2, 1, 2, 1, 0, 0, $this->_now),
            3 => array(3, 2, 1, 1, 0, 0, $this->_now),
            4 => array(4, 2, 2, 2, 0, 0, $this->_now),
            5 => array(5, 1, 3, 3, 0, 0, $this->_now),
            6 => array(6, 2, 3, 4, 0, 0, $this->_now),
            7 => array(7, 3, 3, 2, 0, 0, $this->_now),
            8 => array(8, 4, 3, 1, 0, 0, $this->_now)
        );

        $s_columns = array('id', 'mark', 'instanceid', 'timecreated', 'timemodified');

        $s_data = array(
            0 => $s_columns,
            1 => array(1, 20, 13, $this->_now, $this->_now),
            2 => array(2, 20, 13, $this->_now, $this->_now),
            3 => array(3, 20, 13, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence_word_block' => $w_data,
                'dragdrop_sentence' => $s_data
        )));

        $expected = function($w_data) use ($w_columns) {
            $data = array_combine($w_columns, $w_data);
            $data['left'] = $data['xcoord'];
            $data['top'] = $data['ycoord'];
            unset($data['xcoord']);
            unset($data['ycoord']);
            unset($data['timecreated']);
            unset($data['sentenceid']);
            unset($data['position']);
            return $data;

        };

        $sw = $this->_cut->get(1);
        $this->assertEquals(2, count($sw->wordblocks));

        // word blocks 1 and 3 are in sentence 1
        foreach (array(3, 1) as $key => $id) {
            $this->assertEquals(array_combine($s_columns, $s_data[1]), (array)$sw->sentence);
            $this->assertEquals($expected($w_data[$id]), (array)$sw->wordblocks[$key]);
        }

        $sw = $this->_cut->get(2);
        $this->assertEquals(2, count($sw->wordblocks));

        // word blocks 2 and 4 are in sentence 2
        foreach (array(2, 4) as $key => $id) {
            $this->assertEquals(array_combine($s_columns, $s_data[2]), (array)$sw->sentence);
            $this->assertEquals($expected($w_data[$id]), (array)$sw->wordblocks[$key]);
        }

        $sw = $this->_cut->get(3);
        $this->assertEquals(4, count($sw->wordblocks));

        // word blocks 5,6,7 and 8 are in sentence 2
        foreach (array(8,7,5,6) as $key => $id) {
            $this->assertEquals(array_combine($s_columns, $s_data[3]), (array)$sw->sentence);
            $this->assertEquals($expected($w_data[$id], $s_data[1]), (array)$sw->wordblocks[$key]);
        }
    }
}
