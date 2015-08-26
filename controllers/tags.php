<?php

defined('MOODLE_INTERNAL') || die();

$controller = $app['controllers_factory'];

// get tags
$controller->get('/', function () use ($app) {
    global $PAGE;
    $PAGE->set_context(context_system::instance());
    require_once(__DIR__ . '/../src/wordblock_tags.php');
    $tags = new wordblock_tags();
    return $app->json($tags->get_all());
})
->before($app['middleware']['ajax_request'])
->bind('get_tags');

// return the controller
return $controller;
