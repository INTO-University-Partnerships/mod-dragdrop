<?php
namespace dragdrop;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/entity.php');

class user_attempt implements entity {

    const DEFAULT_NUM_ATTEMPTS = 3;

    /**
     * function for retrieving a model class
     * @var callable
     */
    protected $_get_model;

    /**
     * either NOGROUPS or SEPARATEGROUPS
     * @var integer
     */
    protected $_group_mode = NOGROUPS;

    /**
     * @var integer
     */
    protected $_userid;

    /**
     * constructor
     */
    public function __construct($get_model) {
        $this->_get_model = $get_model;
    }

    /**
     * @param integer $group_mode - either NOGROUPS or SEPARATEGROUPS
     */
    public function set_groupmode($group_mode) {
        $this->_group_mode = $group_mode;
    }

    /**
     * @param integer $userid
     */
    public function set_userid($userid) {
        $this->_userid = $userid;
    }

    public function get($id) {
        global $DB;
        $this->must_exist($id);
        $data = $DB->get_record('dragdrop_attempt', array('id' => $id), '*', MUST_EXIST);
        $data->contributing_attempts = $DB->count_records('dragdrop_attempt', array(
            'userid' => $data->userid,
            'dragdropid' => $data->dragdropid,
            'reset' => 0
        ));
        return $data;
    }

    public function get_all($instanceid, $params = null) {
        global $DB, $USER;
        $userid = isset($params['userid']) ? $params['userid']: $USER->id;
        $sql = "SELECT a.*, u.firstname, u.lastname
                FROM {dragdrop_attempt} a
                JOIN {user} u
                ON u.id = a.userid
                WHERE a.userid = :userid
                AND a.dragdropid = :dragdropid";
        $records = $DB->get_records_sql($sql, array('userid' => $userid, 'dragdropid' => $instanceid));
        foreach ($records as &$record) {
            $record->timecreated_formatted = userdate($record->timecreated);
        }
        return $records;
    }

    /**
     * @param integer $instanceid
     * @param integer $userid
     * @return array
     */
    public function get_attempts_for_user($instanceid, $userid) {
        global $DB;
        $sql = "SELECT a.*, u.firstname, u.lastname
                FROM {dragdrop_attempt} a
                JOIN {user} u
                ON u.id = a.userid
                WHERE a.userid = :userid
                AND a.dragdropid = :dragdropid";
        $records = $DB->get_records_sql($sql, array('userid' => $userid, 'dragdropid' => $instanceid));
        foreach ($records as &$record) {
            $record->timecreated_formatted = userdate($record->timecreated);
        }
        return $records;
    }

    public function save($instanceid, $data, $now, $id = 0) {
        global $DB, $USER;
        $attempt = $this->get_num_user_attempts($instanceid, $USER->id);
        $max_attempts = (int)$DB->get_field('dragdrop', 'num_attempts', array('id' => $instanceid));

        if ($attempt >= $max_attempts) {
            throw new BadRequestHttpException(get_string('maximum_attempts_reached', 'mod_dragdrop'));
        }

        $words = $data['wordblocks'];
        $sentence = $this->create_sentence($instanceid, $words);
        return $DB->insert_record('dragdrop_attempt', (object)array(
            'dragdropid' => $instanceid,
            'attempt' => $attempt + 1,
            'sentence' => $sentence,
            'correct' => (int)$this->is_attempt_correct($instanceid, $sentence),
            'userid' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now
        ));
    }

    /**
     * @param integer $instanceid
     * @param integer $userid
     * @return int
     */
    protected function get_num_user_attempts($instanceid, $userid) {
        global $DB;
        $sql = "SELECT COUNT(a.id)
                FROM {dragdrop_attempt} a
                WHERE a.dragdropid = :instanceid
                AND a.userid = :userid
                AND a.reset = 0";
        $params = array(
            'instanceid' => $instanceid,
            'userid' => $userid
        );
        return (int)$DB->get_field_sql($sql, $params);
    }

    /**
     * gets the attempt as a string of words in a sentence
     * @param $id
     * @param $words
     * @return string
     */
    protected function create_sentence($id, $words) {
        global $DB;
        $sentence = array();
        $all_words = $DB->get_records_menu('dragdrop_word_block', array('dragdropid' => $id), '', 'id, wordblock');
        foreach ($words as $word) {
            $sentence[] = $all_words[$word->wordblockid];
        }
        return implode(" ", $sentence);
    }

    /**
     * whether an attempt is correct against any of the valid sentences
     * @param $instanceid
     * @param $attempt
     * @return bool
     */
    protected function is_attempt_correct($instanceid, $attempt) {
        $sentence_model = call_user_func($this->_get_model, 'sentence');
        $answers = $sentence_model->get_all($instanceid);
        foreach ($answers as $answer) {
            if ($attempt == $this->create_sentence($instanceid, $answer->wordblocks)) {
                return true;
            }
        }
        return false;
    }

