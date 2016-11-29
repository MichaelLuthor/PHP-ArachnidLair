<?php 
require_once 'include/autoloader.php';
require_once 'library/medoo/medoo.php';
require_once 'library/guzzle/autoloader.php';
require_once 'library/php-webdriver/autoloader.php';
ArachnidLair::setup()->loadSpider('QQ/UserInfoSpider')->foraging();