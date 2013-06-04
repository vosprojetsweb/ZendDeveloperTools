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

/**
 * Php Info Collector.
 *
 * Collects main information about php configuration :
 *  - error stuff
 *  - security stuff
 *
 */
class PhpInfoCollector extends AbstractCollector
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'phpinfo';
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return PHP_INT_MAX - 1;
    }

    /**
     * @inheritdoc
     */
    public function collect(MvcEvent $mvcEvent)
    {
        if (!isset($this->data)) {
            $this->data = array();
        }

        $coreConfiguration = ini_get_all('core');

        //All configuration variables about error
        $this->data['errors'] = array();
        foreach ($coreConfiguration as $name => $details) {
            if (strpos($name, 'error') !== false) {
                $this->data['errors'][$name] = $details;
            }
        }

        $expectedConfiguration = array(
            'expose_php' => 'Off'
        );

        //All Configuration variables about security
        foreach ($expectedConfiguration as $name => $expectedValue) {
            $details = $coreConfiguration[$name];
            $details['expected_value'] = $expectedValue;
            $this->data['security'][$name] = $details;
        }
    }


    public function getErrorsInfo()
    {
        return $this->data['errors'];
    }

    public function getSecurityInfo()
    {
        return $this->data['security'];
    }
}