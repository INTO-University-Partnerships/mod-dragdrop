<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/entity.php';
require_once __DIR__ . '/../models/comment.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class dragdrop_comment_model_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_now;

    /**
     * @var dragdrop\comment
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_now = time();
        $this->_cut = new dragdrop\comment(array('dragdrop_model_manager', 'get_model'));
        $this->resetAfterTest(true);
    }

    /**
     * tests instantiation
     */
    public function test_word_block_instantiation() {
        $this->assertInstanceOf('dragdrop\\comment', $this->_cut);
    }

    /**
     * tests creating a new comment
     * @global moodle_database $DB
     */
    public function test_save() {
        global $DB, $USER;
        $data = array(
            'comment' => ' A comment of great meaning  ',
            'userid' => 17
        );
        $id = $this->_cut->save(45, $data, $this->_now);

        $this->assertTrue(
            $DB->record_exists('dragdrop_comment', array(
                'creatorid' => $USER->id,
                'userid' => 17,
                'dragdropid' => 45,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
        $record = $DB->get_record('dragdrop_comment', array('id' => $id));
        $this->assertEquals('A comment of great meaning', $record->comment);
    }

    /**
     * update an existing comment
     */
    public function test_save_an_existing_comment() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, 3, 13, $this->_now, $this->_now),
                    array(2, 'poor dropping skills', 2, 3, 13, $this->_now, $this->_now)
                ))
        ));
        $data = array(
            'comment' => ' your still not very good at dragging  '
        );
        $this->_cut->save(13, $data, $this->_now + 1, 1);
        $this->assertEquals(2, $DB->count_records('dragdrop_comment'));
        $this->assertTrue(
            $DB->record_exists('dragdrop_comment', array(
                'id' => 1,
                'creatorid' => 2,
                'userid' => 3,
                'dragdropid' => 13,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now + 1
            ))
        );
        $record = $DB->get_record('dragdrop_comment', array('id' => 1));
        $this->assertEquals('your still not very good at dragging', $record->comment);
        $this->assertTrue(
            $DB->record_exists('dragdrop_comment', array(
                'id' => 2,
                'creatorid' => 2,
                'userid' => 3,
                'dragdropid' => 13,
                'timecreated' => $this->_now,
                'timemodified' => $this->_now
            ))
        );
        $record = $DB->get_record('dragdrop_comment', array('id' => 2));
        $this->assertEquals('poor dropping skills', $record->comment);
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_save_an_existing_comment_that_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, 3, 13, $this->_now, $this->_now),
                    array(2, 'poor dropping skills', 2, 3, 13, $this->_now, $this->_now)
                ))
        ));
        $data = array(
            'comment' => ' Many Words All Placed Next To Each Other  '
        );
        $this->_cut->save(13, $data, $this->_now, 3);
    }

    /**
     * test delete a comment
     */
    public function test_delete() {
        global $DB;
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, 3, 13, $this->_now, $this->_now),
                    array(2, 'poor dropping skills', 2, 3, 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->delete(2);
        $this->assertEquals(1, $DB->count_records('dragdrop_comment'));
        $this->assertFalse(
            $DB->record_exists('dragdrop_word_block', array(
                'id' => 2,
            ))
        );
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_delete_a_comment_that_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, 3, 13, $this->_now, $this->_now),
                    array(2, 'poor dropping skills', 2, 3, 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->delete(3);
    }

    /**
     * test get one comment
     */
    public function test_get() {

        $columns = array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified');

        $values = array(
            array(1, 'your not very good at dragging', 2, 3, 13, $this->_now, $this->_now),
            array(2, 'poor dropping skills', 2, 3, 13, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_comment' => array(
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
     * @expectedException dml_missing_record_exception
     */
    public function test_get_does_not_exist() {
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, 3, 13, $this->_now, $this->_now),
                    array(2, 'poor dropping skills', 2, 3, 13, $this->_now, $this->_now)
                ))
        ));
        $this->_cut->get(3);
    }

    /**
     * test get all words belonging to the logged in user
     */
    public function test_get_all() {
        // create 3 users
        $users = array();
        for ($x=0; $x<3; $x++) {
            $users[$x] = $this->getDataGenerator()->create_user();
        }
        // login a user
        $this->setUser($users[2]);

        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, $users[2]->id, 13, $this->_now + 4, $this->_now),
                    array(2, 'poor dropping skills', 2, $users[2]->id, 13, $this->_now + 1, $this->_now),
                    array(3, 'poor dropping skills again', 2, $users[2]->id, 13, $this->_now + 2, $this->_now),
                    array(4, 'this is a different user', 2, $users[1]->id, 13, $this->_now, $this->_now),
                    array(5, 'this is a different user', 2, $users[0]->id, 13, $this->_now, $this->_now)
                ))
        ));
        $words = $this->_cut->get_all(13);
        $this->assertCount(3, $words);

        // check the sort
        $ids = array_keys($words);
        $this->assertEquals(array(1, 3, 2), $ids);
    }

    /**
     * test get all words belonging to the logged in user
     */
    public function test_get_all_by_another_user() {
        // create 3 users
        $users = array();
        for ($x=0; $x<3; $x++) {
            $users[$x] = $this->getDataGenerator()->create_user();
        }
        // login a user
        $this->setUser($users[1]);

        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_comment' => array(
                    array('id', 'comment', 'creatorid', 'userid', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'your not very good at dragging', 2, $users[2]->id, 13, $this->_now + 4, $this->_now),
                    array(2, 'poor dropping skills', 2, $users[2]->id, 13, $this->_now + 1, $this->_now),
                    array(3, 'poor dropping skills again', 2, $users[2]->id, 13, $this->_now + 2, $this->_now),
                    array(4, 'this is a different user', 2, $users[1]->id, 13, $this->_now, $this->_now),
                    array(5, 'this is a different user', 2, $users[0]->id, 13, $this->_now, $this->_now)
                ))
        ));
        $words = $this->_cut->get_all(13, array('userid' => $users[2]->id));
        $this->assertCount(3, $words);

        // check the sort
        $ids = array_keys($words);
        $this->assertEquals(array(1, 3, 2), $ids);
    }
}