<?php
class ArachnidLair {
    /** @var ArachnidLair */
    private static $arachnidLair = null;
    
    /** @return ArachnidLair */
    public static function setup() {
        if ( null === self::$arachnidLair ) {
            self::$arachnidLair = new ArachnidLair();
        }
        return self::$arachnidLair;
    }
    
    /**
     * @param string $name
     * @return AbstractSpirder
     */
    public function loadSpider($name) {
        $spiderPath = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR
                    .'spider'.DIRECTORY_SEPARATOR
                    .str_replace('/', DIRECTORY_SEPARATOR, $name).'.php';
        if ( !file_exists($spiderPath) ) {
            throw new Exception("Spider {$name} does not exists.");
        }
        require $spiderPath;
        $spirder = explode('/', $name);
        $spirder = end($spirder);
        return new $spirder();
    }
}