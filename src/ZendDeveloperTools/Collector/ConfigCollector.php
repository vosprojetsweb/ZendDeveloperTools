<?php
/**
 * Zend Developer Tools for Zend Framework (http://framework.zend.com/)
 *
 * @link       http://github.com/zendframework/ZendDeveloperTools for the canonical source repository
 * @copyright  Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendDeveloperTools\Collector;

use Serializable;
use Traversable;
use Closure;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ArrayUtils;
use ZendDeveloperTools\Stub\ClosureStub;

/**
 * Config data collector - dumps the contents of the `Config` and `ApplicationConfig` services
 */
class ConfigCollector implements CollectorInterface, Serializable
{
    const NAME     = 'config';
    const PRIORITY = 100;

    /**
     * @var array|null
     */
    protected $config;

    /**
     * @var array|null
     */
    protected $applicationConfig;

    /**
     * @var array|null
     */
    protected $phpConfig = null;

    /**
     * @var array|null
     */
    protected $phpIniFiles = null;


    /**
     * @inheritdoc
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return static::PRIORITY;
    }

    /**
     * {@inheritDoc}
     */
    public function collect(MvcEvent $mvcEvent)
    {
        if (! $application = $mvcEvent->getApplication()) {
            return;
        }

        $serviceLocator = $application->getServiceManager();

        if ($serviceLocator->has('Config')) {
            $this->config = $this->makeArraySerializable($serviceLocator->get('Config'));
        }

        if ($serviceLocator->has('ApplicationConfig')) {
            $this->applicationConfig = $this->makeArraySerializable($serviceLocator->get('ApplicationConfig'));
        }

        if ($serviceLocator->has('ZendDeveloperTools\Config') === true) {
            $config = $serviceLocator->get('ZendDeveloperTools\Config')->getCollectorOptions('config');

            $this->phpConfig = array();
            foreach ($config['php_directives'] as $name) {
                $this->phpConfig[] = array(
                    'directive' => $name,
                    'value' => ini_get($name)
                );
            }
        }

        $this->phpIniFiles = array(php_ini_loaded_file()) + explode(',', php_ini_scanned_files());
    }

    /**
     * @return array|null
     */
    public function getConfig()
    {
        return isset($this->config) ? $this->unserializeArray($this->config) : null;
    }

    /**
     * @return array|null
     */
    public function getApplicationConfig()
    {
        return isset($this->applicationConfig) ? $this->unserializeArray($this->applicationConfig) : null;
    }

    /**
     *
     * @return array|null
     */
    public function getPhpConfig()
    {
        return $this->phpConfig;
    }

    public function hasPhpConfig()
    {
        return $this->phpConfig !== null;
    }

    /**
     * Rerturns the list of all PHP ini files parsed
     * @return array
     */
    public function getPhpIniFilesParsed()
    {
        return $this->phpIniFiles;
    }


    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                'config' => $this->config,
                'applicationConfig' => $this->applicationConfig,
                'phpConfig' => $this->phpConfig,
                'phpIniFiles' => $this->phpIniFiles
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data                    = unserialize($serialized);
        $this->config            = $data['config'];
        $this->applicationConfig = $data['applicationConfig'];
        $this->phpConfig         = $data['phpConfig'];
        $this->phpIniFiles         = $data['phpIniFiles'];
    }

    /**
     * Replaces the un-serializable items in an array with stubs
     *
     * @param array|\Traversable $data
     *
     * @return array
     */
    private function makeArraySerializable($data)
    {
        $serializable = array();

        foreach (ArrayUtils::iteratorToArray($data) as $key => $value) {
            if ($value instanceof Traversable || is_array($value)) {
                $serializable[$key] = $this->makeArraySerializable($value);

                continue;
            }

            if ($value instanceof Closure) {
                $serializable[$key] = new ClosureStub();

                continue;
            }

            $serializable[$key] = $value;
        }

        return $serializable;
    }

    /**
     * Opposite of {@see makeArraySerializable} - replaces stubs in an array with actual un-serializable objects
     *
     * @param array $data
     *
     * @return array
     */
    private function unserializeArray(array $data)
    {
        $unserialized = array();

        foreach (ArrayUtils::iteratorToArray($data) as $key => $value) {
            if ($value instanceof Traversable || is_array($value)) {
                $unserialized[$key] = $this->unserializeArray($value);

                continue;
            }

            if ($value instanceof ClosureStub) {
                $unserialized[$key] = function () {};

                continue;
            }

            $unserialized[$key] = $value;
        }

        return $unserialized;
    }
}
