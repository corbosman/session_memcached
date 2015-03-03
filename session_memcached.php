<?php

require_once(dirname(__FILE__) . '/rcube_session_memcached.php');

class session_memcached extends rcube_plugin
{
    /**
     * no init necessary. rcube.php will load the rcube_session_redis class.
     */
    function init() {}

    /**
     * load config early
     */
    public function onload()
    {
        error_log(print_r("onload\n",1),3,"/tmp/log");
        $this->load_config();
    }

}
