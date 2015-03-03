<?php

/*
 +-----------------------------------------------------------------------+
 | Copyright (C) 2005-2014, Cor Bosman                                   |
 |                                                                       |
 | Licensed under the GNU General Public License version 2               |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Provide memcached supported session management                      |
 +-----------------------------------------------------------------------+
 | Author: Cor Bosman <cor@roundcu.be>                                   |
 +-----------------------------------------------------------------------+
*/

/**
 * Class to provide memcached session storage
 *
 * @author     Cor Bosman <cor@roundcu.be>
 */
class rcube_session_memcached extends rcube_session {

    private $memcached;

    /**
     * @param Object $config
     */
    public function __construct($config)
    {
        // must call parent construct
        parent::__construct($config);

        // instantiate memcached object
        $this->memcached = new Memcached();

        if (!$this->memcached) {
            rcube::raise_error(array('code' => 604, 'type' => 'session',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Failed to find Memcached. Make sure php-memcached is included"),
                               true, true);
        }

        // get config instance
        $hosts = $this->config->get('memcached_hosts', array('localhost:11211'));

        // host config is wrong
        if (!is_array($hosts) || empty($hosts)) {
            rcube::raise_error(array('code' => 604, 'type' => 'session',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Memcached host not configured"),
                               true, true);
        }

        $servers = array();

        foreach ($hosts as $host) {
            // explode individual fields
            list($host, $port, $weight) = array_pad(explode(':', $host, 3), 3, null);

            // set default values if not set
            $host = ($host !== null) ? $host : '127.0.0.1';
            $port = ($port !== null) ? $port : 11211;
            $weight = ($weight !== null) ? $weight : 1;

            $servers[] = array($host, $port, $weight);
        }

        // add the servers
        $this->memcached->addServers($servers);

        // use compression
        $this->memcached->setOption(Memcached::OPT_COMPRESSION, true);

        // register sessions handler
        $this->register_session_handler();
    }

    /**
     * @param $save_path
     * @param $session_name
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * remove data from store
     *
     * @param $key
     * @return bool
     */
    public function destroy($key)
    {
        if ($key) {
            $this->memcached->delete($key, 0);
        }

        return true;
    }


    /**
     * read data from memcache store
     *
     * @param $key
     * @return null
     */
    public function read($key)
    {
        if ($value = $this->memcached->get($key)) {
            $arr = unserialize($value);
            $this->changed = $arr['changed'];
            $this->ip      = $arr['ip'];
            $this->vars    = $arr['vars'];
            $this->key     = $key;

            return !empty($this->vars) ? (string) $this->vars : '';
        }

        return null;
    }


    /**
     * update memcached session data
     *
     * @param $key
     * @param $newvars
     * @param $oldvars
     * @return bool
     */
    public function update($key, $newvars, $oldvars)
    {
        $ts = microtime(true);

        if ($newvars !== $oldvars || $ts - $this->changed > $this->lifetime / 3) {
            return $this->memcached->set($key, serialize(array('changed' => time(), 'ip' => $this->ip, 'vars' => $newvars)),
                                        $this->lifetime + 60);
        }

        return true;
    }

    /**
     * write data to memcache storage
     *
     * @param $key
     * @param $vars
     * @return bool
     */
    public function write($key, $vars)
    {
        return $this->memcached->set($key, serialize(array('changed' => time(), 'ip' => $this->ip, 'vars' => $vars)),
                                    $this->lifetime + 60);
    }


}