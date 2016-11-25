<?php
use GuzzleHttp\Cookie\SetCookie;
trait TraitSpiderWebDriver {
    /** @return array */
    function getCookieValuesFromWebDriver( $driver ) {
        $cookies = $driver->manage()->getCookies();
        $cookieValues = array();
        foreach ( $cookies as $index => $cookie ) {
            $cookieValues[$cookie['name']] = $cookie['value'];
        }
        return $cookieValues;
    }
    
    /**
     * @param array $cookies
     */
    public function setCookiesByWebDriver( $driver ) {
        $cookies = $driver->manage()->getCookies();
        foreach ( $cookies as $cookie ) {
            $setCookie = new SetCookie();
            $setCookie->setPath($cookie['path']);
            $setCookie->setDomain($cookie['domain']);
            $setCookie->setName($cookie['name']);
            $setCookie->setHttpOnly($cookie['httpOnly']);
            $setCookie->setSecure($cookie['secure']);
            $setCookie->setValue($cookie['value']);
            $this->cookieJar->setCookie($setCookie);
        }
    }
}