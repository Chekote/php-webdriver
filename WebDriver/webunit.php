#!/usr/bin/env php
<?php
/**
 * Copyright 2011-2012 Anthon Pang. All Rights Reserved.
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
 * @author Anthon Pang <anthonp@nationalfibre.net>
 */

/**
 * WebDriver-based web test runner
 */
require_once(dirname(__FILE__) . '/__init__.php');
require_once(dirname(__FILE__) . '/WebTest/Script.php');
require_once(dirname(__FILE__) . '/WebTest.php');

$rc = WebDriver_WebTest::main($argc, $argv);
exit((int) !$rc);
