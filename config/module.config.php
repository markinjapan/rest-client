<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'MarkInJapan\\RestClient\\RestClient' => 'MarkInJapan\\RestClient\\Factory\\RestClientFactory',
        )
    ),
    // Default configuration
    'rest-client' => array(
        'http_client' => array(
            'keepalive' => true,
        ),
    ),
);
