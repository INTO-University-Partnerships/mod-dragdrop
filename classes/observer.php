<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/dragdrop_notification_sender.php';
require_once __DIR__ . '/../src/dragdrop_model_manager.php';

class mod_dragdrop_observer {

    /**
     * @param \mod_dragdrop\event\comment_added_by_tutor $event
     */
    public static function comment_added_by_tutor(\mod_dragdrop\event\comment_added_by_tutor $event) {
        global $CFG;
        $url = $CFG->wwwroot . SLUG . '/' . $event->other['module']['cmid'] . '#/previous';
        $body = get_string('notify:' . __FUNCTION__, 'mod_dragdrop', array(
            'url' => $url,
            'activity' => $event->other['module']['mod_instance']['name']
        ));
        $userid = (integer)$event->other['entity']['userid'];
        self::_send_notification($event, $body, array($userid), $url);
    }

    /**
     * @param \mod_dragdrop\event\student_attempt_made $event
     */
    public static function student_attempt_made(\mod_dragdrop\event\student_attempt_made $event) {
        global $CFG;
        $manager = $event->get_model_manager();
        $model = $manager->get_model('user_attempt');
        $instanceid = $event->other['module']['mod_instance']['id'];
        $num_attempts = $event->other['module']['mod_instance']['num_attempts'];
        $userid = $event->other['entity']['userid'];

        // return if the user has not used up all of their attempts (logging is sufficient)
        if ($model->get_num_incorrect_attempts($instanceid, $userid) < $num_attempts) {
            return;
        }

        // load the tutors
        $courseid = $event->other['module']['course']['id'];
        $user = $event->get_user();
        $tutors = $user->get_course_tutors($courseid, $userid, $event->get_group_mode());

        // return if no course tutors
        if (empty($tutors)) {
            return;
        }

        // load the user
        $user = $user->get_user($userid);

        // notification send
        $url = $CFG->wwwroot . SLUG . '/' . $event->other['module']['cmid'] . '#/attempts/' . $userid;
        $body = get_string('notify:' . __FUNCTION__, 'mod_dragdrop', array(
            'url' => $url,
            'activity' => $event->other['module']['mod_instance']['name'],
            'studentname' => $user->firstname . " " . $user->lastname
        ));
        self::_send_notification($event, $body, array_keys($tutors), $url);
    }

    /**
     * @param \mod_dragdrop\event\mod_dragdrop_base_event $event
     * @param string $body
     * @param array $users
     * @param string $url
     */
    protected static function _send_notification(\mod_dragdrop\event\mod_dragdrop_base_event $event, $body, $users, $url) {
        try {
            $sender = new dragdrop_notification_sender();
            $sender->send_notification($event->get_guzzler(), $body, $users, $url);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

}
