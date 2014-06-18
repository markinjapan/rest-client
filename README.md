rest-client
===============

**Warning**

This is still mostly a work in progress!

Introduction
------------

A RESTful API client, built for use with ZF2.

Included components:

* REST client - for making RESTful API calls
* REST gateway - for dealing with RESTful API resources
* API authentication handler - for adding appropriate authentication headers when making API calls

Currently only HTTP Basic authentication is supported, but there are plans to add more authentication methods in future (check "Coming Soon").

Assumptions
-----------

This library assumes responses will be JSON encoded, as this is becoming the defacto standard for API payloads.

It's also assumed the server will send "API Problem" like responses when errors occur, as per the draft ["Problem Details for HTTP APIs"](http://tools.ietf.org/html/draft-nottingham-http-problem-06) standard. These are converted into Exceptions that can be used in your application.

Coming Soon
-----------

* Support for HTTP Digest authentication
* Support for OAuth 2 "client credentials" authentication
* Support for HAL JSON payloads (through HAL-aware Collection and Entity objects)

What about XML?
---------------

This API client is designed to only work with JSON payloads, and there are no plans for adding XML support as this is becoming the lesser used format for API payloads.