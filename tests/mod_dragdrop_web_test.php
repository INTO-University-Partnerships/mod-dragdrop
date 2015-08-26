<?php

// use the Client and Request classes
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

class mod_dragdrop_web_test extends advanced_testcase {

    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * setUp
     */
    public function setUp() {
        if (!defined('SLUG')) {
            define('SLUG', '');
        }
        if (!defined('SILEX_WEB_TEST')) {
            define('SILEX_WEB_TEST', true);
        }

        // create Silex app
        $this->_app = require __DIR__ . '/../app.php';
        $this->_app['debug'] = true;
        $this->_app['exception_handler']->disable();

        // add middleware to work around Moodle expecting non-empty $_GET or $_POST
        $this->_app->before(function (Request $request) {
            if (empty($_GET) && 'GET' == $request->getMethod()) {
                $_GET = $request->query->all();
            }
            if (empty($_POST) && 'POST' == $request->getMethod()) {
                $_POST = $request->request->all();
            }
        });

        // reset the database after each test
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        $_GET = array();
        $_POST = array();
    }

    /**
     * tests a non-existent route
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function test_non_existent_route() {
        $client = new Client($this->_app);
        $client->request('GET', '/does_not_exist');
    }

    /**
     * tests the instances route that shows all activity instances (i.e. course modules) in a certain course
     * @global moodle_database $DB
     */
    public function test_instances_route() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // create a handful of modules within the course
        foreach (range(1, 5) as $i) {
            $module = $this->getDataGenerator()->create_module('dragdrop', array(
                'course' => $course->id,
            ));
        }

        // login the user
        $this->setUser($user);

        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/instances/' . $course->id);
        $this->assertTrue($client->getResponse()->isOk());
        // check the page content
        foreach (range(1, 5) as $i) {
            $this->assertContains(get_string('modulename', 'dragdrop') . ' ' . $i, $client->getResponse()->getContent());
        }
        $this->assertNotContains(get_string('modulename', 'dragdrop').' 6', $client->getResponse()->getContent());
    }

    /**
     * tests the 'byinstanceid' route that lets you view a dragdrop activity by instance id (as opposed to course module id)
     */
    public function test_byinstanceid_route() {
        global $CFG;
        $client = new Client($this->_app);
        $course = $this->getDataGenerator()->create_course();
        $dragdrop = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
        ));
        $client->request('GET', '/instance/' . $dragdrop->id);
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('view', array(
            'cmid' => $dragdrop->cmid,
        ));
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

    /**
     * tests the 'view' route that lets you view a dragdrop activity by course module id
     * @global moodle_database $DB
     */
    public function test_view_route() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // create a course module
        $dragdrop = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
        ));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // login the user
        $this->setUser($user);

        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/' . $dragdrop->cmid);
        $this->assertTrue($client->getResponse()->isOk());
    }

    /**
     * tests serving up a partial that doesn't exist
     * @expectedException file_serving_exception
     * @expectedExceptionMessage Can not serve file - server configuration problem. (Non-existent partial)
     */
    public function test_partials_route_does_not_exist() {
        $client = new Client($this->_app);
        $client->request('GET', '/partials/does_not_exist.twig', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
    }

    /**
     * tests serving up a partial without an XMLHttpRequest
     * @expectedException file_serving_exception
     * @expectedExceptionMessage Can not serve file - server configuration problem. (AJAX requests only)
     */
    public function test_partials_route_non_xmlhttprequest() {
        $this->_app['debug'] = false;
        $client = new Client($this->_app);
        $client->request('GET', '/partials/directives/wordBlockListItem.twig', array(), array(), array(
            // empty
        ));
    }

    /**
     * tests serving up a partial
     */
    public function test_partials_route() {
        $client = new Client($this->_app);
        $client->request('GET', '/partials/directives/wordBlockListItem.twig', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isOk());
    }

}