    public function delete($id) {
        global $DB;
        $this->must_exist($id);
        $DB->delete_records('dragdrop_attempt', array('id' => $id));
    }

    public function must_exist($id) {
        global $DB;
        $DB->get_field('dragdrop_attempt', 'id', array(
            'id' => $id,
        ), MUST_EXIST);
    }

    /**
     * get users with aggregate data about attempts made (users must either be enrolled on the course or have made an attempt)
     * @global moodle_database $DB
     * @param integer $instanceid
     * @param string $q
     * @param string $sort
     * @param string $direction
     * @param integer $limitfrom
     * @param integer $limitnum
     * @return array
     */
    public function get_users_with_attempt_count($instanceid, $q="", $sort=NULL, $direction='ASC', $limitfrom=0, $limitnum=0) {
        global $DB;
        $grouped = ($this->_group_mode === SEPARATEGROUPS);

        # enrolled users sql and params
        $enrol_sql = $this->get_enrolment_subquery_sql($grouped);
        $enrol_params = ($grouped) ? [$this->_userid, $instanceid] : [$instanceid];

        # attempts sql and params
        $attempts_sql = $this->get_attempts_aggregate_subquery_sql();
        $attempt_params = [$instanceid];

        # reset sql and params
        $reset_sql = $this->get_attempts_reset_subquery_sql();
        $reset_params = [$instanceid];

        # query sql and params
        list($q_sql, $q_params) = $this->_get_q_query_string($q);

        # order by sql
        $order_by = $this->_get_user_attempts_order_by_sql($sort, $direction);

        $sql = <<<SQL
            SELECT u.id, u.firstname, u.lastname, a.lastattempt, a.numattempts, r.numattempts as numreset, a.completed
            FROM {user} u
            INNER JOIN ($enrol_sql) e
              ON u.id = e.userid
            LEFT JOIN ($attempts_sql) a
              ON a.userid = u.id
            LEFT JOIN ($reset_sql) r
              ON r.userid = u.id
            WHERE (e.userid IS NOT NULL OR a.userid IS NOT NULL)
              AND u.deleted = 0
              $q_sql
            ORDER BY $order_by
SQL;
        $params = array_merge($enrol_params, $attempt_params, $reset_params, $q_params);
        $results = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        foreach ($results as &$result) {
            $result->lastattempt_formatted = ($result->lastattempt) ? userdate($result->lastattempt) : "";
        }
        return array_values($results);
    }

    /**
     * @param string $sort
     * @param string $direction
     * @return string
     */
    protected function _get_user_attempts_order_by_sql($sort, $direction) {
        $sortings = array(
            'lastattempt' => $this->nulls($direction, "a.lastattempt") . ", a.lastattempt {$direction}, u.lastname, u.firstname",
            'user' => "u.lastname  {$direction}, u.firstname",
            'numattempts' => $this->nulls($direction, "a.numattempts") . ", a.numattempts {$direction}, u.lastname, u.firstname",
            'completed' => $this->nulls($direction, "a.completed") . ", a.numattempts {$direction}, u.lastname, u.firstname"
        );
        return $sortings[($sort == null || !array_key_exists($sort, $sortings)) ? 'user' : $sort];
    }

    /**
     * sql to ensure nulls are sorted first for ascending, and last for descending queries
     * @param $direction
     * @param $column
     * @return string
     */
    protected function nulls($direction, $column) {
        return ($direction == 'ASC') ? "({$column} IS NOT NULL)" : "({$column} IS NULL)";
    }

    /**
     * get the total number of users (either enrolled or just having made an attempt)
     * @global moodle_database $DB
     * @param integer $instanceid
     * @param string $q
     * @return integer
     */
    public function get_total_users($instanceid, $q = "") {
        global $DB;
        $grouped = ($this->_group_mode === SEPARATEGROUPS);

        # enrolled users sql and params
        $enrol_sql = $this->get_enrolment_subquery_sql($grouped);
        $enrol_params = ($grouped) ? [$this->_userid, $instanceid] : [$instanceid];

        # attempts sql and params
        $attempts_sql = $this->get_attempts_aggregate_subquery_sql();
        $attempt_params = [$instanceid];

        # query sql and params
        list($q_sql, $q_params) = $this->_get_q_query_string($q);

        $sql = <<<SQL
            SELECT COUNT(u.id) as count
            FROM {user} u
            INNER JOIN ($enrol_sql) e
              ON u.id = e.userid
            LEFT JOIN ($attempts_sql) a
              ON a.userid = u.id
            WHERE (e.userid IS NOT NULL OR a.userid IS NOT NULL)
              AND u.deleted = 0
              $q_sql
SQL;
        $params = array_merge($enrol_params, $attempt_params, $q_params);
        $record = $DB->get_record_sql($sql, $params);
        return (int)$record->count;
    }

