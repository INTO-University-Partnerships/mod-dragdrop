<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/entity.php';
require_once __DIR__ . '/../models/sentence.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class dragdrop_sentence_model_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_now;

    /**
     * @var dragdrop\sentence
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_now = time();
        $this->_cut = new dragdrop\sentence(array('dragdrop_model_manager', 'get_model'));
        $this->resetAfterTest(true);
    }


    /**
     * tests instantiation
     */
    public function test_sentence_instantiation() {
        $this->assertInstanceOf('dragdrop\\sentence', $this->_cut);
    }

    /**
     * tests creating a new word block
     * @global moodle_database $DB
     */
    public function test_save() {
        global $DB;
        // don't have much data as yet
        $data = array(
            'mark' => 0
        );
        $this->_cut->save(45, $data, $this->_now);
        $this->assertTrue(
            $DB->record_exists('dragdrop_sentence', array(
                'instanceid' => 45,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
    }

    /**
     * test save existing
     * @global moodle_database $DB
     */
    public function test_save_existing() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 20, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now)
                ))
        ));
        $data = array(
            'mark' => 40,
        );
        $this->_cut->save(13, $data, $this->_now + 1, 1);
        $this->assertEquals(2, $DB->count_records('dragdrop_sentence'));
        $this->assertTrue(
            $DB->record_exists('dragdrop_sentence', array(
                'id' => 1,
                'instanceid' => 13,
                'mark' => 40,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now + 1
            ))
        );
        $this->assertTrue(
            $DB->record_exists('dragdrop_sentence', array(
                'id' => 2,
                'instanceid' => 13,
                'mark' => 20,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
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
        $data = array(
            'mark' => 40,
        );
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
        $this->_cut->delete(2);
        $this->assertEquals(1, $DB->count_records('dragdrop_sentence'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_sentence', array(
                'id' => 2,
            ))
        );
        $this->assertEquals(2, $DB->count_records('dragdrop_sentence_word_block'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_sentence_word_block', array(
                'sentenceid' => 2,
            ))
        );
        $this->assertEquals(2, $DB->count_records('dragdrop_word_block'));
    }

    /**
     * test delete a sentence that does not exist
     * @expectedException dml_missing_record_exception
     */
    public function test_delete_a_word_block_that_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 20, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->delete(3);
    }


    /**
     * test get a single sentence
     */
    public function test_get() {

        $columns = array('id', 'instanceid', 'mark', 'timecreated', 'timemodified');

        $values = array(
            array(1, 13, 20, $this->_now, $this->_now),
            array(2, 13, 20, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_sentence' => array(
                $columns,
                $values[0],
                $values[1]
            ),
        )));

        $data = $this->_cut->get(1);
        $this->assertEquals(
            array_combine($columns, $values[0]), (array)$data
        );
        $data = $this->_cut->get(2);
        $this->assertEquals(
            array_combine($columns, $values[1]), (array)$data
        );
    }

    /**
     * test get a sentence that does not exist
     * @expectedException dml_missing_record_exception
     */
    public function test_get_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_sentence' => array(
                array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                array(1, 20, 13, $this->_now, $this->_now),
                array(2, 20, 13, $this->_now, $this->_now)
            )
        )));
        $this->_cut->get(3);
    }

    /**
     * test get all
     */
    public function test_get_all() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => array(
                    array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'onesingleword', 13, $this->_now, $this->_now),
                    array(2, 'a series of words', 13, $this->_now, $this->_now),
                    array(3, 'more words', 13, $this->_now, $this->_now),
                    array(4, 'in the database', 13, $this->_now, $this->_now),
                    array(5, 'wrong dragdropid', 14, $this->_now, $this->_now)
                ),
                'dragdrop_sentence_word_block' => array(
                    array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                    array(1, 1, 1, 2, 0, 0, $this->_now),
                    array(2, 1, 2, 1, 0, 0, $this->_now),
                    array(3, 2, 1, 1, 0, 0, $this->_now),
                    array(4, 2, 2, 2, 0, 0, $this->_now),
                    array(5, 1, 3, 3, 0, 0, $this->_now),
                    array(6, 2, 3, 1, 0, 0, $this->_now),
                    array(7, 3, 3, 2, 0, 0, $this->_now),
                    array(8, 4, 3, 4, 0, 0, $this->_now)
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 10, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now),
                    array(3, 40, 13, $this->_now, $this->_now),
                    array(4, 50, 14, $this->_now, $this->_now)
                ))
        ));
        $sentences = $this->_cut->get_all(13);
        $this->assertEquals(3, count($sentences));
        $this->assertArrayHasKey(1, $sentences);
        $this->assertEquals(
            array(
                'id' => 1,
                'wordblocks' => array(
                    (object) array(
                        'wordblockid' => 2,
                        'wordblock' => 'a series of words'),
                    (object) array(
                        'wordblockid' => 1,
                        'wordblock' => 'onesingleword')
                )
            ), (array)$sentences[1]);
        $this->assertArrayHasKey(2, $sentences);
        $this->assertEquals(
            array(
                'id' => 2,
                'wordblocks' => array(
                    (object) array(
                        'wordblockid' => 1,
                        'wordblock' => 'onesingleword'),
                    (object) array(
                        'wordblockid' => 2,
                        'wordblock' => 'a series of words'),
                )
            ), (array)$sentences[2]);
        $this->assertArrayHasKey(3, $sentences);
        $this->assertEquals(
            array(
                'id' => 3,
                'wordblocks' => array(
                    (object) array(
                        'wordblockid' => 2,
                        'wordblock' => 'a series of words'),
                    (object) array(
                        'wordblockid' => 3,
                        'wordblock' => 'more words'),
                    (object) array(
                        'wordblockid' => 1,
                        'wordblock' => 'onesingleword'),
                    (object) array(
                        'wordblockid' => 4,
                        'wordblock' => 'in the database')
                )
            ), (array)$sentences[3]);
    }

    public function test_get_all_no_words() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 10, 13, $this->_now, $this->_now)
                ))
        ));

        $sentences = $this->_cut->get_all(13);
        $this->assertArrayHasKey(1, $sentences);
        $this->assertEquals(
            array(
                'id' => 1,
                'wordblocks' => array()
            ), (array)$sentences[1]);
    }

    public function test_get_all_no_sentences() {

        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 10, 13, $this->_now, $this->_now)
                ))
        ));
        $sentences = $this->_cut->get_all(14);
        $this->assertEmpty($sentences);
    }
}
