<?php

namespace MarkInJapan\RestClient\Exception;

use Exception;
use Traversable;
use stdClass;

class RestClientException extends Exception
{
    /**
     * @var string Type
     */
    protected $_type;

    /**
     * @var string Detail
     */
    protected $_detail;

    /**
     * @var array Additional details
     */
    protected $_additional_details;

    /**
     * @var string Instance
     */
    protected $_instance;

    /**
     * Constructor
     * @param int $code Exception code
     * @param stdClass|Traversable|string $problem Either plain text, or traversable object or array containing problem details
     */
    public function __construct($code, $problem = null)
    {
        // If $problem is stdClass, or traversable object
        if ($problem instanceof stdClass
            or $problem instanceof Traversable
        ) {
            $message = null;

            // Assign keys from problem object to appropriate internal properties
            foreach ($problem as $key => $value) {
                switch ($key) {
                    case 'type':
                        $this->_type = $value;
                        break;

                    case 'title':
                        $message = $value;
                        break;

                    case 'detail':
                        $this->_detail = $value;
                        break;

                    case 'instance':
                        $this->_instance = $value;
                        break;

                    default:
                        $this->_additional_details[$key] = $value;
                }
            }

            parent::__construct($message, $code);
        } elseif (is_string($problem)) {
            parent::__construct($problem, $code);
        }
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getDetail()
    {
        return $this->_detail;
    }

    public function getInstance()
    {
        return $this->_instance;
    }

    /**
     * Get additional details, optionally by name
     * @param string|int $name Named additional detail
     * @return mixed
     */
    public function getAdditionalDetails($name = null)
    {
        if ($name === null) {
            return $this->_additional_details;
        } elseif (array_key_exists($name, $this->_additional_details)) {
            return $this->_additional_details[$name];
        } else {
            return null;
        }
    }
}