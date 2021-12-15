<?php
/**
 * This file is part of ninja-mutex.
 *
 * (C) Kamil Dziedzic <arvenil@klecza.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NinjaMutex\Lock;

/**
 * Abstract lock implementor
 *
 * @author Kamil Dziedzic <arvenil@klecza.pl>
 */
abstract class LockAbstract implements LockInterface
{
    const USLEEP_TIME = 100;

    /**
     * Information which allows to track down process which acquired lock
     *
     * @var array
     */
    protected $lockInformation = array();

    /**
     * @var array
     */
    protected $locks = array();

    public function __construct()
    {
        $this->lockInformation = $this->generateLockInformation();
    }

    public function __clone()
    {
        $this->locks = array();
    }

    /**
     * Acquire lock
     *
     * @param  string   $name    name of lock
     * @param  null|int $timeout 1. null if you want blocking lock
     *                           2. 0 if you want just lock and go
     *                           3. $timeout > 0 if you want to wait for lock some time (in milliseconds)
     * @return bool
     */
    public function acquireLock($name, $timeout = null)
    {
        $blocking = $timeout === null;
        $start = microtime(true);
        $end = $start + $timeout / 1000;
        $locked = false;
        while (!(empty($this->locks[$name]) && $locked = $this->getLock($name, $blocking)) && ($blocking || ($timeout > 0 && microtime(true) < $end))) {
            usleep(static::USLEEP_TIME);
        }

        if ($locked) {
            $this->locks[$name] = true;

            return true;
        }

        return false;
    }

    /**
     * @param  string $name
     * @param  bool   $blocking
     * @return bool
     */
    abstract protected function getLock($name, $blocking);

    /**
     * Information generate by this method allow to track down process which acquired lock
     * .
     * By default it returns array with:
     * 1. pid
     * 2. server_ip
     * 3. server_name
     *
     * @return array
     */
    protected function generateLockInformation()
    {
        $pid = getmypid();
        $hostname = gethostname();
        $host = gethostbyname($hostname);

        // Compose data to one string
        $params = array();
        $params[] = $pid;
        $params[] = $host;
        $params[] = $hostname;

        return $params;
    }

    /**
     * Information returned by this method allow to track down process which acquired lock
     * .
     * @return array
     */
    protected function getLockInformation()
    {
        return $this->lockInformation;
    }
}
