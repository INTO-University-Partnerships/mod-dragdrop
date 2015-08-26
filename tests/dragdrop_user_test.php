<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/dragdrop_user.php';

class dragdrop_user_test extends advanced_testcase {


    /**
     * @var dragdrop_user
     */
    protected $_cut;

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        $this->_cut = new dragdrop_user();
        $this->resetAfterTest();

    }

    /**
     * tests instantiation
     */
    public function test_word_block_instantiation() {
        $this->assertInstanceOf('dragdrop_user', $this->_cut);
    }

    /**
     * tests getting a user
     */
    public function test_get_user() {
        $user = $this->getDataGenerator()->create_user();
        $result = $this->_cut->get_user($user->id);
        $this->assertEquals(array(
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email
        ), array(
            'id' => $result->id,
            'firstname' => $result->firstname,
            'lastname' => $result->lastname,
            'email' => $result->email
        ));
    }

    /**
     * tests getting a course's tutors when group-mode is 'NOGROUPS'
     */
    public function test_get_tutors_no_groups() {
        global $DB;

        // courses
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $coursec = $this->getDataGenerator()->create_course();

        // roles
        $roleid = $this->getDataGenerator()->create_role(array('shortname' => 'tutor'));
        $student_roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // enrol all users as tutors on the first course,
        // half as tutors on the second course
        // and the other half as students on the third course
        $tutors = array();
        foreach (range(1, 4) as $i) {
            $tutors[$i] = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user(
                $tutors[$i]->id,
                $coursea->id,
                $roleid
            );
            if ($i % 2) {
                $this->getDataGenerator()->enrol_user($tutors[$i]->id, $courseb->id, $roleid);
            }
            else {
                $this->getDataGenerator()->enrol_user($tutors[$i]->id, $coursec->id, $student_roleid);
            }
        }

        // create one user enrolled only as a student on each course
        $student = $this->getDataGenerator()->create_user();
        foreach ([$coursea, $courseb, $coursec] as $course) {
            $this->getDataGenerator()->enrol_user($student->id, $course->id, $student_roleid);
        }

        // First course
        $results = $this->_cut->get_course_tutors($coursea->id, $student->id, NOGROUPS);
        foreach ($tutors as $tutor) {
            $this->assertArrayHasKey($tutor->id, $results);
        }

        // Second course
        $results = $this->_cut->get_course_tutors($courseb->id, $student->id, NOGROUPS);
        $this->assertArrayHasKey($tutors[1]->id, $results);
        $this->assertArrayHasKey($tutors[3]->id, $results);

        // Third course
        $results = $this->_cut->get_course_tutors($coursec->id, $student->id, NOGROUPS);
        $this->assertEmpty($results);
    }

    /**
     * tests getting a course's tutors when group-mode is 'SEPARATEGROUPS'
     */
    public function test_get_tutors_separate_groups() {
        global $DB;

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // roles
        $tutor_roleid = $this->getDataGenerator()->create_role(array('shortname' => 'tutor'));
        $student_roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // create 3 tutors
        $tutors = array();
        foreach (range(1, 3) as $i) {
            $tutors[$i] = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($tutors[$i]->id, $course->id, $tutor_roleid);
        }

        // create 2 groups
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        # tutor 1 is in group 1, tutor 2 is in groups 1 & 2, tutor 3 is in group 2
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $tutors[1]->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $tutors[2]->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $tutors[2]->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $tutors[3]->id]);

        // create one student in group 2
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $student_roleid);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $student->id]);

        // First course
        $results = $this->_cut->get_course_tutors($course->id, $student->id, SEPARATEGROUPS);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey($tutors[2]->id, $results);
        $this->assertArrayHasKey($tutors[3]->id, $results);
    }

    /**
     * group mode is 'NOGROUPS'
     */
    public function test_has_group_access_no_groups() {
        // create 2 users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // enrol the users
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // login user1
        $this->setUser($user1);

        $this->assertTrue($this->_cut->has_group_access(NOGROUPS, $user2->id, $course->id));
    }

    /**
     * group mode is 'SEPARATEGROUPS'; userid is that of the logged-in user
     */
    public function test_has_group_access_separate_groups_logged_in_user() {
        // create 1 user
        $user = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // enrol the user
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // login user
        $this->setUser($user);

        $this->assertTrue($this->_cut->has_group_access(SEPARATEGROUPS, $user->id, $course->id));
    }

    /**
     * group mode is 'SEPARATEGROUPS'; userid is not that of the logged-in user
     */
    public function test_has_group_access_separate_groups_other_user() {
        // create 2 users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // enrol the users
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // login user1
        $this->setUser($user1);

        $this->assertFalse($this->_cut->has_group_access(SEPARATEGROUPS, $user2->id, $course->id));
    }

    /**
     * group mode is 'SEPARATEGROUPS'; userid is not that of the logged-in user;
     * user1 belongs to the same group as user2
     */
    public function test_has_group_access_same_group() {
        // create 2 users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // enrol the users
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // login user1
        $this->setUser($user1);

        // create a group
        $group = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id
        ));
        $this->getDataGenerator()->create_group_member(array(
            'groupid' => $group->id,
            'userid' => $user1->id
        ));
        $this->getDataGenerator()->create_group_member(array(
            'groupid' => $group->id,
            'userid' => $user2->id
        ));
        $this->assertTrue($this->_cut->has_group_access(SEPARATEGROUPS, $user2->id, $course->id));
    }

    /**
     * group mode is 'SEPARATEGROUPS'; userid is not that of the logged-in user;
     * user1 belongs to a different group to user2
     */
    public function test_has_group_access_different_group() {
        // create 2 users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // enrol the users
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // login user1
        $this->setUser($user1);

        // create 2 groups
        $group1 = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id
        ));
        $group2 = $this->getDataGenerator()->create_group(array(
            'courseid' => $course->id
        ));
        $this->getDataGenerator()->create_group_member(array(
            'groupid' => $group1->id,
            'userid' => $user1->id
        ));
        $this->getDataGenerator()->create_group_member(array(
            'groupid' => $group2->id,
            'userid' => $user2->id
        ));
        $this->assertFalse($this->_cut->has_group_access(SEPARATEGROUPS, $user2->id, $course->id));
    }

}
