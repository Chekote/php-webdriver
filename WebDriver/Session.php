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
 */

/**
 * WebDriver_Session class
 *
 * @package WebDriver
 *
 * @method string window_handle() Retrieve the current window handle.
 * @method array window_handles() Retrieve the list of all window handles available to the session.
 * @method string getUrl() Retrieve the URL of the current page
 * @method void url($jsonUrl) Navigate to a new URL
 * @method void forward() Navigates forward in the browser history, if possible.
 * @method void back() Navigates backward in the browser history, if possible.
 * @method void refresh() Refresh the current page.
 * @method mixed execute($jsonScript) Inject a snippet of JavaScript into the page for execution in the context of the currently selected frame. (synchronous)
 * @method mixed execute_async($jsonScript) Inject a snippet of JavaScript into the page for execution in the context of the currently selected frame. (asynchronous)
 * @method string screenshot() Take a screenshot of the current page.
 * @method void frame($jsonFrameId) Change focus to another frame on the page.
 * @method array getCookie() Retrieve all cookies visible to the current page.
 * @method array cookie($jsonCookie) Set a cookie.
 * @method string source() Get the current page source.
 * @method string title() Get the current page title.
 * @method void keys($jsonKeys) Send a sequence of key strokes to the active element.
 * @method string getOrientation() Get the current browser orientation.
 * @method void orientation($jsonOrientation) Set the current browser orientation.
 * @method string getAlert_text() Gets the text of the currently displayed JavaScript alert(), confirm(), or prompt() dialog.
 * @method void alert_text($jsonText) Sends keystrokes to a JavaScript prompt() dialog.
 * @method void accept_alert() Accepts the currently displayed alert dialog.
 * @method void dismiss_alert() Dismisses the currently displayed alert dialog.
 * @method void moveto($jsonCoordinates) Move the mouse by an offset of the specificed element (or current mouse cursor).
 * @method void click($jsonButton) Click any mouse button (at the coordinates set by the last moveto command).
 * @method void buttondown() Click and hold the left mouse button (at the coordinates set by the last moveto command).
 * @method void buttonup() Releases the mouse button previously held (where the mouse is currently at).
 * @method void doubleclick() Double-clicks at the current mouse coordinates (set by moveto).
 * @method array getLocation() Get the current geo location.
 * @method void location($jsonCoordinates) Set the current geo location.
 */
final class WebDriver_Session extends WebDriver_Container
{
    /**
     * @see http://code.google.com/p/selenium/source/browse/trunk/java/client/src/org/openqa/selenium/remote/CapabilityType.java
     * @see http://code.google.com/p/selenium/wiki/JsonWireProtocol#Capabilities_JSON_Object
     */
    const BROWSER_NAME                      = 'browserName';
    const VERSION                           = 'version';
    const PLATFORM                          = 'platform';
    const SUPPORTS_JAVASCRIPT               = 'javascriptEnabled';
    const TAKES_SCREENSHOT                  = 'takesScreenshot';
    const SUPPORTS_ALERTS                   = 'handlesAlerts';
    const SUPPORTS_SQL_DATABASE             = 'databaseEnabled';
    const SUPPORTS_LOCATION_CONTEXT         = 'locationContextEnabled';
    const SUPPORTS_APPLICATION_CACHE        = 'applicationCacheEnabled';
    const SUPPORTS_BROWSER_CONNECTION       = 'browserConnectionEnabled';
    const SUPPORTS_FINDING_BY_CSS           = 'cssSelectorsEnabled';
    const SUPPORTS_WEB_STORAGE              = 'webStorageEnabled';
    const ROTATABLE                         = 'rotatable';
    const ACCEPT_SSL_CERTS                  = 'acceptSslCerts';
    const HAS_NATIVE_EVENTS                 = 'nativeEvents';
    const PROXY                             = 'proxy';

    /**
     * Proxy types
     *
     * @see http://code.google.com/p/selenium/wiki/JsonWireProtocol#Proxy_JSON_Object
     */
    const DIRECT = 'direct';
    const MANUAL = 'manual';
    const PAC    = 'pac';
    const SYSTEM = 'system';