    /**
     * query to return anyone who is enrolled on the course
     * @param boolean $grouped
     * @return string
     */
    protected function get_enrolment_subquery_sql($grouped) {
        $group_sql = $grouped ? $this->get_group_sql('ue.userid') : '';
        return <<<SQL
            SELECT ue.userid
            FROM {user_enrolments} ue
            INNER JOIN {enrol} e
              ON e.id = ue.enrolid
            INNER JOIN {dragdrop} d
              ON d.course = e.courseid
            INNER JOIN {role} r
              ON r.id=e.roleid
            AND r.shortname='student'
            {$group_sql}
            WHERE d.id = ?
            GROUP BY ue.userid
SQL;
    }

    /**
     * query to return anyone who has made an attempt, along with some aggregate data
     * @return string
     */
    protected function get_attempts_aggregate_subquery_sql() {
        return <<<SQL
          SELECT a.userid,
            MAX(a.timecreated) AS lastattempt,
            COUNT(a.id) AS numattempts,
            MAX(a.correct) AS completed
          FROM {dragdrop_attempt} a
          INNER JOIN {dragdrop} d ON d.id = a.dragdropid
          WHERE d.id = ?
          GROUP BY a.userid
SQL;
    }

    /**
     * get sql for joining to groups
     * @param string $user_col
     * @return string
     */
    protected function get_group_sql($user_col) {
        return <<<SQL
            INNER JOIN {groups} g ON g.courseid = d.course
            INNER JOIN {groups_members} gm1 ON gm1.groupid = g.id AND gm1.userid = $user_col
            INNER JOIN {groups_members} gm2 ON gm2.groupid = g.id AND gm2.userid = ?
SQL;
    }

    /**
     * query to return anyone who has made an attempt that has been reset
     * @return string
     */
    protected function get_attempts_reset_subquery_sql() {
        return <<<SQL
          SELECT a.userid,
            COUNT(a.id) AS numattempts
          FROM {dragdrop_attempt} a
          WHERE a.dragdropid = ?
          AND a.reset = 1
          GROUP BY a.userid
SQL;
    }

    /**
     * search query string on user
     * @global moodle_database $DB
     * @param string $q
     * @return array
     */
    protected function _get_q_query_string($q) {
        global $DB;
        $q = trim(preg_replace('/\s{2,}/', ' ', $q));
        if (empty($q)) {
            return array("", array());
        }
        $firstname_like = $DB->sql_like('u.firstname', '?', false);
        $lastname_like = $DB->sql_like('u.lastname', '?', false);
        $fullname_like = $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), '?', false);
        return array(" AND ({$firstname_like} OR {$lastname_like} OR {$fullname_like})",
            array("%$q%", "%$q%", "%$q%"));
    }

    /**
     * resets a users attempts on a given drag and drop activity
     * @global moodle_database $DB
     * @param integer $instanceid
     * @param integer $userid
     * @param integer $now
     * @return integer
     */
    public function reset_attempts($instanceid, $userid, $now) {
        global $DB;
        $reset_group = $this->get_next_reset_group($instanceid, $userid);
        $sql = "UPDATE {dragdrop_attempt}
                SET reset = 1, reset_group = $reset_group, timemodified = $now
                WHERE userid = :userid
                AND dragdropid = :dragdropid
                AND reset = 0";
        return $DB->execute($sql, array('userid' => $userid, 'dragdropid' => $instanceid));
    }

    /**
     * returns a number that represents a group of reset attempts
     * @global moodle_database $DB
     * @param integer $instanceid
     * @param integer $userid
     * @return integer
     */
    protected function get_next_reset_group($instanceid, $userid) {
        global $DB;
        $sql = "SELECT MAX(a.reset_group)
                FROM {dragdrop_attempt} a
                WHERE a.dragdropid = :instanceid
                AND a.userid = :userid
                AND a.reset = 1";
        $params = array(
            'instanceid' => $instanceid,
            'userid' => $userid
        );
        return (int)$DB->get_field_sql($sql, $params) + 1;
    }

    /**
     * the number of incorrect attempts for a user
     * @global moodle_database $DB
     * @param integer $instanceid
     * @param integer $userid
     * @return int
     */
    public function get_num_incorrect_attempts($instanceid, $userid) {
        global $DB;
        $sql = <<<SQL
                SELECT COUNT(a.id)
                FROM {dragdrop_attempt} a
                WHERE a.dragdropid = :instanceid
                AND a.userid = :userid
                AND a.reset = 0
                AND a.correct = 0
SQL;
        return (int) $DB->get_field_sql($sql, array(
                'instanceid' => $instanceid,
                'userid' => $userid
            ));
    }

}
