<?php

defined('MOODLE_INTERNAL') || die();

class dragdrop_user {

    /**
     * constructor
     */
    public function __construct() {
        // empty
    }

    /**
     * get some userdata
     * @param integer $userid
     * @return mixed
     */
    public function get_user($userid) {
        global $DB;
        return $DB->get_record('user', array(
            'id'=>$userid),
            'id, firstname, lastname, email',
            MUST_EXIST
        );
    }

    /**
     * get tutors for a given course
     * @param integer $courseid
     * @return array
     */
    public function get_course_tutors($courseid, $userid, $group_mode) {
        global $DB;
        $group_sql = $group_mode == SEPARATEGROUPS ? sprintf('AND (%s) > 0', $this->_get_group_subquery_sql()) : '';
        $sql = <<<SQL
            SELECT u.id, u.firstname, u.lastname, u.email
            FROM {context} ctx
            INNER JOIN {role_assignments} ra
                ON ra.contextid = ctx.id
            INNER JOIN {role} r
                ON r.id = ra.roleid
                AND r.shortname = :role_shortname
            INNER JOIN {user} u
                ON u.id = ra.userid
                AND u.deleted = 0
            WHERE ctx.instanceid = :courseid
                AND ctx.contextlevel = :context_course
                $group_sql
            ORDER BY u.id
SQL;
        $params = array(
            'courseid' => $courseid,
            'context_course' => CONTEXT_COURSE,
            'role_shortname' => 'tutor'
        );
        if ($group_mode == SEPARATEGROUPS) {
            $params['userid'] = $userid;
        }
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * @return string
     */
    protected function _get_group_subquery_sql() {
        return <<<SQL
            SELECT COUNT(gm1.userid)
            FROM {groups} g
            INNER JOIN {groups_members} gm1 ON gm1.groupid = g.id AND gm1.userid = :userid
            INNER JOIN {groups_members} gm2 ON gm2.groupid = g.id
            WHERE g.courseid = ctx.instanceid AND gm2.userid = u.id
SQL;
    }

    /**
     * whether a user has access based on group mode
     * @param integer $group_mode
     * @param integer $userid
     * @param integer $courseid
     * @return bool
     */
    public function has_group_access($group_mode, $userid, $courseid) {
        global $USER;
        if ($userid == $USER->id || $group_mode != SEPARATEGROUPS) {
            return true;
        }
        return $this->users_are_members_of_the_same_group($courseid, $userid, $USER->id);
    }

    /**
     * whether 2 users are members of the same group(s)
     * @global moodle_database
     * @param integer $courseid
     * @param integer $userid1
     * @param integer $userid2
     * @return bool
     */
    public function users_are_members_of_the_same_group($courseid, $userid1, $userid2) {
        global $DB;
        $sql = <<<SQL
            SELECT *
            FROM {groups} g
            INNER JOIN {groups_members} gm1 ON gm1.groupid = g.id
            INNER JOIN {groups_members} gm2 ON gm2.groupid = g.id
            WHERE g.courseid = :courseid
            AND gm1.userid = :userid1
            AND gm2.userid = :userid2
SQL;
        return $DB->record_exists_sql($sql, array(
            'courseid' => $courseid,
            'userid1' => $userid1,
            'userid2' => $userid2
        ));
    }

}