    /**
     * {@inheritdoc}
     */
    protected function methods()
    {
        return array(
            'window_handle' => array('GET'),
            'window_handles' => array('GET'),
            'url' => array('GET', 'POST'), // alternate for POST, use open($url)
            'forward' => array('POST'),
            'back' => array('POST'),
            'refresh' => array('POST'),
            'execute' => array('POST'),
            'execute_async' => array('POST'),
            'screenshot' => array('GET'),
            'frame' => array('POST'),
            'cookie' => array('GET', 'POST'), // for DELETE, use deleteAllCookies()
            'source' => array('GET'),
            'title' => array('GET'),
            'keys' => array('POST'),
            'orientation' => array('GET', 'POST'),
            'alert_text' => array('GET', 'POST'),
            'accept_alert' => array('POST'),
            'dismiss_alert' => array('POST'),
            'moveto' => array('POST'),
            'click' => array('POST'),
            'buttondown' => 'POST',
            'buttonup' => array('POST'),
            'doubleclick' => array('POST'),
            'location' => array('GET', 'POST'),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function obsoleteMethods()
    {
        return array(
            'modifier' => array('POST'),
            'speed' => array('GET', 'POST'),
        );
    }

    /**
     * Open URL: /session/:sessionId/url (POST)
     * An alternative to $session->url($url);
     *
     * @param string $url
     *
     * @return WebDriver_Session
     */
    public function open($url)
    {
        $this->curl('POST', '/url', is_array($url) ? $url : array('url' => $url));

        return $this;
    }

    /**
     * Get browser capabilities: /session/:sessionId (GET)
     *
     * @return mixed
     */
    public function capabilities()
    {
        $result = $this->curl('GET', '');

        return $result['value'];
    }

    /**
     * Close session: /session/:sessionId (DELETE)
     *
     * @return mixed
     */
    public function close()
    {
        $result = $this->curl('DELETE', '');

        return $result['value'];
    }

    // There's a limit to our ability to exploit the dynamic nature of PHP when it
    // comes to the cookie methods because GET and DELETE request methods are indistinguishable
    // from each other: neither takes parameters.

    /**
     * Get all cookies: /session/:sessionId/cookie (GET)
     * Alternative to: $session->cookie();
     *
     * @return mixed
     */
    public function getAllCookies()
    {
        $result = $this->curl('GET', '/cookie');

        return $result['value'];
    }

    /**
     * Set cookie: /session/:sessionId/cookie (POST)
     * Alternative to: $session->cookie($cookie_json);
     *
     * @param array $cookieJson
     *
     * @return WebDriver_Session
     */
    public function setCookie($cookieJson)
    {
        $this->curl('POST', '/cookie', is_array($cookieJson) ? $cookieJson : array('cookie' => $cookieJson));

        return $this;
    }

    /**
     * Delete all cookies: /session/:sessionId/cookie (DELETE)
     *
     * @return WebDriver_Session
     */
    public function deleteAllCookies()
    {
        $this->curl('DELETE', '/cookie');

        return $this;
    }

    /**
     * Delete a cookie: /session/:sessionId/cookie/:name (DELETE)
     *
     * @param string $cookieName
     *
     * @return WebDriver_Session
     */
    public function deleteCookie($cookieName)
    {
        $this->curl('DELETE', '/cookie/' . $cookieName);

        return $this;
    }

    /**
     * window methods: /session/:sessionId/window (POST, DELETE)
     * - $session->window() - close current window
     * - $session->window($name) - set focus
     * - $session->window($window_handle)->method() - chaining
     *
     * @return WebDriver_Base
     */
    public function window()
    {
        // close current window
        if (func_num_args() == 0) {
            $this->curl('DELETE', '/window');

            return $this;
        }

        // set focus
        $arg = func_get_arg(0); // window handle or name attribute
        if (is_array($arg)) {
            $this->curl('POST', '/window', $arg);

            return $this;
        }

        // chaining
        return new WebDriver_Window($this->url . '/window', $arg);
    }

    /**
     * Delete window: /session/:sessionId/window (DELETE)
     *
     * @return WebDriver_Session
     */
    public function deleteWindow()
    {
        $this->curl('DELETE', '/window');

        return $this;
    }

    /**
     * Set focus to window: /session/:sessionId/window (POST)
     *
     * @param mixed $name window handler or name attribute
     *
     * @return WebDriver_Session
     */
    public function focusWindow($name)
    {
        $this->curl('POST', '/window', array('name' => $name));

        return $this;
    }

    /**
     * timeouts methods: /session/:sessionId/timeouts (POST)
     * - $session->timesouts($json) - set timeout for an operation
     * - $session->timeouts()->method() - chaining
     *
     * @return WebDriver_Base
     */
    public function timeouts()
    {
        // set timeouts
        if (func_num_args() == 1) {
            $arg = func_get_arg(0); // json
            $this->curl('POST', '/timeouts', $arg);

            return $this;
        }

        if (func_num_args() == 2) {
            $arg = array(
                'type' => func_get_arg(0), // 'script' or 'implicit'
                'ms' => func_get_arg(1),   // timeout in milliseconds
            );
            $this->curl('POST', '/timeouts', $arg);

            return $this;
        }

        // chaining
        return new WebDriver_Timeouts($this->url . '/timeouts');
    }

    /**
     * ime method chaining, e.g.,
     * - $session->ime()->method()
     *
     * @return WebDriver_Ime
     */
    public function ime()
    {
        return new WebDriver_Ime($this->url . '/ime');
    }

    /**
     * Get active element (i.e., has focus): /session/:sessionId/element/active (POST)
     * - $session->activeElement()
     *
     * @return mixed
     */
    public function activeElement()
    {
        $results = $this->curl('POST', '/element/active');

        return $this->webDriverElement($results['value']);
    }

    /**
     * touch method chaining, e.g.,
     * - $session->touch()->method()
     *
     * @return WebDriver_Touch
     */
    public function touch()
    {
        return new WebDriver_Touch($this->url . '/touch');
    }

    /**
     * local_storage method chaining, e.g.,
     * - $session->local_storage()->method()
     *
     * @return WebDriver_Storage_Local
     */
    public function local_storage()
    {
        return WebDriver_Storage::factory('local', $this->url . '/local_storage');
    }

    /**
     * session_storage method chaining, e.g.,
     * - $session->session_storage()->method()
     *
     * @return WebDriver_Storage_Session
     */
    public function session_storage()
    {
        return WebDriver_Storage::factory('session', $this->url . '/session_storage');
    }

    /**
     * {@inheritdoc}
     */
    protected function getElementPath($elementId)
    {
        return sprintf('%s/element/%s', $this->url, $elementId);
    }
}
