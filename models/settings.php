<?php
namespace dragdrop;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

defined('MOODLE_INTERNAL') || die();

class settings implements entity {

    protected $_get_model;

    public function __construct($get_model) {
        $this->_get_model = $get_model;
    }

    public function get($id) {
        global $DB;
        $this->must_exist($id);
        return array(
            'dragdrop' => $DB->get_record('dragdrop', array('id' => $id), '*', MUST_EXIST),
            'feedback' => array_values($DB->get_records('dragdrop_feedback', array('dragdropid' => $id), 'attempt')),
        );
    }

    /**
     * there is only one settings "entity" so just return from get
     * @param int $instanceid
     * @param array $params
     * @return int
     */
    public function get_all($instanceid, $params = null) {
        return array(
            $this->get($instanceid)
        );
    }

    public function save($instanceid, $data, $now, $id=0) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // instance level data
        $dragdrop = $DB->get_record('dragdrop', array('id' => $instanceid), '*', MUST_EXIST);
        $todb = clone($dragdrop);
        if (array_key_exists('instruction', $data)) {
            $todb->instruction = clean_param($data['instruction'], PARAM_CLEANHTML);
        }
        if (array_key_exists('feedback_correct', $data)) {
            $todb->feedback_correct = clean_param($data['feedback_correct'], PARAM_CLEANHTML);
        }
        if (array_key_exists('hint', $data)) {
            $todb->hint = clean_param($data['hint'], PARAM_CLEANHTML);
        }
        if (array_key_exists('num_attempts', $data)) {
            $todb->num_attempts = (int) $data['num_attempts'];
            $this->delete_feedback($instanceid, $todb->num_attempts);
        }
        if (array_key_exists('display_labels', $data)) {
            $todb->display_labels = (boolean) $data['display_labels'];
        }
        if ($dragdrop !== $todb) {
            $todb->timemodified = $now;
            $DB->update_record('dragdrop', $todb);
        }

        // feedback data
        if (array_key_exists('feedback', $data)) {
            $this->save_feedback($data['feedback'], $instanceid, $now);
        }
        $transaction->allow_commit();
        return $dragdrop->id;
    }


    protected function save_feedback($feedback, $instanceid, $now) {
        global $DB;
        if ($update = $DB->get_record('dragdrop_feedback', array('attempt' => $feedback->attempt, 'dragdropid' => $instanceid))) {
            $update->feedback = clean_param($feedback->html, PARAM_CLEANHTML);
            $update->timemodified = $now;
            $DB->update_record('dragdrop_feedback', $update);
        }
        else {
            $insert = new \stdClass();
            $insert->dragdropid = $instanceid;
            $insert->attempt = (int) $feedback->attempt;
            $insert->feedback = clean_param($feedback->html, PARAM_CLEANHTML);
            $insert->timemodified = $now;
            $insert->timecreated = $now;
            $DB->insert_record('dragdrop_feedback', $insert);
        }
    }

    public function delete($id) {
        throw new BadRequestHttpException('Settings cannot be deleted from here');
    }

    /**
     * delete any feedback where the number of attempts exceeds what's in the DB
     * @param $dragdropid
     * @param $num_attempts
     */
    protected function delete_feedback($dragdropid, $num_attempts) {
        global $DB;
        $sql = "DELETE FROM {dragdrop_feedback}
                WHERE attempt > :numattempts
                AND dragdropid = :dragdropid";
        $DB->execute($sql, array('dragdropid' => $dragdropid, 'numattempts' => $num_attempts));
    }

    /**
     * ensures that a dragdrop activity with the given id exists
     * @param $id
     */
    public function must_exist($id) {
        global $DB;
        $DB->get_field('dragdrop', 'id', array(
            'id' => $id,
        ), MUST_EXIST);
    }
}