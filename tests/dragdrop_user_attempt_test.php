<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/entity.php';
require_once __DIR__ . '/../models/user_attempt.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class dragdrop_user_attempt_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_now;

    /**
     * @var dragdrop\user_attempt
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_now = time();
        $this->_cut = new dragdrop\user_attempt(array('dragdrop_model_manager', 'get_model'));
        $this->resetAfterTest(true);
    }


    /**
     * tests instantiation
     */
    public function test_sentence_instantiation() {
        $this->assertInstanceOf('dragdrop\\user_attempt', $this->_cut);
    }

    /**
     * tests creating a attempt that is incorrect
     * @global moodle_database $DB
     */
    public function test_save_incorrect() {
        global $DB, $USER;
        $columns = array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified');
        $data = array(
            $columns,
            array(1, 'I\'m', 45, $this->_now, $this->_now),
            array(2, 'mad', 45, $this->_now, $this->_now),
            array(3, 'as', 45, $this->_now, $this->_now),
            array(4, 'hell', 45, $this->_now, $this->_now),
            array(5, 'and', 45, $this->_now, $this->_now),
            array(6, 'I\'m', 45, $this->_now, $this->_now),
            array(7, 'not going to', 45, $this->_now, $this->_now),
            array(8, 'take it anymore', 45, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => $data,
                'dragdrop_sentence_word_block' => array(
                    array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                    array(1, 8, 11, 1, 0, 0, $this->_now),
                    array(2, 7, 11, 2, 0, 0, $this->_now),
                    array(3, 1, 11, 3, 0, 0, $this->_now),
                    array(4, 4, 11, 4, 0, 0, $this->_now),
                    array(5, 5, 11, 5, 0, 0, $this->_now),
                    array(6, 2, 11, 6, 0, 0, $this->_now),
                    array(7, 3, 11, 7, 0, 0, $this->_now),
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(11, 20, 45, $this->_now, $this->_now),
                )))
        );
        // valid module instance instance
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop' => array(
                array('id', 'course', 'name', 'instruction', 'hint', 'feedback_correct', 'num_attempts', 'timemodified', 'timecreated'),
                array(45, 2, 'Dragdrop Activity', 'Instructional Text', '<p>A hint</p>', '<p>Feedback</p>', 3, time(), time())
            )
        )));

        // first attempt
        $submission_order = array(2, 7, 1, 3, 4, 6, 5);
        $to_save = $this->get_submission_data($submission_order);
        $now = time();
        $id = $this->_cut->save(45, $to_save, $now);

        $record = $DB->get_record('dragdrop_attempt', array('id' => $id));
        $sentence = array();
        foreach ($submission_order as $order) {
            $word = array_combine($columns, $data[$order]);
            $sentence[] = $word['wordblock'];
        }
        $this->assertEquals((object)array(
            'id' => $id,
            'sentence' => implode(" ", $sentence),
            'correct' => 0,
            'reset' => 0,
            'reset_group' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'userid' => $USER->id,
            'attempt' => 1,
            'dragdropid' => 45), $record);

        // second attempt
        $submission_order = array(8, 3, 4, 1, 2, 7);
        $to_save = $this->get_submission_data($submission_order);
        $id = $this->_cut->save(45, $to_save, $now);

        $record = $DB->get_record('dragdrop_attempt', array('id' => $id));
        $sentence = array();
        foreach ($submission_order as $order) {
            $word = array_combine($columns, $data[$order]);
            $sentence[] = $word['wordblock'];
        }
        $this->assertEquals((object)array(
            'id' => $id,
            'sentence' => implode(" ", $sentence),
            'correct' => 0,
            'reset' => 0,
            'reset_group' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'userid' => $USER->id,
            'attempt' => 2,
            'dragdropid' => 45), $record);
    }

    public function test_save_correct() {
        global $DB, $USER;
        $columns = array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified');
        $data = array(
            $columns,
            array(1, 'I\'m', 45, $this->_now, $this->_now),
            array(2, 'mad', 45, $this->_now, $this->_now),
            array(3, 'as', 45, $this->_now, $this->_now),
            array(4, 'hell', 45, $this->_now, $this->_now),
            array(5, 'and', 45, $this->_now, $this->_now),
            array(6, 'I\'m', 45, $this->_now, $this->_now),
            array(7, 'not going to', 45, $this->_now, $this->_now),
            array(8, 'take it anymore', 45, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => $data,
                'dragdrop_sentence_word_block' => array(
                    array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                    array(1, 8, 11, 1, 0, 0, $this->_now),
                    array(2, 7, 11, 2, 0, 0, $this->_now),
                    array(3, 1, 11, 3, 0, 0, $this->_now),
                    array(4, 4, 11, 4, 0, 0, $this->_now),
                    array(5, 5, 11, 5, 0, 0, $this->_now),
                    array(6, 2, 11, 6, 0, 0, $this->_now),
                    array(7, 3, 11, 7, 0, 0, $this->_now),
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(11, 20, 45, $this->_now, $this->_now),
                )))
        );
        // valid module instance
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop' => array(
                array('id', 'course', 'name', 'instruction', 'hint', 'feedback_correct', 'num_attempts', 'timemodified', 'timecreated'),
                array(45, 2, 'Dragdrop Activity', 'Instructional Text', '<p>A hint</p>', '<p>Feedback</p>', 3, time(), time())
            )
        )));

        // first attempt
        $submission_order = array(8, 7, 1, 4, 5, 2, 3);
        $to_save = $this->get_submission_data($submission_order);
        $now = time();
        $id = $this->_cut->save(45, $to_save, $now);

        $record = $DB->get_record('dragdrop_attempt', array('id' => $id));
        $sentence = array();
        foreach ($submission_order as $order) {
            $word = array_combine($columns, $data[$order]);
            $sentence[] = $word['wordblock'];
        }
        $this->assertEquals((object)array(
            'id' => $id,
            'sentence' => implode(" ", $sentence),
            'correct' => 1,
            'reset' => 0,
            'reset_group' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'userid' => $USER->id,
            'attempt' => 1,
            'dragdropid' => 45), $record);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function test_save_maximum_attempts_reached() {
        global $USER;
        $columns = array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified');
        $data = array(
            $columns,
            array(1, 'I\'m', 45, $this->_now, $this->_now),
            array(2, 'mad', 45, $this->_now, $this->_now),
            array(3, 'as', 45, $this->_now, $this->_now),
            array(4, 'hell', 45, $this->_now, $this->_now),
            array(5, 'and', 45, $this->_now, $this->_now),
            array(6, 'I\'m', 45, $this->_now, $this->_now),
            array(7, 'not going to', 45, $this->_now, $this->_now),
            array(8, 'take it anymore', 45, $this->_now, $this->_now)
        );

        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_word_block' => $data,
            'dragdrop_attempt' => array(
                array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'sentence', 'timemodified', 'timecreated'),
                array(1, 45, 0, $USER->id, 1, 'An incorrect attempt', $this->_now, $this->_now),
                array(2, 45, 0, $USER->id, 2, 'An incorrect attempt', $this->_now, $this->_now),
                array(3, 45, 0, $USER->id, 3, 'An incorrect attempt', $this->_now, $this->_now),
            )
        )));
        // valid module instance instance
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop' => array(
                array('id', 'course', 'name', 'instruction', 'hint', 'feedback_correct', 'num_attempts', 'timemodified', 'timecreated'),
                array(45, 2, 'Dragdrop Activity', 'Instructional Text', '<p>A hint</p>', '<p>Feedback</p>', 3, time(), time())
            )
        )));

        // first attempt
        $submission_order = array(8, 7, 1, 4, 5, 2, 3);
        $to_save = $this->get_submission_data($submission_order);
        $now = time();
        $this->_cut->save(45, $to_save, $now);
    }


    protected function get_submission_data($block_ids) {
        $to_submit = array('wordblocks' => array());
        foreach ($block_ids as $id) {
            $to_submit['wordblocks'][] = (object)array(
                'wordblockid' => $id
            );
        }
        return $to_submit;
    }

    public function test_get_all() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $columns = array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'sentence', 'timemodified', 'timecreated');
        $data = array($columns,
            array(1, 45, 0, $user->id, 1, 'An incorrect attempt', $this->_now, $this->_now),
            array(2, 45, 0, $user->id, 2, 'An incorrect attempt', $this->_now, $this->_now),
            array(3, 45, 0, $user->id, 3, 'An incorrect attempt', $this->_now, $this->_now),
            array(4, 45, 1, $user->id, 4, 'An correct attempt', $this->_now, $this->_now),
            array(5, 44, 0, $user->id, 3, 'An incorrect attempt on a different activity', $this->_now, $this->_now),
        );
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $data
            )
        ));
        $records = $this->_cut->get_all(45);

        $this->assertEquals(4, count($records));

        foreach (array(1, 2, 3, 4) as $key => $id) {
            $exp = array_combine($columns, $data[$id]);
            $exp['timecreated_formatted'] = userdate($exp['timecreated']);
            $exp['reset_group'] = 0;
            $exp['firstname'] = $user->firstname;
            $exp['lastname'] = $user->lastname;
            $exp['reset'] = 0;
            $this->assertEquals($exp, (array)$records[$id]);
        }
    }

    public function test_get() {
        global $USER;
        $columns = array('id', 'dragdropid', 'correct', 'userid', 'reset', 'attempt', 'sentence', 'timemodified', 'timecreated');
        $data = array($columns,
            array(1, 45, 0, $USER->id, 0 ,1 ,'An incorrect attempt', $this->_now, $this->_now),
            array(2, 45, 0, $USER->id, 1 ,2 ,'An incorrect attempt', $this->_now, $this->_now),
            array(3, 45, 0, $USER->id, 1 ,3 ,'An incorrect attempt', $this->_now, $this->_now),
            array(4, 45, 1, $USER->id, 0 ,4 ,'An correct attempt', $this->_now, $this->_now),
            array(5, 44, 0, $USER->id, 0 ,3 ,'An incorrect attempt on a different activity', $this->_now, $this->_now),
        );
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $data
            )
        ));
        $record = $this->_cut->get(2);
        $exp = array_combine($columns, $data[2]);
        $exp['contributing_attempts'] = 2;
        $exp['reset_group'] = 0;
        $this->assertEquals($exp, (array)$record);
        $record = $this->_cut->get(5);
        $exp = array_combine($columns, $data[5]);
        $exp['contributing_attempts'] = 1;
        $exp['reset_group'] = 0;
        $this->assertEquals($exp, (array)$record);
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_get_does_not_exist() {
        $this->_cut->get(9999);
    }

    public function test_delete() {
        global $DB, $USER;
        $columns = array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'sentence', 'timemodified', 'timecreated');
        $data = array($columns,
            array(1, 45, 0, $USER->id, 1, 'An incorrect attempt', $this->_now, $this->_now),
            array(2, 45, 0, $USER->id, 2, 'An incorrect attempt', $this->_now, $this->_now),
            array(3, 45, 0, $USER->id, 3, 'An incorrect attempt', $this->_now, $this->_now),
            array(4, 45, 1, $USER->id, 4, 'An correct attempt', $this->_now, $this->_now),
            array(5, 44, 0, $USER->id, 3, 'An incorrect attempt on a different activity', $this->_now, $this->_now),
        );
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $data
            )
        ));
        $this->_cut->delete(3);
        $this->assertFalse($DB->record_exists('dragdrop_attempt', array('id' => 3)));
        $this->assertEquals(4, $DB->count_records('dragdrop_attempt'));
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_delete_does_not_exist() {
        $this->_cut->delete(9999);
    }

    /**
     * reset some attempts
     */
    public function test_reset_attempts() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $columns = array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'sentence', 'timemodified', 'timecreated');
        $data = array($columns,
            array(1, 45, 0, $user->id, 1, 'An incorrect attempt', $this->_now, $this->_now),
            array(2, 45, 0, $user->id, 2, 'An incorrect attempt', $this->_now, $this->_now),
            array(3, 45, 0, $user->id, 3, 'An incorrect attempt', $this->_now, $this->_now),
            array(4, 45, 1, $user->id, 4, 'An correct attempt', $this->_now, $this->_now),
            array(5, 44, 0, $user->id, 3, 'An incorrect attempt on a different activity', $this->_now, $this->_now),
            array(6, 45, 0, 99999, 3, 'A different user on the same activity', $this->_now, $this->_now),
        );
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $data
            )
        ));
        $this->_cut->reset_attempts(45, $user->id, $this->_now + 1);
        $records = $DB->get_records('dragdrop_attempt');
        $this->assertEquals(6, count($records));

        foreach (array(1, 2, 3, 4) as $key => $id) {
            $exp = array_combine($columns, $data[$id]);
            $exp['reset'] = 1;
            $exp['reset_group'] = 1;
            $exp['timemodified'] = $this->_now + 1;
            $this->assertEquals($exp, (array)$records[$id]);
        }
        foreach (array(5, 6) as $key => $id) {
            $exp = array_combine($columns, $data[$id]);
            $exp['reset'] = 0;
            $exp['reset_group'] = 0;
            $this->assertEquals($exp, (array)$records[$id]);
        }

        // add another couple of attempts, and reset again
        $columns = array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'sentence', 'timemodified', 'timecreated');
        $new_attempts = array($columns,
            7 => array(7, 45, 0, $user->id, 1, 'An incorrect attempt', $this->_now, $this->_now),
            8 => array(8, 45, 0, $user->id, 2, 'An incorrect attempt', $this->_now, $this->_now)
        );
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $new_attempts
            )
        ));
        $this->_cut->reset_attempts(45, $user->id, $this->_now + 1);
        $records = $DB->get_records('dragdrop_attempt');
        $this->assertEquals(8, count($records));
        foreach (array(7, 8) as $key => $id) {
            $exp = array_combine($columns, $new_attempts[$id]);
            $exp['reset'] = 1;
            $exp['reset_group'] = 2;
            $exp['timemodified'] = $this->_now + 1;
            $this->assertEquals($exp, (array)$records[$id]);
        }
    }

    /**
     * number of incorrect attempts for a given user
     */
    public function test_get_num_incorrect_attempts() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $columns = array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'sentence', 'reset', 'reset_group', 'timemodified', 'timecreated');
        $data = array($columns,
            array(1, 45, 0, $user->id, 1, 'An incorrect attempt', 0, 0, $this->_now, $this->_now),
            array(2, 45, 0, $user->id, 2, 'An incorrect attempt', 0, 0, $this->_now, $this->_now),
            array(3, 45, 0, $user->id, 3, 'An incorrect attempt', 0, 0, $this->_now, $this->_now),
            array(4, 45, 1, $user->id, 4, 'An correct attempt', 0, 0, $this->_now, $this->_now),
            array(5, 44, 0, $user->id, 3, 'An incorrect attempt on a different activity', 0, 0, $this->_now, $this->_now),
            array(6, 45, 0, 99999, 3, 'A different user on the same activity', 0, 0, $this->_now, $this->_now),
            array(7, 45, 1, $user->id, 1, 'A reset correct attempt', 1, 1, $this->_now, $this->_now),
            array(8, 45, 0, $user->id, 1, 'A reset incorrect attempt', 1, 2, $this->_now, $this->_now),
        );
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $data
            )
        ));
        $attempts = $this->_cut->get_num_incorrect_attempts(45, $user->id);
        $this->assertEquals(3, $attempts);
    }


    /**
     * test that users who have made attempts are returned
     */
    public function test_get_users_with_attempt_count() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        list($users, $expect) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }
        $data = $this->_cut->get_users_with_attempt_count($module->id);
        $this->assertEquals(count($users), count($data));
        foreach ($data as $row) {
            $this->assertEquals($expect[$row->id], (array)$row);
        }
    }

    /**
     * a user who is enrolled but with no attempts should also appear in the report
     */
    public function test_get_users_with_attempt_count_enrolled_user() {

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        list($users, $expect) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }
        $data = $this->_cut->get_users_with_attempt_count($module->id);
        $this->assertEquals(count($users) + 1, count($data));
        $userdata = null;
        foreach ($data as $row) {
            if ($row->id == $user->id) {
                $userdata = $row;
            }
        }
        $this->assertEquals(array(
            'id' => $user->id,
            'lastattempt' => null,
            'numattempts' => null,
            'numreset' => null,
            'completed' => null,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'lastattempt_formatted' => ""
        ), (array)$userdata);
    }

    /**
     * test with second instance of a module for which we don't want any data returned
     */
    public function test_get_users_with_attempt_count_second_modules() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        $module2 = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        list($users, $expect) = $this->_data_for_attempt_report($module->id);
        $this->_data_for_attempt_report($module2->id);
        foreach ($users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }
        $data = $this->_cut->get_users_with_attempt_count($module->id);
        $this->assertEquals(count($users), count($data));
        foreach ($data as $row) {
            $this->assertEquals($expect[$row->id], (array)$row);
        }
    }

    /**
     * test filter user
     */
    public function test_get_users_with_attempt_count_filters() {
        $names = array(
            'first' => array('abC', 'def', 'Ghi', 'jkl', 'MNO'),
            'last' => array('abC', 'def', 'Ghi', 'jkl', 'MNO')
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        $this->_data_for_attempt_report($module->id, $names);
        $filtered_names = array(
            'first' => array('pQr', 'stu', 'vWx', 'yZ'),
            'last' => array('pQr', 'stu', 'vWx', 'yZ')
        );
        list($users, $expect) = $this->_data_for_attempt_report($module->id, $filtered_names);

        $data = $this->_cut->get_users_with_attempt_count($module->id, 'pqr');
        foreach ($data as $user) {
            $this->assertTrue(strpos($user->firstname . " " . $user->lastname, 'pQr') !== false);
        }

        $data = $this->_cut->get_users_with_attempt_count($module->id, 'q rst');
        foreach ($data as $user) {
            $this->assertTrue(strpos($user->firstname . " " . $user->lastname, 'Q rst') !== false);
        }
    }

    /**
     * test sorting
     */
    public function test_get_users_with_attempt_count_sort() {
        $names = array(
            'first' => array('abC', 'def', 'Ghi', 'jkl', 'MNO', 'pQr', 'stu', 'vWx', 'yZ'),
            'last' => array('abC', 'def', 'Ghi', 'jkl', 'MNO', 'pQr', 'stu', 'vWx', 'yZ')
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        list($users, $expect) = $this->_data_for_attempt_report($module->id, $names);
        foreach ($users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        // users ascending
        $data = $this->_cut->get_users_with_attempt_count($module->id, '', 'user', 'ASC');
        $this->assertEquals(count($users), count($data));
        $lastname = "";
        foreach ($data as $row) {
            $this->assertTrue(strcasecmp(utf8_decode($row->lastname), $lastname) >= 0);
            $lastname = utf8_decode($row->lastname);
        }

        // users descending
        $data = $this->_cut->get_users_with_attempt_count($module->id, '', 'user', 'DESC');
        $this->assertEquals(count($users), count($data));
        $lastname = "";
        foreach ($data as $row) {
            if (!$lastname) {
                continue;
            }
            $this->assertTrue(strcasecmp(utf8_decode($row->lastname), $lastname) <= 0);
            $lastname = utf8_decode($row->lastname);
        }

        //last attempt ascending
        $data = $this->_cut->get_users_with_attempt_count($module->id, '', 'lastattempt', 'ASC');
        $this->assertEquals(count($users), count($data));
        $lastattempt = 0;
        foreach ($data as $row) {
            $this->assertTrue((int) $row->lastattempt >= $lastattempt);
            $lastattempt = (int) $row->lastattempt;
        }

        //last attempt descending
        $data = $this->_cut->get_users_with_attempt_count($module->id, '', 'lastattempt', 'DESC');
        $this->assertEquals(count($users), count($data));
        $lastattempt = 0;
        foreach ($data as $row) {
            if (!$lastattempt) {
                continue;
            }
            $this->assertTrue((int) $row->lastattempt <= $lastattempt);
            $lastattempt = (int) $row->lastattempt;
        }

        //num attempts ascending
        $data = $this->_cut->get_users_with_attempt_count($module->id, '', 'numattempts', 'ASC');
        $this->assertEquals(count($users), count($data));
        $numattempts = 0;
        foreach ($data as $row) {
            $this->assertTrue((int) $row->numattempts >= $numattempts);
            $numattempts = (int) $row->numattempts;
        }

        //num attempts descending
        $data = $this->_cut->get_users_with_attempt_count($module->id, '', 'numattempts', 'DESC');
        $this->assertEquals(count($users), count($data));
        $numattempts = 0;
        foreach ($data as $row) {
            if (!$numattempts) {
                continue;
            }
            $this->assertTrue((int) $row->numattempts <= $numattempts);
            $numattempts = (int) $row->numattempts;
        }
    }

    /**
     * tests a Dragdrop module with a group mode of SEPARATEGROUPS
     * should not return any results as the specified user is not a member of a group
     */
    public function test_get_users_with_attempt_count_when_not_a_group_member() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(
            'dragdrop', array(
                'course' => $course->id
            )
        );

        # create users and enrol on the course
        list($users,) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }

        # remove the first user, and assign the next 3 users to a group
        $user = array_shift($users);
        $this->_create_group_with_users($course->id, array_slice($users, 0, 3));

        # set the group mode to separate groups and retrieve the results
        $this->_cut->set_groupmode(SEPARATEGROUPS);
        $this->_cut->set_userid($user->id);
        $data = $this->_cut->get_users_with_attempt_count($module->id);

        $this->assertEmpty($data);
    }

    /**
     * tests a Dragdrop module with a group mode of SEPARATEGROUPS
     * should return users that are of the same group as the specified user
     */
    public function test_get_users_with_attempt_count_when_a_group_member() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(
            'dragdrop', array(
                'course' => $course->id
            )
        );

        # create users and enrol on the course
        list($users, $expect) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }

        $group_members = array_slice($users, 0, 6);
        $this->_create_group_with_users($course->id, $group_members);

        # assign some further users to a different group
        $this->_create_group_with_users($course->id, array_slice($users, 6));

        # set the group mode to separate groups and retrieve the results
        $this->_cut->set_groupmode(SEPARATEGROUPS);
        $this->_cut->set_userid(current($group_members)->id);
        $data = $this->_cut->get_users_with_attempt_count($module->id);

        $this->assertEquals(6, count($data));
        foreach ($data as $row) {
            $this->assertEquals($expect[$row->id], (array)$row);
        }
    }

    /**
     * tests a Dragdrop module with a group mode of SEPARATEGROUPS
     * should return users that are of the same groups the specified user
     */
    public function test_get_users_with_attempt_count_when_multiple_group_memberships() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(
            'dragdrop', array(
                'course' => $course->id
            )
        );

        # create users and enrol them on the course
        list($users, $expect) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }
        $requesting_user = $users[0];
        $group_1_members = array_slice($users, 0, 3);
        $this->_create_group_with_users($course->id, $group_1_members);
        $group_2_members = array_merge(array_slice($users, 3, 3), [$requesting_user]);
        $this->_create_group_with_users($course->id, $group_2_members);

        # set the group mode to separate groups and retrieve the results
        $this->_cut->set_groupmode(SEPARATEGROUPS);
        $this->_cut->set_userid($requesting_user->id);

        $data = $this->_cut->get_users_with_attempt_count($module->id);
        $all_members = array_merge($group_1_members, $group_2_members);
        $this->assertEquals(count(array_unique($all_members, SORT_REGULAR)), count($data));
        foreach ($data as $row) {
            $this->assertEquals($expect[$row->id], (array)$row);
        }
    }

    /**
     * test getting a total number of users
     */
    public function test_get_total_users() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        list($users,) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }
        $data = $this->_cut->get_total_users($module->id);
        $this->assertEquals(count($users), $data);
    }

    /**
     * test getting a total number of users filtered
     */
    public function test_get_total_users_filtered() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));
        $filtered_names = array(
            'first' => array('pQr', 'stu', 'vWx', 'yZ'),
            'last' => array('pQr', 'stu', 'vWx', 'yZ')
        );
        list($users,) = $this->_data_for_attempt_report($module->id, $filtered_names);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }
        $data = $this->_cut->get_total_users($module->id, 'pqr');
        $count = 0;
        foreach ($users as $user) {
            if (strpos($user->firstname . " " . $user->lastname, 'pQr') !== false) {
                $count++;
            }
        }
        $this->assertEquals($count, $data);
        $data = $this->_cut->get_total_users($module->id, 'q rst');
        $count = 0;
        foreach ($users as $user) {
            if (strpos($user->firstname . " " . $user->lastname, 'Q rst') !== false) {
                $count++;
            }
        }
        $this->assertEquals($count, $data);
    }

    /**
     * tests a Dragdrop module with a group mode of SEPARATEGROUPS
     * should return a count of 0 (zero) when the specified user is not a member of a group
     */
    public function test_get_total_users_when_not_a_group_member() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(
            'dragdrop', array(
                'course' => $course->id
            )
        );

        # create users and enrol on the course
        list($users,) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }

        # remove the first user, and assign the next 3 users to a group
        $user = array_shift($users);
        $this->_create_group_with_users($course->id, array_slice($users, 0, 3));

        # set the group mode to separate groups and retrieve the results
        $this->_cut->set_groupmode(SEPARATEGROUPS);
        $this->_cut->set_userid($user->id);
        $data = $this->_cut->get_total_users($module->id);
        $this->assertEquals(0, $data);
    }

    /**
     * tests a Dragdrop module with a group mode of SEPARATEGROUPS
     * should return a count of users in the same group as the specified user
     */
    public function test_get_total_users_when_a_group_member() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(
            'dragdrop', array(
                'course' => $course->id
            )
        );

        # create users and enrol on the course
        list($users, ) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }
        $group_members = array_slice($users, 0, 7);
        $this->_create_group_with_users($course->id, $group_members);

        # assign some further users to a different group
        $this->_create_group_with_users($course->id, array_slice($users, 6));

        # set the group mode to separate groups and retrieve the results
        $this->_cut->set_groupmode(SEPARATEGROUPS);
        $this->_cut->set_userid(current($group_members)->id);
        $data = $this->_cut->get_total_users($module->id);
        $this->assertEquals(7, $data);
    }

    /**
     * tests a Dragdrop module with a group mode of SEPARATEGROUPS
     * should return a count of users that are in the same groups as the specified user
     */
    public function test_get_total_users_when_multiple_group_memberships() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module(
            'dragdrop', array(
                'course' => $course->id
            )
        );

        # create users and enrol on the course
        list($users,) = $this->_data_for_attempt_report($module->id);
        foreach ($users as $u) {
            $this->getDataGenerator()->enrol_user($u->id, $course->id);
        }
        $requesting_user = $users[0];
        $group_1_members = array_slice($users, 0, 5);
        $this->_create_group_with_users($course->id, $group_1_members);
        $group_2_members = array_merge(array_slice($users, 2, 8), [$requesting_user]);
        $this->_create_group_with_users($course->id, $group_2_members);

        # set the group mode to separate groups and retrieve the results
        $this->_cut->set_groupmode(SEPARATEGROUPS);
        $this->_cut->set_userid($requesting_user->id);
        $data = $this->_cut->get_total_users($module->id);

        $all_members = array_merge($group_1_members, $group_2_members);
        $this->assertEquals(count(array_unique($all_members, SORT_REGULAR)), $data);
    }

    /**
     * @param integer $courseid
     * @param array $users
     * @return stdClass
     */
    protected function _create_group_with_users($courseid, $users) {
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $courseid
        ));

        // assign the users to the group
        foreach ($users as $user) {
            $this->getDataGenerator()->create_group_member([
                'groupid' => $group->id,
                'userid' => $user->id
            ]);
        }
        return $group;
    }

    /**
     * generates data for reporting
     * @param $instanceid
     * @param $names
     * @return array
     */
    protected function _data_for_attempt_report($instanceid, $names=null) {
        global $DB;
        $users = array();
        foreach (range(0, 12) as $i) {
            if ($names) {
                shuffle($names['first']);
                shuffle($names['last']);
                $first = implode("", array_slice($names['first'], 0, rand(0, count($names['first']) - 1)));
                $last = implode("", array_slice($names['last'], 0, rand(0, count($names['first']) - 1)));
                $users[$i] = $this->getDataGenerator()->create_user(array(
                    'firstname' => $first,
                    'lastname' => $last,
                ));
            } else {
                $users[$i] = $this->getDataGenerator()->create_user();
            }
        }
        $sentence = function () {
            $wordblocks = array('some', 'random', 'wordblocks', 'to sort', 'and', 'form', 'sentences');
            shuffle($wordblocks);
            $rnd = rand(1, count($wordblocks));
            $sentence = array_slice($wordblocks, 0, $rnd - 1);
            return implode(" ", $sentence);
        };
        $attempts = array();
        $id = $DB->get_field_sql('SELECT MAX(id) FROM {dragdrop_attempt}') + 1;
        $expect = array();

        // create between 1 and 5 previous attempts, and between 1 and 5 reset attempts, for all users
        for ($x = 0; $x < count($users); $x++) {
            $num_attempts = rand(1, 5);
            $time = $this->_now;
            $completed = 0;
            for ($a = 1; $a <= $num_attempts; $a++) {
                $time++;
                $correct = rand(0, 1);
                $completed = (int) ($completed || $correct);
                $attempts[$id] = array($id, $instanceid, $correct, $users[$x]->id, $a, 0, $sentence(), $this->_now, $time);
                $id++;
            }
            $reset_attempts = rand(1, 5);
            for ($r = 1; $r <= $reset_attempts; $r++) {
                $correct = rand(0, 1);
                $completed = (int) ($completed || $correct);
                $attempts[$id] = array($id, $instanceid, $completed, $users[$x]->id, $r+$a, 1, $sentence(), $this->_now, $this->_now);
                $id++;
            }

            $expect[$users[$x]->id] = array(
                'id' => $users[$x]->id,
                'lastattempt' => $time,
                'numattempts' => $num_attempts + $reset_attempts,
                'numreset' => $reset_attempts,
                'completed' => $completed,
                'firstname' => $users[$x]->firstname,
                'lastname' => $users[$x]->lastname,
                'lastattempt_formatted' => userdate($time)
            );
        }

        $columns = array('id', 'dragdropid', 'correct', 'userid', 'attempt', 'reset', 'sentence', 'timemodified', 'timecreated');
        array_unshift($attempts, $columns);
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_attempt' => $attempts
            )
        ));
        return array($users, $expect);
    }
}