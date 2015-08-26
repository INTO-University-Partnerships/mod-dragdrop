<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/dragdrop_capabilities.php';

class dragdrop_capabilities_test extends advanced_testcase {


    protected $_user;

    protected $_context;
    protected $_roleid;

    /**
     * @var dragdrop_capabilities
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        global $DB;
        $this->_cut = new dragdrop_capabilities();
        $this->_user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->_roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->_user->id, $course->id, $this->_roleid);
        $this->_context = context_course::instance($course->id);
        assign_capability('mod/dragdrop:view', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->resetAfterTest(true);
    }

    /**
     * tests instantiation
     */
    public function test_word_block_instantiation() {
        $this->assertInstanceOf('dragdrop_capabilities', $this->_cut);
    }

    /**
     * user can be view all data relating to some entities
     */
    public function test_can_view() {
        $this->setUser($this->_user);
        foreach (array('word_block', 'sentence', 'sentence_words', 'settings') as $entity) {
            $this->assertTrue($this->_cut->can_view($entity, $this->_context));
        }
    }

    /**
     * user cannot view nonexistent entities
     */
    public function test_cannot_view_nonexistent() {
        $this->setUser($this->_user);
        $this->assertFalse($this->_cut->can_view('doesn\'t exist', $this->_context));
    }

    /**
     * user cannot view entities that belong to another user: applies to user_attempt and comments
     */
    public function test_cannot_view_other_users_entities() {
        $this->setUser($this->_user);
        $this->assertFalse($this->_cut->can_view('user_attempt', $this->_context));
        $this->assertFalse($this->_cut->can_view('comment', $this->_context));
    }

    /**
     * user can view own entities when own userid provided as third param
     */
    public function test_can_view_own_entities_with_userid() {
        $this->setUser($this->_user);
        $this->assertTrue($this->_cut->can_view('user_attempt', $this->_context, $this->_user->id));
        $this->assertTrue($this->_cut->can_view('comment', $this->_context, $this->_user->id));
    }

    /**
     * user cannot view another user's entities when another userid provided
     */
    public function test_cannot_view_own_entities_with_userid() {
        $this->setUser($this->_user);
        $this->assertFalse($this->_cut->can_view('user_attempt', $this->_context, $this->_user->id + 1));
        $this->assertFalse($this->_cut->can_view('comment', $this->_context, $this->_user->id + 1));
    }

    /**
     * user can view all entities with the right capabilities
     */
    public function test_can_view_other_user_entities() {
        assign_capability('mod/dragdrop:view_all_attempts', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:view_all_comments', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->setUser($this->_user);
        $this->assertTrue($this->_cut->can_view('user_attempt', $this->_context));
        $this->assertTrue($this->_cut->can_view('comment', $this->_context));
    }

    /**
     * user cannot manage any entities without the right caps
     */
    public function test_cannot_manage() {
        $this->setUser($this->_user);
        foreach (array('word_block', 'sentence', 'sentence_words', 'settings') as $entity) {
            $this->assertFalse($this->_cut->can_manage($entity, $this->_context, $this->_user->id));
        }
    }

    /**
     * user cannot manage nonexistent entities
     */
    public function test_cannot_manage_nonexistent() {
        $this->setUser($this->_user);
        $this->assertFalse($this->_cut->can_view('doesn\'t exist', $this->_context));
    }


    /**
     * user can manage entities with the right caps
     */
    public function test_can_manage() {
        assign_capability('mod/dragdrop:sentence', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:sentence_words', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:word_block', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:settings', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->setUser($this->_user);
        foreach (array('word_block', 'sentence', 'sentence_words', 'settings') as $entity) {
            $this->assertFalse($this->_cut->can_manage($entity, $this->_context));
        }
    }

    /**
     * cannot manage entities that involve user data without the right caps
     */
    public function test_cannot_manage_user_entities() {
        assign_capability('mod/dragdrop:comment', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:user_attempt', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->setUser($this->_user);
        foreach (array('comment', 'user_attempt') as $entity) {
            $this->assertFalse($this->_cut->can_manage($entity, $this->_context));
        }
    }

    /**
     * user can manage entities that involve user data when own userid provided
     */
    public function test_can_manage_user_entities_with_own_userid() {
        assign_capability('mod/dragdrop:comment', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:user_attempt', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->setUser($this->_user);
        foreach (array('comment', 'user_attempt') as $entity) {
            $this->assertTrue($this->_cut->can_manage($entity, $this->_context, $this->_user->id));
        }
    }

    /**
     * user cannot manage entities that involve user data when another userid is provided
     */
    public function test_cannot_manage_user_entities_with_another_userid() {
        assign_capability('mod/dragdrop:comment', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:user_attempt', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->setUser($this->_user);
        foreach (array('comment', 'user_attempt') as $entity) {
            $this->assertFalse($this->_cut->can_manage($entity, $this->_context, $this->_user->id + 1));
        }
    }

    /**
     * user can manage all entities with the right capabilities
     */
    public function test_can_manage_other_user_entities() {
        assign_capability('mod/dragdrop:comment', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:user_attempt', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:manage_all_attempts', CAP_ALLOW, $this->_roleid, $this->_context->id);
        assign_capability('mod/dragdrop:manage_all_comments', CAP_ALLOW, $this->_roleid, $this->_context->id);
        $this->setUser($this->_user);
        $this->assertTrue($this->_cut->can_manage('user_attempt', $this->_context));
        $this->assertTrue($this->_cut->can_manage('comment', $this->_context));
    }
}