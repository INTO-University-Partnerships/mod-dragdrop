<?php

// use the Client and Request classes
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mockery as m;

defined('MOODLE_INTERNAL') || die();

class mod_dragdrop_v1_api_test extends advanced_testcase {


    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * setUp
     */
    protected function setUp() {
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
        m::close();
        $_GET = array();
        $_POST = array();
    }

    /**
     * tests getting entities from an invalid instanceids
     */
    public function test_get_entities_with_invalid_instanceid() {
        global $DB;
        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', '/api/v1/999/mock_entity/', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 404 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('moduleinstancedoesnotexist', 'error'), $content->errorMessage);

    }

    /**
     * test getting entities where the course module does not exist
     */
    public function test_get_entities_with_invalid_course_module() {
        global $DB;
        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // valid module instance
        $this->loadDataSet($this->createArrayDataSet(array(
            'dragdrop' => array(
                array('id', 'course', 'name', 'instruction', 'hint', 'feedback_correct', 'num_attempts', 'timemodified', 'timecreated'),
                array(1, $course->id, 'Dragdrop Activity', 'Instructional Text', '<p>A hint</p>', '<p>Feedback</p>', 3, time(), time())
            )
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/1/mock_entity/", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 404 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('invalidcoursemodule', 'error'), $content->errorMessage);

    }

    /**
     * @expectedException file_serving_exception
     */
    public function test_get_entities_without_debugging_or_xmlhttp() {
        global $DB;

        // set debugging to false
        $this->_app['debug'] = false;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/");

        $this->assertTrue($client->getResponse()->isServerError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 500 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('exception:ajax_only', $this->_app['plugin']), $content);
    }

    /**
     * test get entities with invalid entity name
     */
    public function test_get_entities_with_invalid_entity() {
        global $DB;
        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/does_not_exist/", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 404 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);

    }

    /**
     * user is enrolled, but not logged in
     */
    public function test_get_entities_without_logging_in() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isServerError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 404 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('redirecterrordetected', 'error'), $content->errorMessage);
    }

    /**
     * test getting multiple entities without the right capability
     */
    public function test_get_entities_no_capability() {
        global $DB;

        // remove the view capability from students
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        unassign_capability('mod/dragdrop:view', $roleid);

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        // check 403 status code
        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        // check content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test getting multiple entities without the right capability
     */
    public function test_get_entities_separate_groups_without_group_access() {
        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
            'groupmode' => SEPARATEGROUPS
        ));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // mock capabilities
        $c = m::mock('dragdrop_capabilities');
        $c->shouldReceive('can_view')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($c) {
            return $c;
        });

