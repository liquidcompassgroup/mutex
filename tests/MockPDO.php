<?php
/**
 * This file is part of Mutex.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  Arvenil\Ninja\Mutex
 */
namespace Arvenil\Ninja\Mutex;

/**
 * Mock PDO to mimic *_lock functionality
 * 
 * @author Kamil Dziedzic <arvenil@klecza.pl>
 */
class MockPDO extends \PDO {
    /**
     * @var string[]
     */
    protected static $data = array();

    /**
     * @var string[]
     */
    protected $current = array();

    public function __construct () {}

    public function query($statement) {
        if (preg_match('/RELEASE_LOCK\("(.*)"\)/', $statement, $m)) {
            return $this->_mock_release_lock($m[1]);
        } elseif (preg_match('/GET_LOCK\("(.*)", *(.*)\)/', $statement, $m)) {
            return $this->_mock_get_lock($m[1], $m[2]);
        } elseif (preg_match('/IS_FREE_LOCK\("(.*)"\)/', $statement, $m)) {
            return $this->_mock_is_free_lock($m[1]);
        }
    }

    protected function _mock_get_lock($key, $timeout) {
        // http://dev.mysql.com/doc/refman/5.0/en/miscellaneous-functions.html#function_get-lock
        //
        // "If you have a lock obtained with GET_LOCK(),
        // it is released when you (...) execute a new GET_LOCK()"
        //
        // SELECT IS_FREE_LOCK( 'a' ) , GET_LOCK( 'a', 0 ) , IS_FREE_LOCK( 'a' ) , GET_LOCK( 'a', 0 )
        // IS_FREE_LOCK('a') GET_LOCK('a', 0) IS_FREE_LOCK('a') GET_LOCK('a', 0)
        // 1                 1                0                 1
        if (current($this->_mock_is_free_lock($key)) || isset($this->current[$key])) {
            self::$data[$key] = true;
            $this->current[$key] = true;
            return array("1");
        }

        // We use sleep because GET_LOCK(str,timeout) accept timeout in seconds
        sleep($timeout);
        return array("0");
    }

    protected function _mock_is_free_lock($key) {
        if (isset(self::$data[$key])) {
            return array("0");
        }

        return array("1");
    }

    protected function _mock_release_lock($key) {
        unset(self::$data[$key]);
        unset($this->current[$key]);
        return array("1");
    }
}