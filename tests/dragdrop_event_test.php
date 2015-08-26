<?php

use Mockery as m;

defined('MOODLE_INTERNAL') || die();

class dragdrop_event_test extends advanced_testcase {

    /**
     * @var Silex\Application
     */
    protected $_app;




    /**
     * setUp
     */
    public function setUp() {
        global $CFG;

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

        // Django settings removed by Moodle's PHPUnit integration
        $CFG->djangowwwroot = 'http://localhost:8000';
        $CFG->django_notification_basic_auth = array('notification', 'Wibble123!');
        $CFG->django_urls = array(
            'send_notification' => '/messaging_core/send/notification/',
        );
    }

    /**
     * tearDown
     */
    public function tearDown() {
        $_GET = array();
        $_POST = array();
        m::close();
    }

    /**
     * test that triggering a "comment added by tutor" event creates a Guzzle request
     */
    public function test_trigger_comment_added_by_tutor_event_creates_request() {
        // create a course
        $course = $this->getDataGenerator()->create_course();

        // mock out GuzzleHttp\Client
        $request = m::mock('\GuzzleHttp\Message\Request');
        $request->shouldIgnoreMissing();
        $response = m::mock('\GuzzleHttp\Response');
        $response->shouldIgnoreMissing();
        $client = m::mock('\GuzzleHttp\Client');
        $client->shouldReceive('createRequest')
            ->once()
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request)
            ->andReturn($response);

