<?php
namespace dragdrop;

defined('MOODLE_INTERNAL') || die();

class comment implements entity {

    protected $_get_model;

    public function __construct($get_model) {
        $this->_get_model = $get_model;
    }

    public function get($id) {
        global $DB;
        return $DB->get_record('dragdrop_comment', array('id' => $id), '*', MUST_EXIST);
    }


    public function get_all($instanceid, $params = null) {
        global $USER, $DB;
        $userid = isset($params['userid']) ? $params['userid']: $USER->id;
        $sql = "SELECT c.*, u.firstname, u.lastname
                FROM {dragdrop_comment} c
                JOIN {user} u
                ON u.id=c.creatorid
                WHERE c.dragdropid = :dragdropid
                AND c.userid = :userid
                ORDER BY c.timecreated DESC";
        $records = $DB->get_records_sql($sql, array(
            'dragdropid' => $instanceid,
            'userid' => $userid
        ));
        foreach ($records as &$record) {
            $record->author_string = $record->firstname . " " . $record->lastname . ", " . userdate($record->timecreated);
        }
        return $records;
    }

    public function save($instanceid, $data, $now, $id=0) {
        global $DB, $USER;
        $data['timemodified'] = $now;
        $data['comment'] = clean_param($data['comment'], PARAM_CLEANHTML);
        if ($id) {
            $this->must_exist($id);
            $data['id'] = $id;
            $DB->update_record('dragdrop_comment', (object) $data);
            return $id;
        }
        $data['creatorid'] = $USER->id;
        $data['dragdropid'] = $instanceid;
        $data['timecreated'] = $now;
        return $DB->insert_record('dragdrop_comment', (object) $data);
    }

    public function must_exist($id) {
        global $DB;
        $DB->get_field('dragdrop_comment', 'id', array(
            'id' => $id,
        ), MUST_EXIST);
    }

    public function delete($id) {
        global $DB;
        $this->must_exist($id);
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('dragdrop_comment', array('id' => $id));
        $transaction->allow_commit();
    }
}