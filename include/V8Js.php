<?php
if ( !class_exists('V8Js') ) {
    class V8Js {
        const V8_VERSION = '1.1.1';
        const FLAG_NONE = 1 ;
        const FLAG_FORCE_ARRAY = 2 ;
        public function __construct ( $object_name="PHP", $variables=array(), $extensions=array(), $report_uncaught_exceptions=true) {}
        public function executeString ( $script, $identifier="V8Js::executeString()", $flags=V8Js::FLAG_NONE) {}
        public static function getExtensions ( ) {}
        public function getPendingException ( ) {}
        public static function registerExtension ( $extension_name, $script, $dependencies=array(), $auto_enable=false) {}
    }
}