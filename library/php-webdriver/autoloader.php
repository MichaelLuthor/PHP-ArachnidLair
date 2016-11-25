<?php
function facebook_webdriver_autoloader( $class ) {
    $baseDir = dirname(__FILE__);
    $class = str_replace('Facebook\\WebDriver\\', '', $class);
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = $baseDir.DIRECTORY_SEPARATOR.$class.'.php';
    if ( file_exists($file) ) {
        require_once $file;
    }
}
spl_autoload_register('facebook_webdriver_autoloader');