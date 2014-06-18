<?php

namespace MarkInJapan\RestClient;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Client as HttpClient;
use Zend\Http\Response as HttpResponse;

use MarkInJapan\RestClient\Exception as RestClientException;

class RestClient implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @var array Config
     */
    protected $_config;

    /**
     * @var Zend\Http\Client HTTP client
     */
    protected $_http_client;

    /**
     * Constructor
     * @param array $config Config
     */
    public function __construct(array $config = array())
    {
        $this->_config = $config;
    }

    /**
     * Send GET request
     * @param string $url API stub URL
     * @param array $params Request params
     */
    public function get($url, array $params = array())
    {
        return $this->send(Request::METHOD_GET, $url, $params);
    }

    /**
     * Send POST request
     * @param string $url API stub URL
     * @param array $params Request params
     * @param array $data Request payload (data)
     */
    public function post($url, array $params = array(), array $data = array())
    {
        return $this->send(Request::METHOD_POST, $url, $params, $data);
    }

    /**
     * Send PUT request
     * @param string $url API stub URL
     * @param array $params Request params
     * @param array $data Request payload (data)
     */
    public function put($url, array $params = array(), array $data = array())
    {
        return $this->send(Request::METHOD_PUT, $url, $params, $data);
    }

    /**
     * Send PATCH request
     * @param string $url API stub URL
     * @param array $params Request params
     * @param array $data Request payload (data)
     */
    public function patch($url, array $params = array(), array $data = array())
    {
        return $this->send(Request::METHOD_PATCH, $url, $params, $data);
    }

    /**
     * Send DELETE request
     * @param string $url API stub URL
     * @param array $params Request params
     */
    public function delete($url, array $params = array())
    {
        return $this->send(Request::METHOD_DELETE, $url, $params);
    }

    /**
     * Send API request
     * @param string $method HTTP method
     * @param string $url API stub URL
     * @param array $params Request params
     * @param array $data Request payload (data)
     * @return array
     */
    public function send($method, $url, array $params = array(), array $data = array())
    {
        // Set method in HTTP client
        $this->_http_client
            ->resetParameters()
            ->setMethod($method)
            ->setUri($this->_config['base_url'] . $url);

        // Set params and data in HTTP client, depending on method
        switch ($method) {
            case Request::METHOD_POST:
            case Request::METHOD_PATCH:
                $this->_http_client
                    ->setEncType('application/json')
                    ->setRawBody(json_encode($data));

            case Request::METHOD_GET:
                $this->_http_client->setParameterGet($params);

        }

        // Trigger "send.pre" event
        $this->getEventManager()->trigger('send.pre', $this);

        /** @todo Worth setting up configurable listener for Accept types and attach to send.pre?
         * Although JSON is current standard, hal+json or other variants may need configuring...
         */
        $this->_http_client->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');

        // Send request
        $response = $this->_http_client->send();

        // Trigger "send.post" event
        $this->getEventManager()->trigger('send.post', $this);

// var_dump(
//     $this->_http_client->getRequest(),
//     $response->getStatusCode(),
//     $response->getHeaders(),
//     $response->getBody()
// );

        // Access Forbidden
        if ($response->isForbidden()) {
            throw new RestClientException\AccessException(
                $response->getStatusCode(),
                $this->_getApiProblemFromResponse($response)
            );
        }
        // Not Found
        elseif ($response->isNotFound()) {
            throw new RestClientException\ResourceException(
                $response->getStatusCode(),
                $this->_getApiProblemFromResponse($response)
            );
        }
        // Client Error
        elseif ($response->isClientError()) {
            throw new RestClientException\ClientException(
                $response->getStatusCode(),
                $this->_getApiProblemFromResponse($response)
            );
        }
        // Server Error
        elseif ($response->isServerError()) {
            throw new RestClientException\ServerException(
                $response->getStatusCode(),
                $this->_getApiProblemFromResponse($response)
            );
        }
        // Created
        elseif ($response->getStatusCode() === 201) {
            // Check for "Location" header
            if ($response->getHeaders()->has('Location') === false) {
                throw new RestClientException\ResponseException(
                    500,
                    'Response from server missing "Location" header'
                );
            }
        }

        // If JSON response
        if ($response->getHeaders()->get('ContentType')->match(array(
            'application/*+json',
            'application/json',
        ))) {
            // Decode JSON body and return
            return json_decode($response->getBody());
        } else {
            // Return body directly
            return $response->getBody();
        }
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

    /**
     * Get HTTP client
     * @return Zend\Http\Client
     */
    public function getHttpClient()
    {
        return $this->_http_client;
    }

    /**
     * Set HTTP client
     * @param Zend\Http\Client $client HTTP client
     * @return self
     */
    public function setHttpClient(HttpClient $client)
    {
        $this->_http_client = $client;
        return $this;
    }

    /**
     * Get API Problem from response
     * @param Response $response HTTP response object
     * @return object
     */
    protected function _getApiProblemFromResponse(HttpResponse $response)
    {
        // If ContentType is API Problem (application/problem+json)
        if ($response->getHeaders()->get('ContentType')->match('application/problem+json')) {
            return json_decode($response->getBody());
        }
        // Handle other responses by mimicing API Problem structure
        else {
            return (object)[
                'title'  => $response->getReasonPhrase(),
                'detail' => $response->getBody(),
            ];
        }
    }
}