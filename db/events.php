<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_dragdrop\event\comment_added_by_tutor',
        'callback' => 'mod_dragdrop_observer::comment_added_by_tutor'
    ),
    array(
        'eventname' => '\mod_dragdrop\event\student_attempt_made',
        'callback' => 'mod_dragdrop_observer::student_attempt_made'
    ),
);
