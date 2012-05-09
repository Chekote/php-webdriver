<?php
/**
 * Copyright 2004-2012 Facebook. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package WebDriver
 *
 * @author Justin Bishop <jubishop@gmail.com>
 * @author Anthon Pang <anthonp@nationalfibre.net>
 * @author Fabrizio Branca <mail@fabrizio-branca.de>
 * @author Tsz Ming Wong <tszming@gmail.com>
 */

/**
 * Abstract WebDriver_Base class
 *
 * @package WebDriver
 */
abstract class WebDriver_Base
{
    /**
     * URL
     *
     * @var string
     */
    protected $url;

    /**
     * Return array of supported method names and corresponding HTTP request types
     *
     * @return array
     */
    abstract protected function methods();

    /**
     * Return array of obsolete method names and corresponding HTTP request types
     *
     * @return array
     */
    protected function obsoleteMethods()
    {
        return array();
    }

    /**
     * Constructor
     *
     * @param string $url URL to Selenium server
     */
    public function __construct($url = 'http://localhost:4444/wd/hub')
    {
        $this->url = $url;
    }

    /**
     * Magic method which returns URL to Selenium server
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    /**
     * Returns URL to Selenium server
     *
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Curl request to webdriver server.
     *
     * @param string $requestMethod HTTP request method, e.g., 'GET', 'POST', or 'DELETE'
     * @param string $command       If not defined in methods() this function will throw.
     * @param array  $parameters    If an array(), they will be posted as JSON parameters
     *                              If a number or string, "/$params" is appended to url
     * @param array  $extraOptions  key=>value pairs of curl options to pass to curl_setopt()
     *
     * @return array array('value' => ..., 'info' => ...)
     *
     * @throws WebDriver_Exception if error
     */
    protected function curl($requestMethod, $command, $parameters = null, $extraOptions = array())
    {
        if ($parameters && is_array($parameters) && $requestMethod !== 'POST') {
            throw WebDriver_Exception::factory(WebDriver_Exception::NO_PARAMETERS_EXPECTED, sprintf(
                'The http method called for %s is %s but it has to be POST' .
                ' if you want to pass the JSON params %s',
                $command,
                $requestMethod,
                json_encode($parameters)
            ));
        }

        $url = sprintf('%s%s', $this->url, $command);
        if ($parameters && (is_int($parameters) || is_string($parameters))) {
            $url .= '/' . $parameters;
        }

        $curl = WebDriver_Environment::CurlInit($requestMethod, $url, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8', 'Accept: application/json'));

        if ($requestMethod === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($parameters && is_array($parameters)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
            }
        } else if ($requestMethod == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        foreach ($extraOptions as $option => $value) {
            curl_setopt($curl, $option, $value);
        }

        $rawResults = trim(WebDriver_Environment::CurlExec($curl));
        $info = curl_getinfo($curl);

        if ($error = curl_error($curl)) {
            $message = sprintf(
                'Curl error thrown for http %s to %s$s',
                $requestMethod,
                $url,
                $parameters && is_array($params)
                ? ' with params: ' . json_encode($parameters) : ''
            );

            throw WebDriver_Exception::factory(WebDriver_Exception::CURL_EXEC, $message . "\n\n" . $error);
        }

        curl_close($curl);

        $results = json_decode($rawResults, true);
        $value   = null;

        if (is_array($results) && array_key_exists('value', $results)) {
            $value = $results['value'];
        }

        $message = null;

        if (is_array($value) && array_key_exists('message', $value)) {
            $message = $value['message'];
        }

        // if not success, throw exception
        if ($results['status'] != 0) {
            throw WebDriver_Exception::factory($results['status'], $message);
        }

        return array('value' => $value, 'info' => $info);
    }

    /**
     * Magic method that maps calls to class methods to execute WebDriver commands
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (count($arguments) > 1) {
            throw WebDriver_Exception::factory(WebDriver_Exception::JSON_PARAMETERS_EXPECTED,
                'Commands should have at most only one parameter,' .
                ' which should be the JSON Parameter object'
            );
        }

        if (preg_match('/^(get|post|delete)/', $name, $matches)) {
            $requestMethod = strtoupper($matches[0]);
            $webdriverCommand = strtolower(substr($name, strlen($requestMethod)));
        } else if (count($arguments) > 0) {
            $webdriverCommand = $name;
            $this->getRequestMethod($webdriverCommand);
            $requestMethod = 'POST';
        } else {
            $webdriverCommand = $name;
            $requestMethod = $this->getRequestMethod($webdriverCommand);
        }

        $methods = $this->methods();
        if (!in_array($requestMethod, (array) $methods[$webdriverCommand])) {
            throw WebDriver_Exception::factory(WebDriver_Exception::INVALID_REQUEST, sprintf(
                '%s is not an available http method for the command %s.',
                $requestMethod,
                $webdriverCommand
            ));
        }

        $results = $this->curl(
            $requestMethod,
            '/' . $webdriverCommand,
            array_shift($arguments)
        );

        return $results['value'];
    }

    /**
     * Get default HTTP request method for a given WebDriver command
     *
     * @param string $webdriverCommand
     *
     * @return string
     *
     * @throws Exception if invalid WebDriver command
     */
    private function getRequestMethod($webdriverCommand)
    {
        if (!array_key_exists($webdriverCommand, $this->methods())) {
            throw WebDriver_Exception::factory(array_key_exists($webdriverCommand, $this->obsoleteMethods())
                ? WebDriver_Exception::OBSOLETE_COMMAND : WebDriver_Exception::UNKNOWN_COMMAND,
                sprintf('%s is not a valid WebDriver command.', $webdriverCommand)
            );
        }

        $methods = $this->methods();
        $requestMethods = (array) $methods[$webdriverCommand];

        return array_shift($requestMethods);
    }
}
