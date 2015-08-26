<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/entity.php';
require_once __DIR__ . '/../models/word_block.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class dragdrop_word_block_model_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_now;

    /**
     * @var dragdrop\word_block
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_now = time();
        $this->_cut = new dragdrop\word_block(array('dragdrop_model_manager', 'get_model'));
        $this->resetAfterTest(true);
    }

    /**
     * tests instantiation
     */
    public function test_word_block_instantiation() {
        $this->assertInstanceOf('dragdrop\\word_block', $this->_cut);
    }

    /**
     * tests creating a new word block
     * @global moodle_database $DB
     */
    public function test_save() {
        global $DB;
        $data = array(
            'wordblock' => ' oneSINGLEword  '
        );
        $this->_cut->save(45, $data, $this->_now);

        $this->assertTrue(
            $DB->record_exists('dragdrop_word_block', array(
                'wordblock' => 'oneSINGLEword',
                'dragdropid' => 45,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
    }

    /**
     * test create a block of multiple words
     * @global moodle_database $DB
     */
    public function test_save_multiple_words() {
        global $DB;
        $data = array(
            'wordblock' => ' Many Words All Placed Next To Each Other  '
        );
        $this->_cut->save(54, $data, $this->_now);

        $this->assertTrue(
            $DB->record_exists('dragdrop_word_block', array(
                'wordblock' => 'Many Words All Placed Next To Each Other',
                'dragdropid' => 54,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
    }

    /**
     * update an existing word block
     */
    public function test_save_an_existing_word_block() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_word_block' => array(
                array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                array(1, 'onesingleword', 13, $this->_now, $this->_now),
                array(2, 'another word not to be updated', 13, $this->_now, $this->_now)
            ))
        ));
        $data = array(
            'wordblock' => ' Many Words All Placed Next To Each Other  '
        );
        $this->_cut->save(13, $data, $this->_now + 1, 1);
        $this->assertEquals(2, $DB->count_records('dragdrop_word_block'));
        $this->assertTrue(
            $DB->record_exists('dragdrop_word_block', array(
                'id' => 1,
                'wordblock' => 'Many Words All Placed Next To Each Other',
                'dragdropid' => 13,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now + 1
            ))
        );
        $this->assertTrue(
            $DB->record_exists('dragdrop_word_block', array(
                'id' => 2,
                'wordblock' => 'another word not to be updated',
                'dragdropid' => 13,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
    }

    /**
     * attempt to update a word block that doesn't exist
     * @expectedException dml_missing_record_exception
     */
    public function test_save_an_existing_word_block_that_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => array(
                    array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'onesingleword', 13, $this->_now, $this->_now),
                    array(2, 'a series of words', 13, $this->_now, $this->_now)
                ))
        ));
        $data = array(
            'wordblock' => ' Many Words All Placed Next To Each Other  '
        );
        $this->_cut->save(13, $data, $this->_now, 3);
    }

    /**
     * test delete a word block
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
                    array(5, 2, 3, 1, 0, 0, $this->_now),
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 20, 13, $this->_now, $this->_now),
                    array(2, 20, 13, $this->_now, $this->_now),
                    array(3, 20, 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->delete(2);
        $this->assertEquals(1, $DB->count_records('dragdrop_word_block'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_word_block', array(
                'id' => 2,
            ))
        );
        $this->assertEquals(2, $DB->count_records('dragdrop_sentence_word_block'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_sentence_word_block', array(
                'wordblockid' => 2,
            ))
        );
        $this->assertEquals(2, $DB->count_records('dragdrop_sentence'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_sentence', array(
                'id' => 3,
            ))
        );
    }

    /**
     * test delete a word block that does not exist
     * @expectedException dml_missing_record_exception
     */
    public function test_delete_a_word_block_that_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => array(
                    array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'onesingleword', 13, $this->_now, $this->_now),
                    array(2, 'a series of words', 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->delete(3);
    }

    /**
     * test get one word block
     */
    public function test_get() {

        $columns = array('id', 'wordblock', 'dragdropid', 'tagid', 'timecreated', 'timemodified');

        $values= array(
            array(1, 'onesingleword', 13, 5, $this->_now, $this->_now),
            array(2, 'a series of words', 13, 4, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_word_block' => array(
                $columns,
                $values[0],
                $values[1]
            ),
        )));

        $data = $this->_cut->get(1);
        $this->assertEquals(
            array_combine($columns, $values[0]), (array) $data
        );
        $data = $this->_cut->get(2);
        $this->assertEquals(
            array_combine($columns, $values[1]), (array) $data
        );
    }

    /**
     * test get a word block that does not exist
     * @expectedException dml_missing_record_exception
     */
    public function test_get_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_word_block' => array(
                array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                array(1, 'onesingleword', 13, $this->_now, $this->_now),
                array(2, 'a series of words', 13, $this->_now, $this->_now)
            ),
        )));
        $this->_cut->get(3);
    }

    /**
     * Test get all words belonging to a specific instance of the module
     */
    public function test_get_all() {

        $words = array('onesingleword', 'along with some other words', 'into', 'a sentence', 'form', 'unrelated_word', 'can you');

        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_word_block' => array(
                array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                array(1, current($words), 13, $this->_now + 2, $this->_now),
                array(2, next($words), 13, $this->_now + 1, $this->_now),
                array(3, next($words), 13, $this->_now + 6, $this->_now),
                array(4, next($words), 13, $this->_now + 5, $this->_now),
                array(5, next($words), 13, $this->_now + 4, $this->_now),
                array(6, next($words), 27, $this->_now, $this->_now),
                array(7, next($words), 13, $this->_now + 4, $this->_now)
            ),
        )));
        $words = $this->_cut->get_all(13);
        $this->assertCount(6, $words);

        // check the sort
        $ids = array_keys($words);
        $this->assertEquals(array(2, 1, 7, 5, 4, 3), $ids);
    }
}