        // mock group access
        $g = m::mock('dragdrop_user');
        $g->shouldReceive('has_group_access')->once()->with(SEPARATEGROUPS, 99999, $course->id)->andReturn(false);
        $this->_app['dragdrop_user'] = $this->_app->share(function () use ($g) {
            return $g;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/", array('userid' => 99999), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        // check 403 status code
        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        // check content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test a getting multiple entities
     */
    public function test_get_entities_valid() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        //mock the model
        $return_data = array(
            array('some_entity_data'),
            array('some_more_entity_data'));
        $mock = m::mock('mock_entity');
        $mock->shouldReceive('get_all')->once()->andReturn($return_data);

        $model = m::mock('dragdrop_model');
        $model->shouldReceive('get_model')->once()->andReturn($mock);
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });
        // mocks
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_view')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        // check 200 status code
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals($return_data, $content);
    }

    /**
     * test get a single entity without logging in
     * @global moodle_database $DB
     */
    public function test_get_single_entity_without_logging_in() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/1", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isServerError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 500 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('redirecterrordetected', 'error'), $content->errorMessage);
    }

    /**
     * test get a single entity without the view capability
     * @global moodle_database $DB
     */
    public function test_get_single_entity_no_capability() {
        global $DB;

        // remove the view capability from students
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        unassign_capability('mod/dragdrop:view', $roleid);

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/1", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        // check 403 status code
        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        // check content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test get a single entity without the view capability
     * @global moodle_database $DB
     */
    public function test_get_single_entity_separate_groups_without_group_access() {
        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
            'groupmode' => SEPARATEGROUPS
        ));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // mock capabilities
        $c = m::mock('dragdrop_capabilities');
        $c->shouldReceive('can_view')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($c) {
            return $c;
        });

        // mock group access
        $g = m::mock('dragdrop_user');
        $g->shouldReceive('has_group_access')->once()->with(SEPARATEGROUPS, 99999, $course->id)->andReturn(false);
        $this->_app['dragdrop_user'] = $this->_app->share(function () use ($g) {
            return $g;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/1", array(
            'userid' => 99999
        ), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        // check 403 status code
        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        // check content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test a response from a mocked entity
     */
    public function test_get_single_entity_valid() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        //mock the model
        $return_data = array('a_single_entity');
        $mock = m::mock('mock_entity');
        $mock->shouldReceive('get')->once()->with(1)->andReturn($return_data);
        $model = m::mock('dragdrop_model');
        $model->shouldReceive('get_model')->once()->andReturn($mock);
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });
        // mocks
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_view')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('GET', "/api/v1/{$module->id}/mock_entity/1", array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        // check 200 status code
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals($return_data, $content);
    }

    /**
     * test create entity without sesskey
     */
    public function test_create_entity_invalid_sesskey() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('POST', "/api/v1/{$module->id}/mock_entity/", array('sesskey'=>'invalid_sesskey'), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 403 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test create without logging in
     */
    public function test_create_entity_without_logging_in() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('POST', "/api/v1/{$module->id}/mock_entity/", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isServerError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 500 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('redirecterrordetected', 'error'), $content->errorMessage);
    }

    /**
     * test a valid create request with a mocked entity
     */
    public function test_create_entity_no_capability() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        // mocks
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_manage')->once()->andReturn(false);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('POST', "/api/v1/{$module->id}/mock_entity/", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 403 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test a valid create request with a mocked entity
     */
    public function test_create_entity() {
        global $DB, $CFG;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        // mocks
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_manage')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        // mock now
        $now = time();
        $this->_app['now'] = $this->_app->protect(function () use ($now) {
            return $now;
        });

        //mock data
        $to_save = new stdClass();
        $to_save->name = "My new entity";
        $to_return = new stdClass();
        $to_return->entity = $to_save;
        $mock = m::mock('mock_entity');
        $mock->shouldReceive('save')->once()->withArgs(array($module->id, (array) $to_save, $now))->andReturn(999);
        $mock->shouldReceive('get')->once()->with(999)->andReturn($to_return);

        // mock model
        $model = m::mock('dragdrop_model_manager');
        $model->shouldReceive('get_model')->once()->andReturn($mock);
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('POST', "/api/v1/{$module->id}/mock_entity/", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ), json_encode($to_save));

        // check 201 response
        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $content = json_decode($client->getResponse()->getContent());
        $to_return->successMessage = get_string('mock_entity_added_successfully', $this->_app['plugin']);
        $this->assertEquals($to_return, $content);

        // check location header
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('get_entity', array(
                'id' => 999,
                'instanceid' => $module->id,
                'entity' => 'mock_entity'
            ));
        $this->assertEquals($url, $client->getResponse()->headers->get('location'));
    }


    public function test_update_entity_invalid_sesskey() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('PUT', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>'invalid_sesskey'), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 403 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test update without logging in
     */
    public function test_update_entity_without_logging_in() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('PUT', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isServerError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 500 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('redirecterrordetected', 'error'), $content->errorMessage);
    }

    public function test_update_entity_no_capability() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // mock capability
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_manage')->once()->andReturn(false);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // mock model
        $model = m::mock('dragdrop_model');
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('PUT', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 403 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test a valid update request with a mocked entity
     */
    public function test_update_entity_valid() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // mock capability
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_manage')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        assign_capability('mod/dragdrop:mock_entity', CAP_ALLOW, $roleid, context_module::instance($module->cmid));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // mock now
        $now = time();
        $this->_app['now'] = $this->_app->protect(function () use ($now) {
            return $now;
        });

        //mock the data
        $to_save = new stdClass();
        $to_save->name = "My new entity";
        $to_return = 999;
        $mock = m::mock('mock_entity');
        $mock->shouldReceive('save')
            ->once()
            ->withArgs(array($module->id, (array) $to_save, $now, 999))
            ->andReturn($to_return);

        // mock model
        $model = m::mock('dragdrop_model');
        $model->shouldReceive('get_model')->once()->andReturn($mock);
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('PUT', "/api/v1/{$module->id}/mock_entity/{$to_return}", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ), json_encode($to_save));

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 200 response
        $content = json_decode($client->getResponse()->getContent());
        $to_save->successMessage = get_string('mock_entity_updated_successfully', $this->_app['plugin']);
        $this->assertEquals($to_save, $content);
    }

    public function test_delete_entity_invalid_sesskey() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('DELETE', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>'invalid_sesskey'), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 403 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test delete entity without logging in
     */
    public function test_delete_entity_without_logging_in() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('DELETE', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isServerError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 500 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('redirecterrordetected', 'error'), $content->errorMessage);
    }

    /**
     * test a delete request_without the right capability
     */
    public function test_delete_entity_no_capability() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));;

        // mock capability
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_manage')->once()->andReturn(false);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        // mock model
        $model = m::mock('dragdrop_model');
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('DELETE', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // check 403 message
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(get_string('accessdenied', 'admin'), $content->errorMessage);
    }

    /**
     * test a valid delete request with a mocked entity
     */
    public function test_delete_entity_valid() {
        global $DB;

        // create a user (no login)
        $user = $this->getDataGenerator()->create_user();

        // login the user
        $this->setUser($user);

        // valid course
        $course = $this->getDataGenerator()->create_course();

        // valid module
        $module = $this->getDataGenerator()->create_module('dragdrop', array('course' => $course->id));

        // mock capability
        $model = m::mock('dragdrop_capabilities');
        $model->shouldReceive('can_manage')->once()->andReturn(true);
        $this->_app['dragdrop_capabilities'] = $this->_app->share(function () use ($model) {
            return $model;
        });
        $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        assign_capability('mod/dragdrop:mock_entity', CAP_ALLOW, $roleid, context_module::instance($module->cmid));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        //mock the model
        $mock = m::mock('mock_entity');
        $mock->shouldReceive('delete')->once();

        // mock model
        $model = m::mock('dragdrop_model');
        $model->shouldReceive('get_model')->once()->andReturn($mock);
        $this->_app['dragdrop_model'] = $this->_app->share(function () use ($model) {
            return $model;
        });

        // the client
        $client = new Client($this->_app);

        // request the route
        $client->request('DELETE', "/api/v1/{$module->id}/mock_entity/3", array('sesskey'=>sesskey()), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isEmpty());
    }

}
