<?php
class URL {
    /** @var string */
    private $baseURL = null;
    /** @var array */
    private $params = array();
    
    /** @param string $baseURL */
    public function __construct( $baseURL ) {
        $this->baseURL = $baseURL;
    }
    
    /** 
     * @param array $params 
     * @return URL
     */
    public function setParams( $params ) {
        $this->params = $params;
        return $this;
    }
    
    /**
     * @param string $name
     * @param string $value
     * @return URL
     */
    public function set( $name, $value ) {
        $this->params[$name] = $value;
        return $this;
    }
    
    /** 
     * @param string $name 
     * @return URL
     */
    public function remove($name) {
        unset($this->params[$name]);
        return $this;
    }
    
    /** 
     * @return void 
     * @return URL
     */
    public function clean() {
        $this->params = array();
        return $this;
    }
    
    /**
     * @return string
     */
    public function toString() {
        $url = $this->baseURL;
        $connector = (false===strpos('?', $url)) ? '?' : '&';
        if ( !empty($this->params) ) {
            $param = http_build_query($this->params);
            $url = $url.$connector.$param;
        }
        return $url;
    }
}