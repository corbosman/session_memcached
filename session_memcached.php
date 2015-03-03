<?php

require_once(dirname(__FILE__) . '/rcube_session_memcached.php');

class session_memcached extends rcube_plugin
{
    /**
     * no init necessary. Use onload instead to be able to hook into the plugin code earlier than normal plugins.
     */
    function init() {}

    /**
     * load config early
     */
    public function onload()
    {
        $this->load_config();
    }

}
