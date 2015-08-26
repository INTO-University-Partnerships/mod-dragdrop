<?php

defined('MOODLE_INTERNAL') || die();

class dragdrop_event {

    /**
     * entities for which an event should be triggered when created
     * entity => event
     * @var array
     */
    private static $_create_events = array(
        'comment' => 'comment_added_by_tutor',
        'user_attempt' => 'student_attempt_made'
    );

    public function __construct() {

    }

    /**
     * @param $entity_name
     * @param array $params
     * @throws coding_exception
     */
    public function handle_event($entity_name, array $params, $group_mode, dragdrop_user $user, dragdrop_model_manager $manager, \GuzzleHttp\Client $guzzler) {
        if (!array_key_exists($entity_name, self::$_create_events)) {
            return;
        }
        $eventName = self::$_create_events[$entity_name];

        // check if event exists
        $eventClass = "\\mod_dragdrop\\event\\$eventName";
        if (!class_exists($eventClass)) {
            $classFile = __DIR__ . '/../classes/event/' . $eventName . '.php';
            if (!file_exists($classFile)) {
                throw new coding_exception("code file defining event '{$eventName}' does not exist");
            }
            require_once $classFile;
            if (!class_exists($eventClass)) {
                throw new coding_exception("code file defining event '{$eventName}' is missing event class by the same name");
            }
        }

        // create event, set guzzler, trigger it and return it
        $event = $eventClass::create($params);
        $event->inject_dependencies($group_mode, $user, $manager, $guzzler);
        $event->trigger();
        return $event;
    }

}
