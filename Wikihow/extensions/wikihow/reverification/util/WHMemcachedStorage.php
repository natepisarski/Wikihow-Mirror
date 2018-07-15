<?php

use TokenBucket\Storage\StorageInterface;

class WHMemcachedStorage implements StorageInterface
{
    /**
     * @var \MemcachedPhpBagOStuff|null
     */
    private $memcachedObj = null;

	/**
	 * @return string
	 */
	public function getStorageName() {
		return 'WHMemcachedStorage';
	}


	public function __construct(\MemcachedPhpBagOStuff $memcachedObj)
    {
        $this->memcachedObj = $memcachedObj;
    }

    public function get($key)
    {
        $data = $this->memcachedObj->get(wfMemcKey($key));
	    wfDebugLog('throttle', var_export(__METHOD__, true));
	    wfDebugLog('throttle', var_export($key, true));
	    wfDebugLog('throttle', var_export($data, true));
        if (!is_array($data)) {
            $data = false;
        }

        return $data;
    }

    public function set($key, $value)
    {
	    wfDebugLog('throttle', var_export(__METHOD__, true));
	    wfDebugLog('throttle', var_export($key, true));
	    wfDebugLog('throttle', var_export($value, true));
	   return $this->memcachedObj->set(wfMemcKey($key), $value);
    }

    public function delete($key)
    {
        return $this->memcachedObj->delete(wfMemcKey($key));
    }
}