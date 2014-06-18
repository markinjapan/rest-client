<?php

namespace MarkInJapan\RestClient\Listener;

use Zend\EventManager\ListenerAggregateTrait;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Zend\Http\Client as HttpClient;

class ApiAuthenticationListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @const string Supported HTTP Authentication methods
     */
    const AUTH_OAUTH2 = 'oauth2';

    /**
     * @var array Config
     */
    protected $_config;

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        // Make sure authentication config set
        if (!empty($this->_config['type'])
            and !empty($this->_config['username'])
            and !empty($this->_config['password'])
        ) {
            // Attach different event depending on config
            switch ($this->_config['type']) {
                case HttpClient::AUTH_BASIC:
                $this->listeners[] = $events->attach('send.pre', array($this, 'authenticateHttpBasic'), 100);
                break;
                case HttpClient::AUTH_DIGEST:
                $this->listeners[] = $events->attach('send.pre', array($this, 'authenticateHttpDigest'), 100);
                break;
                case self::AUTH_OAUTH2:
                /** @todo Make sure any OAuth2 specific config is setup? */
                $this->listeners[] = $events->attach('send.pre', array($this, 'authenticateOauth2'), 100);
                break;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Authenticate with HTTP Basic
     * @param EventInterface $e Event
     * @return ?
     */
    public function authenticateHttpBasic(EventInterface $e)
    {
        /** @todo May only need to setup authentication once? Can detach after run? */
        /** @todo Make sure username and password are set */

        // Get HTTP client and set basic authentication
        $e->getTarget()->getHttpClient()->setAuth($this->_config['username'], $this->_config['password'], HttpClient::AUTH_BASIC);
    }

    /**
     * Authenticate with OAuth2
     * @param EventInterface $e Event
     * @return ?
     */
    public function authenticateOauth2(EventInterface $e)
    {
        // Depending on OAuth2 grant_type, may need to do a multitude of requests
        // Is there OAuth 2 client out there already?
    }

    /**
     * Set config
     * @param array $config Config
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
        return $this;
    }
}
