<?php

defined('MOODLE_INTERNAL') || die();

class dragdrop_capabilities {

    /**
     * capabilities mapped to models (model => capability)
     * @var array
     */
    private static $_capabilities = array(
        'sentence' => 'sentences',
        'word_block' => 'word_blocks',
        'sentence_words' => 'sentences',
        'user_attempt' => 'attempts',
        'settings' => 'feedback_settings',
        'comment' => 'comment'
    );

    /**
     * capabilities that are associated with user data (keys),
     * and the corresponding capability required to view data relating to other users (values)
     * @var array
     */
    private static $_user_view_capabilities = array(
        'user_attempt' => 'view_all_attempts',
        'comment' => 'view_all_comments'
    );

    /**
     * capabilities that are associated with user data (keys),
     * and the capability required to manage entities relating to other users (values)
     * @var array
     */
    private static $_user_manage_capabilities = array(
        'user_attempt' => 'manage_all_attempts',
        'comment' => 'manage_all_comments'
    );

    /**
     * constructor
     */
    public function __construct() {
        // empty
    }

    /**
     * has a manage capability
     * @param $model
     * @param $context
     * @param $userid
     * @return bool
     */
    public function can_manage($model, $context, $userid = 0) {
        global $USER;
        if (!array_key_exists($model, self::$_capabilities)) {
            return false;
        }
        $cap = self::$_capabilities[$model];
        if (!has_capability('mod/dragdrop:'.$cap, $context)) {
            return false;
        }
        if (!array_key_exists($model, self::$_user_manage_capabilities)) {
            return true;
        }
        $capable_of_managing_all = has_capability('mod/dragdrop:'.self::$_user_manage_capabilities[$model], $context);
        return ($USER->id == $userid) || $capable_of_managing_all;
    }


    /**
     * has a view capability
     * for entities that are associated with user data, a userid should be provided
     * @param $model
     * @param $context
     * @param $userid
     * @return bool
     */
    public function can_view($model, $context, $userid = 0) {
        global $USER;
        if (!array_key_exists($model, self::$_capabilities)) {
            return false;
        }
        if (!has_capability('mod/dragdrop:view', $context)) {
            return false;
        }
        if (!array_key_exists($model, self::$_user_view_capabilities)) {
            return true;
        }
        $capable_of_viewing_all = has_capability('mod/dragdrop:'.self::$_user_view_capabilities[$model], $context);
        return ($USER->id == $userid) || $capable_of_viewing_all;
    }
}