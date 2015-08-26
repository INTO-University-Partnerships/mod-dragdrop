<?php

namespace mod_dragdrop\event;

defined('MOODLE_INTERNAL') || die();

abstract class mod_dragdrop_base_event extends \core\event\base {

    /**
     * @var \GuzzleHttp\Client
     */
    protected $_guzzler;

    /**
     * @var integer
     */
    protected $_group_mode;

    /**
     * @var \dragdrop_user
     */
    protected $_user;

    /**
     * @var \dradrop_model_manager
     */
    protected $_model_manager;

    /**
     * @return \GuzzleHttp\Client
     */
    public function get_guzzler() {
        return $this->_guzzler;
    }

    /**
     * @return integer
     */
    public function get_group_mode() {
        return $this->_group_mode;
    }

    /**
     * @return \dragdrop_user
     */
    public function get_user() {
        return $this->_user;
    }

    /**
     * @return \dragdrop_model_manager
     */
    public function get_model_manager() {
        return $this->_model_manager;
    }

    /**
     * @param integer $group_mode
     * @param \dragdrop_user $user
     * @param \dragdrop_model_manager $manager
     * @param \GuzzleHttp\Client $client
     */
    public function inject_dependencies($group_mode, \dragdrop_user $user, \dragdrop_model_manager $manager, \GuzzleHttp\Client $client) {
        $this->_group_mode = $group_mode;
        $this->_user = $user;
        $this->_model_manager = $manager;
        $this->_guzzler = $client;
    }

}
