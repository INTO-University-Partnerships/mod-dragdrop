<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/entity.php';
require_once __DIR__ . '/../models/settings.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class dragdrop_settings_model_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_now;

    /**
     * @var dragdrop\settings
     */
    protected $_cut;

    /**
     * table columns
     */
    protected $_columns;

    /**
     * set-up data
     */
    protected $_data;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_now = time();
        $this->_cut = new dragdrop\settings(array('dragdrop_model_manager', 'get_model'));
        $this->resetAfterTest(true);

        // valid module instance
        $this->_columns = array('id',
            'course',
            'name',
            'instruction',
            'hint',
            'feedback_correct',
            'display_labels',
            'header',
            'footer',
            'num_attempts',
            'timemodified',
            'timecreated'
        );
        $this->_data = array(
            45,
            2,
            'Dragdrop Activity',
            'Instructional Text',
            '<p>A hint</p>',
            '<p>Feedback</p>',
            0,
            '<p>My header</p>',
            '<p>My footer</p>',
            3,
            $this->_now,
            $this->_now
        );
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop' => array(
                $this->_columns, $this->_data
            )
        )));
    }

    /**
     * tests instantiation
     */
    public function test_word_block_instantiation() {
        $this->assertInstanceOf('dragdrop\\settings', $this->_cut);
    }

    /**
     * tests saving number of attempts
     * @global moodle_database $DB
     */
    public function test_save_num_attempts() {
        global $DB;

        $save_data = array(
            'num_attempts' => 5
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $expected = array_combine($this->_columns, $this->_data);
        $expected['num_attempts'] = 5;
        $expected['timemodified'] = $this->_now+1;
        $record = $DB->get_record('dragdrop', array('id' => 45));
        $this->assertEquals($expected, (array) $record);
    }

    /**
     * setting a lower number of attempts should delete any feedback associated with attempts that no longer exist
     */
    public function test_save_num_attempts_deletes_feedback() {
        global $DB;
        $feedback1 = "<p>That was a terrible first attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $feedback2 = "<p>That was a terrible second attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $feedback3 = "<p>That was a terrible third attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $feedback4 = "<p>That was a terrible fourth attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $feedback5 = "<p>That was a terrible fifth attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $f_columns = array('id', 'dragdropid', 'feedback', 'attempt', 'timemodified', 'timecreated');
        $f_data = array(
            array(1, 45, $feedback1, 1, $this->_now, $this->_now),
            array(2, 45, $feedback2, 2, $this->_now, $this->_now),
            array(3, 45, $feedback3, 3, $this->_now, $this->_now),
            array(4, 45, $feedback4, 4, $this->_now, $this->_now),
            array(5, 45, $feedback5, 5, $this->_now, $this->_now)
        );
        array_unshift($f_data, $f_columns);
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_feedback' => $f_data
        )));
        $save_data = array(
            'num_attempts' => 3
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $expected = array_combine($this->_columns, $this->_data);
        $expected['num_attempts'] = 3;
        $expected['timemodified'] = $this->_now+1;
        $record = $DB->get_record('dragdrop', array('id' => 45));
        $this->assertEquals($expected, (array) $record);

        // test feedback removed
        $records = $DB->get_records('dragdrop_feedback', array('dragdropid' => 45));
        $this->assertEquals(3, count($records));
        $this->assertTrue(array_key_exists(1, $records));
        $this->assertTrue(array_key_exists(2, $records));
        $this->assertTrue(array_key_exists(3, $records));
    }

    /**
     * tests saving the instructional text
     * @global moodle_database $DB
     */
    public function test_save_instructional_text() {
        global $DB;
        $instruct = "<p>Please complete this activity.</p><ul><li>Put</li><li>the words</li><li>in order</li></ul>";
        $save_data = array(
            'instruction' => $instruct
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $expected = array_combine($this->_columns, $this->_data);
        $expected['instruction'] = $instruct;
        $expected['timemodified'] = $this->_now+1;
        $record = $DB->get_record('dragdrop', array('id' => 45));
        $this->assertEquals($expected, (array) $record);
    }

    /**
     * tests saving the instructional text
     * @global moodle_database $DB
     */
    public function test_save_hint_text() {
        global $DB;
        $hint = "<p>Try putting the words.</p><ul><li>in</li><li>the right</li><li>order</li></ul>";
        $save_data = array(
            'hint' => $hint
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $expected = array_combine($this->_columns, $this->_data);
        $expected['hint'] = $hint;
        $expected['timemodified'] = $this->_now+1;
        $record = $DB->get_record('dragdrop', array('id' => 45));
        $this->assertEquals($expected, (array) $record);
    }

    /**
     * tests saving the display labels flag
     * @global moodle_database $DB
     */
    public function test_save_display_labels() {
        global $DB;
        $save_data = array(
            'display_labels' => true
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $expected = array_combine($this->_columns, $this->_data);
        $expected['display_labels'] = 1;
        $expected['timemodified'] = $this->_now+1;
        $record = $DB->get_record('dragdrop', array('id' => 45));
        $this->assertEquals($expected, (array) $record);
    }

    /**
     * tests saving some feedback
     * @global moodle_database $DB
     */
    public function test_save_feedback_correct() {
        global $DB;
        $feedback = "<p>Well done on completing the exercise.</p><ul><li>You</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $save_data = array(
            'feedback_correct' => $feedback
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $expected = array_combine($this->_columns, $this->_data);
        $expected['feedback_correct'] = $feedback;
        $expected['timemodified'] = $this->_now+1;
        $record = $DB->get_record('dragdrop', array('id' => 45));
        $this->assertEquals($expected, (array) $record);
    }

    /**
     * tests saving some feedback
     * @global moodle_database $DB
     */
    public function test_save_feedback_incorrect() {
        global $DB;
        $feedback1 = "<p>That was a terrible first attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $save_data = array(
            'feedback' => (object) array(
                    'attempt' => 1,
                    'html' => $feedback1
                )
        );
        $this->_cut->save(45, $save_data, $this->_now+1);
        $feedback2 = "<p>That was a terrible second attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $save_data = array(
            'feedback' => (object) array(
                    'attempt' => 2,
                    'html' => $feedback2
                )
        );
        $this->_cut->save(45, $save_data, $this->_now+2);
        $records = $DB->get_records('dragdrop_feedback', array('dragdropid' => 45));
        $this->assertEquals(2, count($records));
        $record = current($records);
        $this->assertEquals(1, $record->attempt);
        $this->assertEquals($feedback1, $record->feedback);
        $this->assertEquals(45, $record->dragdropid);
        $this->assertEquals($this->_now+1, $record->timecreated);
        $this->assertEquals($this->_now+1, $record->timemodified);

        $record = next($records);
        $this->assertEquals(2, $record->attempt);
        $this->assertEquals($feedback2, $record->feedback);
        $this->assertEquals(45, $record->dragdropid);
        $this->assertEquals($this->_now+2, $record->timecreated);
        $this->assertEquals($this->_now+2, $record->timemodified);
    }

    /**
     * tests saving some feedback
     * @global moodle_database $DB
     */
    public function test_save_feedback_incorrect_update_existing() {
        global $DB;
        $feedback1 = "<p>That was a terrible first attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $feedback2 = "<p>That was a terrible second attempt.</p><ul><li>You</li><li>should</li><li>put</li><li>the words</li><li>in order</li></ul>";
        $f_columns = array('id', 'dragdropid', 'feedback', 'attempt', 'timemodified', 'timecreated');
        $f_data = array(
            array(1, 45, $feedback1, 1, $this->_now, $this->_now),
            array(2, 45, $feedback2, 2, $this->_now, $this->_now)
        );
        array_unshift($f_data, $f_columns);
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_feedback' => $f_data
        )));
        $feedback_new = "<p>Maybe I was a little harsh</p>";
        $save_data = array(
            'feedback' => (object) array(
                    'attempt' => 2,
                    'html' => $feedback_new
                )
        );
        $this->_cut->save(45, $save_data, $this->_now+3);
        $records = $DB->get_records('dragdrop_feedback', array('dragdropid' => 45));
        $this->assertEquals(2, count($records));
        $record = current($records);
        $this->assertEquals(1, $record->attempt);
        $this->assertEquals($feedback1, $record->feedback);
        $this->assertEquals(45, $record->dragdropid);
        $this->assertEquals($this->_now, $record->timecreated);
        $this->assertEquals($this->_now, $record->timemodified);

        $record = next($records);
        $this->assertEquals(2, $record->attempt);
        $this->assertEquals($feedback_new, $record->feedback);
        $this->assertEquals(45, $record->dragdropid);
        $this->assertEquals($this->_now, $record->timecreated);
        $this->assertEquals($this->_now+3, $record->timemodified);
    }

    public function test_get() {
        $settings = $this->_cut->get(45);
        $this->assertEquals(array(
            'dragdrop' => (object) array_combine($this->_columns, $this->_data),
            'feedback' => array()
        ), $settings);
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_get_does_not_exist() {
        $this->_cut->get(9999);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function test_delete() {
        $this->_cut->delete(45);
    }
}