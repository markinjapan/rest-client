<?php

namespace MarkInJapan\RestClient;

use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\ResultSet\ResultSet;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class RestGateway
{
    /**
     * @var string Resource slug
     */
    protected $_resource_slug;

    /**
     * @var RestClient REST client
     */
    protected $_client;

    /**
     * @var ResultSetInterface Result set prototype
     */
    protected $_object_prototype;

    /**
     * Constructor
     * @param string $resource_slug API resource URL slug
     * @param RestClient $client REST client
     * @param ResultSetInterface $result_set_prototype Prototype result set to use for handling results
     */
    public function __construct($resource_slug, RestClient $client, ResultSetInterface $result_set_prototype = null)
    {
        $this->_client = $client;
        $this->_resource_slug = $resource_slug;
        if ($result_set_prototype === null) {
            $this->_result_set_prototype = new ResultSet;
        } else {
            $this->_result_set_prototype = $result_set_prototype;
        }
    }

    /**
     * Create resource
     * @param array $data Key => value array of data
     * @return int New resource ID
     */
    public function create(array $data)
    {
        $result = $this->_client->post($this->_resource_slug, array(), $data);

        // Get HTTP response
        $response = $this->_client->getHttpClient()->getResponse();

        // Check for "201 Created" status
        if ($response->getStatusCode() !== 201) {
            throw new UnexpectedValueException('Unable to CREATE resource');
        }

        // Extract ID from "Location" header (assumes last parameter is ID)
        $location_split = explode('/', $response->getHeaders()->get('Location')->getUri());
        $id = array_pop($location_split);
        if (empty($id)) {
            throw new RuntimeException('Unable to extract entity identifier from Location header in API response');
        }

        return $id;
    }

    /**
     * Fetch resource by ID
     * @param int $id Resource ID
     * @return object Hydrated entity with reference back to gateway
     */
    public function fetch($id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('ID must not be empty');
        }

        $result = $this->_client->get($this->_resource_slug . '/' . $id);

        // Get HTTP response
        $response = $this->_client->getHttpClient()->getResponse();

        // Check for "200 OK" response
        if ($response->isOk() === false) {
            throw new UnexpectedValueException('Unable to GET resource');
        }

        if ($result) {
            // Build result set
            $result_set = clone $this->_result_set_prototype;
            $result_set->initialize(array(
                $result,
            ));
            return $result_set;

            // Hydrate object and add to result set
            // $this->_hydrator->hydrate((array)$result, $object);
        }

        return false;
    }

    /**
     * Fetch all resources
     * @param array $params Params to fetch by
     * @return array Hydrated entities
     */
    public function fetchAll(array $params)
    {
        // if (empty($params)) {
        //     throw new InvalidArgumentException('Params must not be empty');
        // }

        $result = $this->_client->get($this->_resource_slug, $params);

        // Get HTTP response
        $response = $this->_client->getHttpClient()->getResponse();

        // Check for "200 OK" response
        if ($response->isOk() === false) {
            throw new UnexpectedValueException('Unable to GET resource');
        }

        // If HAL response
        if ($response->getHeaders()->get('ContentType')->match('application/hal+json')) {
            /**
             * @todo HAL response is not just simple array of objects, to be converted into array of "Entities"...
             * HAL response includes links, pagination, etc, so need a "Collection" that can handle this.
             */

            // Extract links, store as object properties
            //

            // Extract data
            // $data = $result->_embedded->{$this->_config['entity']};
        } else {
            // Build result set
            $result_set = clone $this->_result_set_prototype;
            $result_set->initialize($result);
            return $result_set;
        }

        return false;
    }

    /**
     * Update (patch) a resource
     * @param int $id Resource ID
     * @param array $data Key => value array of data
     * @return bool
     */
    public function update($id, array $data, array $params = array())
    {
        return $this->_client->patch($this->_resource_slug . '/' . $id, $params, $data);
    }

    /**
     * Update (patch) a collection
     * @param array $data Key => value array of data
     * @param array $params Key => value array of conditions
     * @return bool
     */
    public function updateAll(array $data, array $params = array())
    {
        return $this->_client->patch($this->_resource_slug, $params, $data);
    }

    /**
     * Replace (put) a resource
     * @param int $id Resource ID
     * @param array Key => value array of data
     * @return bool
     */
    public function replace($id, array $data)
    {
        throw new RuntimeException('Not yet implemented');
    }

    /**
     * Replace (put) a collection
     * @param array Key => value array of data
     * @return bool
     */
    public function replaceAll(array $data)
    {
        throw new RuntimeException('Not yet implemented');
    }

    /**
     * Get REST client
     * @return RestClient
     */
    public function getRestClient()
    {
        return $this->_client;
    }

    /**
     * Set REST client
     * @param RestClient $client REST client
     * @return self
     */
    public function setRestClient(RestClient $client)
    {
        $this->_client = $client;
        return $this;
    }
}