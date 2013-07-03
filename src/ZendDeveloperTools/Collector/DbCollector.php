<?php
/**
 * Zend Developer Tools for Zend Framework (http://framework.zend.com/)
 *
 * @link       http://github.com/zendframework/ZendDeveloperTools for the canonical source repository
 * @copyright  Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDeveloperTools\Collector;

use Zend\Mvc\MvcEvent;
use BjyProfiler\Db\Profiler\Profiler;
use Zend\Db\Adapter\Adapter;

/**
 * Database (Zend\Db) Data Collector.
 *
 */
class DbCollector implements CollectorInterface, AutoHideInterface, \Serializable
{
    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var array
     */
    protected $explains;

    /**
     * @var Adapter
     */
    protected $adapter;


    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'db';
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     *
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function mysqlExplain($query, $parameters)
    {
        $result = $this->adapter->getDriver()->createStatement('EXPLAIN ' . $query)->execute($parameters);

        $explain = array();
        foreach ($result as $row) {
            $explain[] = $row;
        }

        return $explain;
    }

    /**
     * @inheritdoc
     */
    public function collect(MvcEvent $mvcEvent)
    {
        if ($this->adapter->getDriver()->getDatabasePlatformName() === 'Mysql') {
            foreach ($this->profiler->getQueryProfiles(Profiler::SELECT) as $profile) {
                $query = $profile->toArray();
                $this->explains[md5($query['sql'])] = $this->mysqlExplain($query['sql'], $query['parameters']);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function canHide()
    {
        if (!isset($this->profiler)) {
            return false;
        } elseif ($this->getQueryCount() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Has the collector access to Bjy's Db Profiler?
     *
     * @return bool
     */
    public function hasProfiler()
    {
        return isset($this->profiler);
    }

    /**
     * Returns Bjy's Db Profiler
     *
     * @return Profiler
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * Sets Bjy's Db Profiler
     *
     * @param  Profiler $profiler
     * @return self
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;

        return $this;
    }

    /**
     * Returns the number of queries send to the database.
     *
     * You can use the constants in the Profiler class to specify
     * what kind of queries you want to get, e.g. Profiler::INSERT.
     *
     * @param  integer $mode
     * @return self
     */
    public function getQueryCount($mode = null)
    {
        return count($this->profiler->getQueryProfiles($mode));
    }

    /**
     * Returns the total time the queries took to execute.
     *
     * You can use the constants in the Profiler class to specify
     * what kind of queries you want to get, e.g. Profiler::INSERT.
     *
     * @param  integer $mode
     * @return float|integer
     */
    public function getQueryTime($mode = null)
    {
        $time = 0;

        foreach ($this->profiler->getQueryProfiles($mode) as $query) {
            $time += $query->getElapsedTime();
        }

        return $time;
    }

    public function getExplain($sql) {
        $key = md5($sql);
        return (isset($this->explains[$key]) === true) ? $this->explains[$key] : null;
    }

    /**
     * @see \Serializable
     */
    public function serialize()
    {
        return serialize(array($this->profiler, $this->explains));
    }

    /**
     * @see \Serializable
     */
    public function unserialize($data)
    {
        list($this->profiler, $this->explains) = unserialize($data);
    }
}