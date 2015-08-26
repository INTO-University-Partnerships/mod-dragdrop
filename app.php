<?php
namespace dragdrop;

use Symfony\Component\HttpFoundation\Request;

// bootstrap Moodle
require_once __DIR__ . '/../../config.php';
global $CFG, $FULLME;

// fix $FULLME
$FULLME = str_replace($CFG->wwwroot, $CFG->wwwroot . SLUG, $FULLME);

// create Silex app
require_once __DIR__ . '/../../vendor/autoload.php';
$app = new \Silex\Application();
$app['debug'] = debugging('', DEBUG_MINIMAL);

// enable Twig service provider
$app->register(new \Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/templates',
    'twig.options' => array(
        'cache' => empty($CFG->disable_twig_cache) ? "{$CFG->dataroot}/twig_cache" : false,
        'auto_reload' => debugging('', DEBUG_MINIMAL),
    ),
));

// enable URL generator service provider
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

// set Twig constants
$app['twig']->addGlobal('plugin', 'mod_dragdrop');
$app['twig']->addGlobal('wwwroot', $CFG->wwwroot);
$app['twig']->addGlobal('slug', SLUG);
$app['twig']->addGlobal('bower_url', isset($CFG->bower_url) ? $CFG->bower_url : $CFG->wwwroot . '/mod/dragdrop/static/js/components/');

// require Twig library functions
require __DIR__ . '/twiglib.php';

// module settings
$app['plugin'] = 'mod_dragdrop';
$app['module_table'] = 'dragdrop';

// require the services
foreach (array(
             'has_capability',
             'now',
             'require_course_login',
             'guzzler',
             'get_groupmode'
         ) as $service) {
    require __DIR__ . '/services/' . $service . '.php';
}

// define middleware
$app['middleware'] = array(
    'ajax_request' => function (Request $request) use ($app) {
            if (!$app['debug'] && !$request->isXmlHttpRequest()) {
                throw new \file_serving_exception(get_string('exception:ajax_only', $app['plugin']));
            }
        },
    'ajax_sesskey' => function (Request $request) use ($app) {
            if (!confirm_sesskey($request->get('sesskey'))) {
                return $app->json(array('errorMessage' => get_string('accessdenied', 'admin')), 403);
            }
        }
);

// include the model helper
require_once(__DIR__ . '/src/dragdrop_model_manager.php');

$app['dragdrop_model'] = $app->share(function () {
    return new \dragdrop_model_manager();
});

// include the capability helper
require_once(__DIR__ . '/src/dragdrop_capabilities.php');
$app['dragdrop_capabilities'] = $app->share(function () {
    return new \dragdrop_capabilities();
});

// include the event helper
require_once(__DIR__ . '/src/dragdrop_event.php');
$app['dragdrop_event'] = $app->share(function () {
    return new \dragdrop_event();
});

// include the load user helper
require_once(__DIR__ . '/src/dragdrop_user.php');
$app['dragdrop_user'] = $app->share(function () {
    return new \dragdrop_user();
});

// mount the controllers
foreach (array(
             'view' => '',
             'partials' => 'partials',
             'tags' => 'tags',
             'instances' => 'instances'
         ) as $controller => $mount_point) {
    $app->mount('/' . $mount_point, require __DIR__ . '/controllers/' . $controller . '.php');
}

require_once(__DIR__ . '/controllers/v1_api.php');
$app->mount('/api/v1/', new v1_api());

// return the app
return $app;