        $event = $this->_app['dragdrop_event']->handle_event('comment', array(
            'context' => context_course::instance($course->id),
            'objectid' => 999
        ), SEPARATEGROUPS, m::mock('dragdrop_user'), m::mock('dragdrop_model_manager'), $client);
        $this->assertTrue($event->is_triggered());
    }

    /**
     * test that triggering a "student attempt made" event does not create a Guzzle request if the user has remaining attempts
     */
    public function test_trigger_attempt_event_does_not_create_request_when_attempts_remain() {
        // create a course
        $course = $this->getDataGenerator()->create_course();

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // create an instance of the dragdrop activity
        $num_attempts = 3;
        $module = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
            'num_attempts' => $num_attempts
        ));

        $mod_data = array(
            'course' => (array) $course,
            'mod_instance' => (array) $module,
            'cmid' => $module->cmid
        );

        // attempt mock
        $attempt = m::mock('dragdrop_attempt');
        $attempt->shouldReceive('get_num_incorrect_attempts')->once()->with($module->id, $user->id)->andReturn($num_attempts - 1);

        // manager mock
        $manager = m::mock('dragdrop_model_manager');
        $manager->shouldReceive('get_model')->once()->with('user_attempt')->andReturn($attempt);

        // Guzzle mock
        $client = m::mock('\GuzzleHttp\Client');
        $client->shouldReceive('createRequest')->never();
        $client->shouldReceive('send')->never();

        $event = $this->_app['dragdrop_event']->handle_event('user_attempt', array(
            'context' => context_course::instance($course->id),
            'objectid' => $module->id,
            'other' => array(
                'module' => $mod_data,
                'entity' => array(
                    'userid' => $user->id
                )
            )), SEPARATEGROUPS, m::mock('dragdrop_user'), $manager, $client);
        $this->assertTrue($event->is_triggered());
    }

    /**
     * test that triggering a "student attempt made" event does not create a Guzzle request when there are no tutors
     */
    public function test_trigger_attempt_event_does_not_create_request_when_no_tutors() {
        // create a course
        $course = $this->getDataGenerator()->create_course();

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // create an instance of the dragdrop activity
        $num_attempts = 3;
        $module = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
            'num_attempts' => $num_attempts
        ));

        $mod_data = array(
            'course' => (array) $course,
            'mod_instance' => (array) $module,
            'cmid' => $module->cmid
        );

        // user mock
        $dduser = m::mock('dragdrop_user');
        $dduser->shouldReceive('get_course_tutors')->once()->with($course->id, $user->id, SEPARATEGROUPS)->andReturn(
            array() //  empty
        );

        // attempt mock
        $attempt = m::mock('dragdrop_attempt');
        $attempt->shouldReceive('get_num_incorrect_attempts')->once()->with($module->id, $user->id)->andReturn($num_attempts);

        // manager mock
        $manager = m::mock('dragdrop_model_manager');
        $manager->shouldReceive('get_model')->once()->with('user_attempt')->andReturn($attempt);

        // Guzzle mock
        $client = m::mock('\GuzzleHttp\Client');
        $client->shouldReceive('createRequest')->never();
        $client->shouldReceive('send')->never();

        $event = $this->_app['dragdrop_event']->handle_event('user_attempt', array(
            'context' => context_course::instance($course->id),
            'objectid' => $module->id,
            'other' => array(
                'module' => $mod_data,
                'entity' => array(
                    'userid' => $user->id
                )
        )), SEPARATEGROUPS, $dduser, $manager, $client);
        $this->assertTrue($event->is_triggered());
    }

    /**
     * test that triggering a "student attempt made" creates Guzzle request
     */
    public function test_trigger_attempt_event_creates_request() {
        // create a course
        $course = $this->getDataGenerator()->create_course();

        // create a user
        $user = $this->getDataGenerator()->create_user();
        $tutors = array();
        foreach (range(1, 3) as $_) {
            $tutor = $this->getDataGenerator()->create_user();
            $tutors[$tutor->id] = $tutor;
        }

        // create an instance of the dragdrop activity
        $num_attempts = 3;
        $module = $this->getDataGenerator()->create_module('dragdrop', array(
            'course' => $course->id,
            'num_attempts' => $num_attempts
        ));

        $mod_data = array(
            'course' => (array) $course,
            'mod_instance' => (array) $module,
            'cmid' => $module->cmid
        );

        // user mock
        $dduser = m::mock('dragdrop_user');
        $dduser->shouldReceive('get_course_tutors')->once()->with($course->id, $user->id, SEPARATEGROUPS)->andReturn(
            $tutors
        );
        $dduser->shouldReceive('get_user')->once()->with($user->id)->andReturn($user);

        // attempt mock
        $attempt = m::mock('dragdrop_attempt');
        $attempt->shouldReceive('get_num_incorrect_attempts')->once()->with($module->id, $user->id)->andReturn($num_attempts);

        // manager mock
        $manager = m::mock('dragdrop_model_manager');
        $manager->shouldReceive('get_model')->once()->with('user_attempt')->andReturn($attempt);

        // Guzzle mock
        $request = m::mock('\GuzzleHttp\Message\Request');
        $request->shouldIgnoreMissing();
        $response = m::mock('\GuzzleHttp\Response');
        $response->shouldIgnoreMissing();
        $client = m::mock('\GuzzleHttp\Client');
        $client->shouldReceive('createRequest')
            ->once()
            ->andReturn($request);
        $client->shouldReceive('send')
            ->once()
            ->with($request)
            ->andReturn($response);

        $event = $this->_app['dragdrop_event']->handle_event('user_attempt', array(
            'context' => context_course::instance($course->id),
            'objectid' => $module->id,
            'other' => array(
                'module' => $mod_data,
                'entity' => array(
                    'userid' => $user->id
                )
            )), SEPARATEGROUPS, $dduser, $manager, $client);
        $this->assertTrue($event->is_triggered());
    }

    /**
     * test to see a coding exception gets throws when not all necessary parameters are set
     */
    public function test_events_are_not_triggered_for_invalid_entities() {
        $client = m::mock('\GuzzleHttp\Client');
        $client->shouldReceive('createRequest')->never();
        $client->shouldReceive('send')->never();
        $course = $this->getDataGenerator()->create_course();
        $return = $this->_app['dragdrop_event']->handle_event('sentence', array(), NOGROUPS, m::mock('dragdrop_user'), m::mock('dragdrop_model_manager'), $client);
        $this->assertEmpty($return);
    }

}
