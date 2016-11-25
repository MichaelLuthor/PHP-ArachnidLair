<?php
trait TraitSpiderJavaScript {
    /** @return string */
    public function runJS ( $string ) {
        $v8js = new V8Js();
        return $v8js->executeString($string);
    }
    
    
    public function parseCallback( $callbackName, $string ) {
        
    }
}