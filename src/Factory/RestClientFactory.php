<?php

namespace MarkInJapan\RestClient\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Http\Client as HttpClient;
use MarkInJapan\RestClient\RestClient;
use MarkInJapan\RestClient\Listener\ApiAuthenticationListener;

class RestClientFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = array();

        if ($serviceLocator->has('Config')) {
            $appConfig = $serviceLocator->get('Config');
            if (isset($appConfig['rest-client'])
                and is_array($appConfig['rest-client'])
            ) {
                $config = $appConfig['rest-client'];
            }
        }

        // Create new HTTP client and configure
        $http_client = new HttpClient;
        if (isset($config['http_client'])) {
            $http_client->setOptions($config['http_client']);
        }

        // Create new RestClient with config, and set HTTP client
        $rest_client = new RestClient($config);
        /** @todo Consider including HTTP client in constructor - it's required for correct operation */
        $rest_client->setHttpClient($http_client);

        // Create new API authentication listener, configure, and attach to REST client
        $api_authentication_listener = new ApiAuthenticationListener($config['authentication']);
        $rest_client->getEventManager()->attachAggregate($api_authentication_listener);

        return $rest_client;
    }
}
