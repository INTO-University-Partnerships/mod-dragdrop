<?php
namespace dragdrop;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

require_once(__DIR__ . '/../src/module_instance.php');

class v1_api implements ControllerProviderInterface {

    /**
     * @param \Silex\Application $app
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        // before all routes in this controller provider
        $controllers->before($app['middleware']['ajax_request'])
            ->before(function () use ($app) {
                $app->error(function (\Exception $e, $code) use ($app) {
                    return $app->json(array('errorMessage' => $e->getMessage()), $code);
                });
            });

        // base route
        $controllers->get('/{instanceid}/', function() use($app) {
            return "API route for this module instance";
        })->bind('api_route');

        // get all attempts for reporting
        $controllers->get('/{instanceid}/attempts/', array($this, 'get_attempts'))
            ->bind('get_attempts')
            ->assert('instanceid', '\d+');

        // get userdata
        $controllers->get('/{instanceid}/user/{userid}', array($this, 'get_user'))
            ->assert('instanceid', '\d+')
            ->assert('userid', '\d+');

        // reset user attempts
        $controllers->put('/{instanceid}/user_attempt/reset/', array($this, 'reset_attempts'))
            ->assert('instanceid', '\d+');

        // get all entities
        $controllers->get('/{instanceid}/{entity}/', array($this, 'all'))
            ->bind('get_entities')
            ->assert('instanceid', '\d+');

        // get a single entity
        $controllers->get('/{instanceid}/{entity}/{id}', array($this, 'get'))
            ->bind('get_entity')
            ->assert('instanceid', '\d+');

        // create an entity
        $controllers->post('/{instanceid}/{entity}/', array($this, 'create'))
            ->assert('instanceid', '\d+')
            ->before($app['middleware']['ajax_sesskey']);

        // update an entity
        $controllers->put('/{instanceid}/{entity}/{id}', array($this, 'update'))
            ->assert('instanceid', '\d+')
            ->before($app['middleware']['ajax_sesskey']);

        // delete an entity
        $controllers->delete('/{instanceid}/{entity}/{id}', array($this, 'delete'))
            ->assert('instanceid', '\d+')
            ->before($app['middleware']['ajax_sesskey']);

        return $controllers;
    }

    /**
     * get a single entity
     * @param \Silex\Application $app
     * @param integer $instanceid
     * @param string $entity
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function get(Application $app, $instanceid, $entity, $id) {
        global $USER;

        // course
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $context = $module->get_context();
        $app['require_course_login']($course);

        // capability
        $userid = $app['request']->get('userid', $USER->id);
        if (!$app['dragdrop_capabilities']->can_view($entity, $context, $userid)) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        // check group access
        $can_manage = $app['has_capability']('moodle/course:manageactivities', $context);
        $group_mode = $app['get_groupmode']($course->id, $module->get_cm()->id);
        if (!$can_manage && !$app['dragdrop_user']->has_group_access($group_mode, $userid, $course->id)) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        $model = $this->get_model($app, $entity);
        try {
            $data = $model->get($id);
        } catch (\dml_missing_record_exception $e) {
            throw new NotFoundHttpException(get_string($entity . '_invalidid', $app['plugin']));
        }
        return $app->json($data);
    }

    /**
     * get all entities for a given instance
     * @param \Silex\Application $app
     * @param integer $instanceid
     * @param string $entity
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function all(Application $app, $instanceid, $entity) {
        global $USER;

        // course set-up
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $context = $module->get_context();
        $app['require_course_login']($course);

        // capability
        $userid = $app['request']->get('userid', $USER->id);
        if (!$app['dragdrop_capabilities']->can_view($entity, $context, $userid)) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        // check group access
        $can_manage = $app['has_capability']('moodle/course:manageactivities', $context);
        $group_mode = $app['get_groupmode']($course->id, $module->get_cm()->id);
        if (!$can_manage && !$app['dragdrop_user']->has_group_access($group_mode, $userid, $course->id)) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        $model = $this->get_model($app, $entity);
        return $app->json(
            array_values($model->get_all($instanceid, (array) $app['request']->query->all()))
        );
    }

    /**
     * create an entity
     * @param \Silex\Application $app
     * @param integer $instanceid
     * @param string $entity
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function create(Application $app, $instanceid, $entity) {
        global $CFG, $USER;

        // course set-up
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $app['require_course_login']($course);

        // capability
        if (!$app['dragdrop_capabilities']->can_manage($entity, $module->get_context(), $app['request']->get('userid'))) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        // get the model and save the data
        $model = $this->get_model($app, $entity);
        $data = (array)json_decode($app['request']->getContent());
        try {
            $id = $model->save($instanceid, $data, $app['now']());
        } catch (\invalid_parameter_exception $e) {
            throw new BadRequestHttpException($e->debuginfo);
        }
        $response = $model->get($id);

        // trigger event
        $params = array(
            'objectid' => $id,
            'other' => array(
                'module' => $module->get_data_for_encoding(),
                'entity' => (array)$response
            ),
            'relateduserid' => $USER->id,
            'context' => $module->get_context()
        );
        $group_mode = $app['get_groupmode']($course->id, $module->get_cm()->id);
        $app['dragdrop_event']->handle_event($entity, $params, $group_mode, $app['dragdrop_user'], $app['dragdrop_model'], $app['guzzler']);

        // return the response
        $response = (array) $response;
        $response['successMessage'] = get_string($entity . '_added_successfully', $app['plugin'], $data);
        $url = $CFG->wwwroot . SLUG . $app['url_generator']->generate('get_entity', array(
                'id' => $id,
                'instanceid' => $instanceid,
                'entity' => $entity
            ));
        return $app->json($response, 201, array(
            'Location' => $url,
        ));
    }

    /**
     * update an existing entity
     * @param \Silex\Application $app
     * @param integer $instanceid
     * @param string $entity
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function update(Application $app, $instanceid, $entity, $id) {

        // course set-up
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $app['require_course_login']($course);

        // capability
        if (!$app['dragdrop_capabilities']->can_manage($entity, $module->get_context(), $app['request']->get('userid'))) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        // get the model and save the data
        $model = $this->get_model($app, $entity);
        $data = (array)json_decode($app['request']->getContent());
        try {
            $model->save($instanceid, $data, $app['now'](), $id);
        } catch (\invalid_parameter_exception $e) {
            throw new BadRequestHttpException($e->debuginfo);
        } catch (\dml_missing_record_exception $e) {
            throw new NotFoundHttpException(get_string($entity . '_invalidid', $app['plugin']));
        }
        $data['successMessage'] = get_string($entity . '_updated_successfully', $app['plugin']);
        return $app->json($data, 200);
    }

    /**
     * delete an entity
     * @param \Silex\Application $app
     * @param integer $instanceid
     * @param string $entity
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function delete(Application $app, $instanceid, $entity, $id) {

        // course set-up
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $app['require_course_login']($course);

        // capability
        if (!$app['dragdrop_capabilities']->can_manage($entity, $module->get_context(), $app['request']->get('userid'))) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }

        // get the model and delete
        $model = $this->get_model($app, $entity);
        try {
            $model->delete($id);
        } catch (\invalid_parameter_exception $e) {
            throw new BadRequestHttpException($e->debuginfo);
        } catch (\dml_missing_record_exception $e) {
            throw new NotFoundHttpException(get_string($entity . '_invalidid', $app['plugin']));
        }
        return $app->json('', 204);
    }

    /**
     * get attempts for users within an activity
     * @param \Silex\Application $app
     * @param integer $instanceid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function get_attempts(Application $app, $instanceid) {
        global $USER;
        $limitfrom = (integer)$app['request']->get('limitfrom');
        $limitnum = (integer)$app['request']->get('limitnum');
        $q = (string)$app['request']->get('q');
        $sort = (string)$app['request']->get('sort');
        $sort_direction = (string)$app['request']->get('direction');
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $context = $module->get_context();
        $app['require_course_login']($course);
        if (!$app['has_capability']('mod/dragdrop:view_all_attempts', $context)) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }
        $model = $this->get_model($app, 'user_attempt');
        $model->set_userid($USER->id);

        $can_manage = $app['has_capability']('moodle/course:manageactivities', $context);
        if (!$can_manage) {
            $model->set_groupmode($app['get_groupmode']($course->id, $module->get_cm()->id));
        }
        $data = array(
            'attempts' => $model->get_users_with_attempt_count($instanceid, $q, $sort, $sort_direction, $limitfrom, $limitnum),
            'total' => (int) $model->get_total_users($instanceid, $q)
        );
        return $app->json($data);
    }

    /**
     * get user data
     * @param Application $app
     * @param $instanceid
     * @param $userid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function get_user(Application $app, $instanceid, $userid = 0) {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $app['require_course_login']($course);
        return $app->json($app['dragdrop_user']->get_user($userid));
    }

    /**
     * Route for resetting a user's attempts on an instance
     * @param Application $app
     * @param $instanceid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function reset_attempts(Application $app, $instanceid) {
        $module = \module_instance::get_instance($instanceid);
        $course = $module->get_course();
        $app['require_course_login']($course);
        if (!$app['has_capability']('mod/dragdrop:manage_all_attempts', $module->get_context())) {
            throw new AccessDeniedHttpException(get_string('accessdenied', 'admin'));
        }
        $model = $this->get_model($app, 'user_attempt');
        $data = (array)json_decode($app['request']->getContent());
        $model->reset_attempts($instanceid, $data['userid'], $app['now']());
        $data['successMessage'] = get_string('attempts_reset_successfully', $app['plugin']);
        return $app->json($data, 200);

    }

    /**
     * get a model from the entity name parameter
     * @param \Silex\Application $app
     * @param integer $name
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function get_model(Application $app, $name) {
        try {
            $model = $app['dragdrop_model']::get_model($name);
            return $model;
        } catch (\coding_exception $e) {
            throw new NotFoundHttpException(get_string('invalid_entity', $app['plugin'], $name));
        }
    }

}
