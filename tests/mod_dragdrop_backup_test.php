<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/dragdrop_backup_controller.php';

/**
 * @see http://docs.moodle.org/dev/Backup_2.0_for_developers
 */
class mod_dragdrop_backup_test extends advanced_testcase {

    /**
     * @var stdClass
     */
    protected $_course_module;

    /**
     * @var object
     */
    protected $_course;

    /**
     * @var integer
     */
    protected $_instanceid;

    /**
     * @var integer
     */
    protected $_t0;

    /**
     * @var dragdrop_backup_controller
     */
    protected $_cut;

    /**
     * @var array
     */
    protected $_word_blocks;

    /**
     * @var array
     */
    protected $_words_to_sectionnums;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        global $CFG, $DB;
        $CFG->keeptempdirectoriesonbackup = true;

        // record initial time
        $this->_t0 = time();

        // create course and some course modules (of which we're testing the last)
        $this->_course = $this->getDataGenerator()->create_course(array(
            'numsections' => 5,
        ), array (
            'createsections' => true,
        ));
        foreach (array('forum', 'forum', 'dragdrop', 'dragdrop') as $module) {
            $this->getDataGenerator()->create_module($module, array(
                'course' => $this->_course->id,
            ));
        }
        $this->_course_module = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $this->_course->id,
            'header' => array(
                'format' => FORMAT_HTML,
                'text' => '<p>My lovely header</p>'
            ),
            'footer' => array(
                'format' => FORMAT_HTML,
                'text' => '<p>My lovely footer</p>'
            ),
            'instruction' => '<p>A very helpful introduction</p>',
            'feedback_correct' => '<p>Some encouraging feedback</p>',
            'hint' => '<p>A not particularly informative hint</p>',
            'num_attempts' => 5
        ));

        // set the course module id and the user id
        $this->_instanceid = $instanceid = $this->_course_module->id;

        $dtb = mktime(9, 0, 0, 8, 27, 2014);

        // seed the dragdrop tables
        $this->loadDataSet($this->createArrayDataSet(array(
                'dragdrop_word_block' => array(
                    array('id', 'wordblock', 'dragdropid', 'timecreated', 'timemodified'),
                    array(1, 'onesingleword', $instanceid, $dtb, $dtb),
                    array(2, 'a series of words', $instanceid, $dtb, $dtb),
                    array(3, 'more words', $instanceid, $dtb, $dtb),
                    array(4, 'in the database', $instanceid, $dtb, $dtb),
                    array(5, 'wrong dragdropid', $instanceid+1, $dtb, $dtb)
                ),
                'dragdrop_sentence_word_block' => array(
                    array('id', 'wordblockid', 'sentenceid', 'position', 'xcoord', 'ycoord', 'timecreated'),
                    array(1, 1, 1, 2, 0, 0, $dtb),
                    array(2, 1, 2, 1, 0, 0, $dtb),
                    array(3, 2, 1, 1, 0, 0, $dtb),
                    array(4, 2, 2, 2, 0, 0, $dtb),
                    array(5, 1, 3, 3, 0, 0, $dtb),
                    array(6, 2, 3, 1, 0, 0, $dtb),
                    array(7, 3, 3, 2, 0, 0, $dtb),
                    array(8, 4, 3, 4, 0, 0, $dtb)
                ),
                'dragdrop_sentence' => array(
                    array('id', 'mark', 'instanceid', 'timecreated', 'timemodified'),
                    array(1, 10, $instanceid, $dtb, $dtb),
                    array(2, 20, $instanceid, $dtb, $dtb),
                    array(3, 40, $instanceid, $dtb, $dtb),
                    array(4, 50, $instanceid+1, $dtb, $dtb)
                ))
        ));
        $feedback1 = "<p>That was a terrible first attempt.</p>";
        $feedback2 = "<p>That was a terrible second attempt.</p>";
        $feedback3 = "<p>That was a terrible third attempt.</p>";
        $feedback4 = "<p>That was a terrible fourth attempt.</p>";
        $feedback5 = "<p>That was a terrible fifth attempt.</p>";
        $f_columns = array('id', 'dragdropid', 'feedback', 'attempt', 'timemodified', 'timecreated');
        $f_data = array(
            array(1, $instanceid, $feedback1, 1, $dtb, $dtb),
            array(2, $instanceid, $feedback2, 2, $dtb, $dtb),
            array(3, $instanceid, $feedback3, 3, $dtb, $dtb),
            array(4, $instanceid, $feedback4, 4, $dtb, $dtb),
            array(5, $instanceid, $feedback5, 5, $dtb, $dtb)
        );
        array_unshift($f_data, $f_columns);
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop_feedback' => $f_data
        )));

        // create an instance of the class under test
        $this->_cut = new dragdrop_backup_controller(
            backup::TYPE_1ACTIVITY,
            $this->_course_module->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $userid = 2
        );

        $this->resetAfterTest(true);
    }

    /**
     * tests instantiation of a backup controller
     */
    public function test_backup_controller_instantiation() {
        $this->assertInstanceOf('backup_controller', $this->_cut);
    }

    /**
     * tests executing a plan creates a single directory in dataroot in /temp/backup
     */
    public function test_execute_plan_creates_one_directory() {
        global $CFG;
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $this->assertCount(0, $child_directories);
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $this->assertCount(1, $child_directories);
    }

    /**
     * tests the backupid corresponds to a directory in dataroot in /temp/backup
     */
    public function test_get_backupid_matches_directory() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $this->assertCount(1, $child_directories);
        $this->assertEquals($child_directories[0], $this->_cut->get_backupid());
    }

    /**
     * tests executing a plan creates a single course module subdirectory in dataroot in /temp/backup/{backupid}/activities/dragdrop_{coursemodule}
     */
    public function test_execute_plan_creates_dragdrop_subdirectory() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $dir = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/dragdrop_' . $this->_course_module->cmid;
        $this->assertFileExists($dir);
    }

    /**
     * tests executing a plan for a dragdrop course module creates a module.xml file
     */
    public function test_execute_plan_creates_module_xml() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $file = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/dragdrop_' . $this->_course_module->cmid . '/module.xml';
        $this->assertFileExists($file);
    }

    /**
     * tests executing a plan for a dragdrop course module creates a dragdrop.xml file
     */
    public function test_execute_plan_creates_dragdrop_xml() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $file = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/dragdrop_' . $this->_course_module->cmid . '/dragdrop.xml';
        $this->assertFileExists($file);
    }

    /**
     * tests executing a plan for a dragdrop course module creates a dragdrop.xml file with the expected content
     */
    public function test_execute_plan_creates_expected_dragdrop_xml_content() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $file = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/dragdrop_' . $this->_course_module->cmid . '/dragdrop.xml';
        $xml = simplexml_load_file($file);

        // activity
        $this->assertEquals($this->_course_module->id, $xml['id']);
        $this->assertSame($this->_course_module->cmid, (integer)$xml['moduleid']);
        $this->assertEquals('dragdrop', $xml['modulename']);
        $this->assertEquals($this->_course_module->name, $xml->dragdrop->name);
        $this->assertEquals('<p>My lovely header</p>', $xml->dragdrop->header);
        $this->assertEquals('<p>A very helpful introduction</p>', $xml->dragdrop->instruction);
        $this->assertEquals('<p>Some encouraging feedback</p>', $xml->dragdrop->feedback_correct);
        $this->assertEquals('<p>A not particularly informative hint</p>', $xml->dragdrop->hint);
        $this->assertSame(5, (integer)$xml->dragdrop->num_attempts);

        // word blocks
        $this->assertEquals(4, count($xml->xpath("//dragdrop/words")));
        $word1 = $xml->xpath("//dragdrop/words[@id='1']")[0];
        $this->assertEquals('onesingleword', $word1->wordblock);
        $word2 = $xml->xpath("//dragdrop/words[@id='2']")[0];
        $this->assertEquals('a series of words', $word2->wordblock);
        $word3 = $xml->xpath("//dragdrop/words[@id='3']")[0];
        $this->assertEquals('more words', $word3->wordblock);
        $word4 = $xml->xpath("//dragdrop/words[@id='4']")[0];
        $this->assertEquals('in the database', $word4->wordblock);

        // sentences
        $this->assertEquals(3, count($xml->xpath("//dragdrop/sentences")));
        $sentence1 = $xml->xpath("//dragdrop/sentences[@id='1']")[0];
        $this->assertSame(10, (integer)$sentence1->mark);
        $sentence2 = $xml->xpath("//dragdrop/sentences[@id='2']")[0];
        $this->assertSame(20, (integer)$sentence2->mark);
        $sentence3 = $xml->xpath("//dragdrop/sentences[@id='3']")[0];
        $this->assertSame(40, (integer)$sentence3->mark);

        // feedback
        $this->assertEquals(5, count($xml->xpath("//dragdrop/feedback")));
        $feedback1 = $xml->xpath("//dragdrop/feedback[@id='1']")[0];
        $this->assertEquals("<p>That was a terrible first attempt.</p>", $feedback1->feedback);
        $feedback2 = $xml->xpath("//dragdrop/feedback[@id='2']")[0];
        $this->assertEquals("<p>That was a terrible second attempt.</p>", $feedback2->feedback);
        $feedback3 = $xml->xpath("//dragdrop/feedback[@id='3']")[0];
        $this->assertEquals("<p>That was a terrible third attempt.</p>", $feedback3->feedback);
        $feedback4 = $xml->xpath("//dragdrop/feedback[@id='4']")[0];
        $this->assertEquals("<p>That was a terrible fourth attempt.</p>", $feedback4->feedback);
        $feedback5 = $xml->xpath("//dragdrop/feedback[@id='5']")[0];
        $this->assertEquals("<p>That was a terrible fifth attempt.</p>", $feedback5->feedback);

        // word blocks in sentence 1
        $this->assertEquals(2, count($xml->xpath("//dragdrop/sentences[@id=1]/sentence_words")));
        $sword1 = $xml->xpath("//dragdrop/sentences[@id=1]/sentence_words[@id=1]")[0];
        $this->assertSame(1, (integer)$sword1->wordblockid);
        $sword2 = $xml->xpath("//dragdrop/sentences[@id=1]/sentence_words[@id=3]")[0];
        $this->assertSame(2, (integer)$sword2->wordblockid);

        // word blocks in sentence 2
        $this->assertEquals(2, count($xml->xpath("//dragdrop/sentences[@id=2]/sentence_words")));
        $sword3 = $xml->xpath("//dragdrop/sentences[@id=2]/sentence_words[@id=2]")[0];
        $this->assertSame(1, (integer)$sword3->wordblockid);
        $sword4 = $xml->xpath("//dragdrop/sentences[@id=2]/sentence_words[@id=4]")[0];
        $this->assertSame(2, (integer)$sword4->wordblockid);


        // word blocks in sentence 3
        $this->assertEquals(4, count($xml->xpath("//dragdrop/sentences[@id=3]/sentence_words")));
        $sword5 = $xml->xpath("//dragdrop/sentences[@id=3]/sentence_words[@id=5]")[0];
        $this->assertSame(1, (integer)$sword5->wordblockid);
        $sword6 = $xml->xpath("//dragdrop/sentences[@id=3]/sentence_words[@id=6]")[0];
        $this->assertSame(2, (integer)$sword6->wordblockid);
        $sword7 = $xml->xpath("//dragdrop/sentences[@id=3]/sentence_words[@id=7]")[0];
        $this->assertSame(3, (integer)$sword7->wordblockid);
        $sword8 = $xml->xpath("//dragdrop/sentences[@id=3]/sentence_words[@id=8]")[0];
        $this->assertSame(4, (integer)$sword8->wordblockid);
    }

    /**
     * tests encoding content links encodes the /mod/dragdrop/index.php URL
     */
    public function test_encode_content_links_encodes_mod_dragdrop_index_url() {
        global $CFG;
        $link = $CFG->wwwroot . '/mod/dragdrop/index.php?id=123';
        $content = '<p>hello</p><a href="' . $link . '">click here</a><p>world</p>';
        $result = backup_dragdrop_activity_task::encode_content_links($content);
        $encoded_link = '$@DRAGDROPINDEX*123@$';
        $this->assertSame('<p>hello</p><a href="' . $encoded_link . '">click here</a><p>world</p>', $result);
    }

    /**
     * tests encoding content links encodes the /mod/dragdrop/view.php URL
     */
    public function test_encode_content_links_encodes_mod_dragdrop_view_url() {
        global $CFG;
        $link = $CFG->wwwroot . '/mod/dragdrop/view.php?id=123';
        $content = '<p>hello</p><a href="' . $link . '">click here</a><p>world</p>';
        $result = backup_dragdrop_activity_task::encode_content_links($content);
        $encoded_link = '$@DRAGDROPVIEWBYID*123@$';
        $this->assertSame('<p>hello</p><a href="' . $encoded_link . '">click here</a><p>world</p>', $result);
    }

    /**
     * returns an array of directories within the given directory (not recursively)
     * @param string $dir
     * @return array
     */
    protected static function _get_child_directories($dir) {
        $retval = array();
        $ignore = array('.', '..');
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if (is_dir($dir . '/' . $entry) && !in_array($entry, $ignore)) {
                    $retval[] = $entry;
                }
            }
            closedir($handle);
        }
        return $retval;
    }
}
