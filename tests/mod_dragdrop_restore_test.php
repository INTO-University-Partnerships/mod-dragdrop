<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/dragdrop_restore_controller.php';

/**
 * @see http://docs.moodle.org/dev/Restore_2.0_for_developers
 */
class mod_dragdrop_restore_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_categoryid;

    /**
     * @var integer
     */
    protected $_userid;

    /**
     * @var integer
     */
    protected $_courseid;

    /**
     * @var mod_dragdrop_restore_controller
     */
    protected $_cut;

    /**
     * @var moodle_transaction
     */
    protected $_transaction;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        global $CFG, $DB;

        // copy the 'restoreme' directory to dataroot
        $src = __DIR__ . '/restoreme/';
        check_dir_exists($CFG->dataroot . '/temp/backup/');
        $dest = $CFG->dataroot . '/temp/backup/';
        shell_exec("cp -r {$src} {$dest}");

        // set parameters, create a course to restore into
        $folder = 'restoreme';
        $this->_categoryid = 1;
        $this->_userid = 2;
        $this->_courseid = restore_dbops::create_new_course('Restored course fullname', 'Restored course shortname', $this->_categoryid);

        // create an instance of the class under test
        $this->_transaction = $DB->start_delegated_transaction();
        $this->_cut = new dragdrop_restore_controller(
            $folder,
            $this->_courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            $this->_userid,
            backup::TARGET_NEW_COURSE
        );

        $this->resetAfterTest(true);
    }

    /**
     * tests instantiation of a restore controller
     */
    public function test_restore_controller_instantiation() {
        $this->assertInstanceOf('restore_controller', $this->_cut);
    }

    /**
     * tests the plan has no missing modules
     */
    public function test_restore_plan_has_no_missing_modules() {
        $this->assertFalse($this->_cut->get_plan()->is_missing_modules());
    }

    /**
     * tests that the precheck returns true as expected
     */
    public function test_execute_precheck_returns_true() {
        $result = $this->_cut->execute_precheck();
        $this->assertTrue($result);
        $this->_cut->execute_plan();
        $this->_transaction->allow_commit();
    }

    /**
     * tests that executing the plan renames the destination course
     * @global moodle_database $DB
     */
    public function test_execute_plan_renames_destination_course() {
        global $DB;

        $before_courseid = (integer)$DB->get_field('course', 'id', array(
            'fullname' => 'Restored course fullname',
            'shortname' => 'Restored course shortname',
        ), MUST_EXIST);
        $this->assertGreaterThanOrEqual(1, $before_courseid);

        $this->_cut->execute_precheck();
        $this->_cut->execute_plan();
        $this->_transaction->allow_commit();

        $after_courseid = (integer)$DB->get_field('course', 'id', array(
            'fullname' => '001',
            'shortname' => '001',
        ), MUST_EXIST);
        $this->assertGreaterThanOrEqual(1, $after_courseid);

        $this->assertSame($this->_courseid, $before_courseid);
        $this->assertSame($before_courseid, $after_courseid);
    }

    /**
     * tests that executing the plan restores the module
     * @global moodle_database $DB
     */
    public function test_execute_plan_restores_module() {
        global $DB;

        $this->_cut->execute_precheck();
        $this->_cut->execute_plan();
        $this->_transaction->allow_commit();

        // module level data
        $this->assertEquals(1, $DB->count_records('dragdrop'));
        $data = (array)$DB->get_record('dragdrop', array(), '*', MUST_EXIST);
        $this->assertSame('Drag and drop', $data['name']);
        $this->assertContains('Complete the task', $data['instruction']);
        $this->assertContains('That was good I think', $data['feedback_correct']);
        $this->assertContains('Work faster', $data['hint']);
        $this->assertContains('Course link in header', $data['header']);
        $this->assertContains('Course link in footer', $data['footer']);
        $this->assertContains('course/view.php?id=' . $this->_courseid, $data['header']);
        $this->assertContains('course/view.php?id=' . $this->_courseid, $data['footer']);
        $this->assertEquals(5, $data['num_attempts']);
        $this->assertEquals(1, $data['display_labels']);

        // word blocks
        $words = $DB->get_records('dragdrop_word_block', array('dragdropid' => $data['id']), '', 'id, tagid, wordblock');
        $this->assert_wordblocks_restored($words);

        // sentences
        $sentences = $DB->get_records('dragdrop_sentence', array('instanceid' => $data['id']));
        $this->assertEquals(1, count($sentences));

        // sentence words
        $sentence = current($sentences);
        $swords = $DB->get_records('dragdrop_sentence_word_block', array('sentenceid' => $sentence->id), '', 'id, position, xcoord, ycoord, wordblockid');
        $this->assert_sentence_words_restored($swords, $words);

        // feedback
        $feedback = $DB->get_records('dragdrop_feedback', array('dragdropid' => $data['id']), '', 'attempt, feedback');
        $this->assert_feedback_restored($feedback);
    }

    /**
     * tests feedback is restored
     * @param $data
     */
    protected function assert_feedback_restored($data) {
        $this->assertEquals(5, count($data));
        $expected = array (
            1 => 'Not very good I suppose',
            2 => 'Really bad',
            3 => 'Getting words',
            4 => 'Stop attempting this',
            5 => 'Please go to a different university'
        );
        foreach ($expected as $attempt => $feedback) {
            $this->assertTrue(array_key_exists($attempt, $data));
            $this->assertContains($expected[$attempt], $feedback);
        }
    }

    /**
     * tests words within a sentence are restored
     * @param $data
     * @param words
     */
    protected function assert_sentence_words_restored($data, $words) {
        $this->assertEquals(6, count($data));
        $expected = array(
                (object) array(
                    'position' => 1,
                    'xcoord' => 2,
                    'ycoord' => 62
                ),
                (object) array(
                    'position' => 2,
                    'xcoord' => 98,
                    'ycoord' => 62
                ),
                (object) array(
                    'position' => 3,
                    'xcoord' => 138,
                    'ycoord' => 62
                ),
                (object) array(
                    'position' => 4,
                    'xcoord' => 203,
                    'ycoord' => 62
                ),
                (object) array(
                    'position' => 5,
                    'xcoord' => 244,
                    'ycoord' => 62
                ),
                (object) array(
                    'position' => 6,
                    'xcoord' => 337,
                    'ycoord' => 62
                )
            );

        $mappings = array('Something', 'is', 'rotten', 'in', 'the state of', 'Denmark.');

        foreach ($data as $d) {
            $wordblock = $words[$d->wordblockid];
            unset($d->id);
            unset($d->wordblockid);
            $key = array_search($d, $expected);
            $this->assertTrue($key !== false);

            // referential integrity
            $this->assertEquals($mappings[$key], $wordblock->wordblock);
            unset($expected[$key]);
        }
    }

    /**
     * tests word blocks are restored
     * @param $data
     */
    protected function assert_wordblocks_restored($data) {
        $this->assertEquals(6, count($data));
        $expected = array(
            (object) array(
                'wordblock' => 'Denmark.',
                'tagid' => 1
            ),
            (object) array(
                'wordblock' => 'the state of',
                'tagid' => 8
            ),
            (object) array(
                'wordblock' => 'in',
                'tagid' => 6
            ),
            (object) array(
                'wordblock' => 'rotten',
                'tagid' => 5
            ),
            (object) array(
                'wordblock' => 'is',
                'tagid' => 4
            ),
            (object) array(
                'wordblock' => 'Something',
                'tagid' => 1
            )
        );
        foreach ($data as $d) {
            unset($d->id);
            $key = array_search($d, $expected);
            $this->assertTrue($key !== false);
            unset($expected[$key]);
        }
    }
}
