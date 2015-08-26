<?php

defined('MOODLE_INTERNAL') || die();

$controller = $app['controllers_factory'];

// view the given activity
$controller->get('/{cmid}', function ($cmid) use ($app) {
    global $DB, $PAGE;

    // get course module id
    $cm = $DB->get_record('course_modules', array(
        'id' => $cmid,
    ), '*', MUST_EXIST);

    // get instance
    $instance = $DB->get_record($app['module_table'], array(
        'id' => $cm->instance,
    ), '*', MUST_EXIST);

    // get course
    $course = $DB->get_record('course', array(
        'id' => $cm->course,
    ), '*', MUST_EXIST);

    // require course login
    $app['require_course_login']($course, $cm);

    // log it
    $event = \mod_dragdrop\event\course_module_viewed::create(array(
        'objectid' => $cm->instance,
        'context' => $PAGE->context,
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot($app['module_table'], $instance);
    $event->add_record_snapshot('course', $course);
    $event->trigger();

    // mark viewed
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // context
    $context = context_module::instance($cm->id);

    //editor
    global $CFG;
    $editor = get_texteditor('tinymce');
    $url = $editor->get_tinymce_base_url();
    $tiny = new moodle_url($url . "/tiny_mce.js");
    $PAGE->requires->js($tiny);
    $tiny = new moodle_url($CFG->wwwroot . "/lib/editor/tinymce/module.js");
    $PAGE->requires->js($tiny);

    // render
    return $app['twig']->render('view.twig', array(
        'cm' => $cm,
        'instance' => $instance,
        'course' => $course,
        'can_manage_sentences' => has_capability('mod/dragdrop:sentences', $context),
        'can_manage_word_blocks' => has_capability('mod/dragdrop:sentences', $context),
        'can_manage_settings' => has_capability('mod/dragdrop:feedback_settings', $context),
        'can_view_attempts' => has_capability('mod/dragdrop:view_all_attempts', $context),
        'can_manage_comments' => has_capability('mod/dragdrop:comment', $context),
        'can_manage_all_attempts' => has_capability('mod/dragdrop:manage_all_attempts', $context),
        'can_view_activity' => has_capability('mod/dragdrop:view', \context_course::instance($course->id))
    ));
})
->bind('view')
->assert('cmid', '\d+');

// view the given activity
$controller->get('/instance/{id}', function ($id) use ($app) {
    global $CFG, $DB;

    // get module id from modules table
    $moduleid = (integer)$DB->get_field('modules', 'id', array(
        'name' => $app['module_table'],
    ), MUST_EXIST);

    // get course module id
    $cmid = (integer)$DB->get_field('course_modules', 'id', array(
        'module' => $moduleid,
        'instance' => $id,
    ), MUST_EXIST);

    // redirect
    return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('view', array(
        'cmid' => $cmid,
    )));
})
->bind('byinstanceid')
->assert('id', '\d+');

// return the controller
return $controller;
