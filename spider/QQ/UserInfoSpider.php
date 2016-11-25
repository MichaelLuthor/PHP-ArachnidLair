<?php
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
class UserInfoSpider extends AbstractSpirder {
    /** Uses */
    use TraitSpiderWebDriver,
        TraitSpiderJavaScript;
    
    /** @var integer */
    private $gtk = null;
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::init()
     */
    protected function init() {
        parent::init();
        
        $this->say('Login QQ');
        $host = 'http://127.0.0.1:4444/wd/hub';
        /* @var $main \Facebook\WebDriver\Remote\RemoteWebDriver */
        $main = RemoteWebDriver::create($host, DesiredCapabilities::chrome());
        $main->get('http://qzone.qq.com/');
        /* @var $loginFrame \Facebook\WebDriver\WebDriver */
        $loginFrame = $main->switchTo()->frame('login_frame');
        $loginFrame->findElement(WebDriverBy::id('switcher_plogin'))->click();
        $loginFrame->findElement(WebDriverBy::id('u'))->sendKeys('568109749');
        $loginFrame->findElement(WebDriverBy::id('p'))->sendKeys('michael1215');
        $loginFrame->findElement(WebDriverBy::id('login_button'))->click();
        sleep(20);
        $cookieValues = $this->getCookieValuesFromWebDriver($main);
        $this->setCookiesByWebDriver($main);
        $main->close();
        
        $this->gtk = $this->runJS("(function (skey) {
            var hash = 5381;
            for (var i = 0, len = skey.length;i < len;++i) {
                hash += (hash << 5) + skey.charAt(i).charCodeAt();
            }
            return hash & 2147483647;
        })('{$cookieValues['p_skey']}');");
        
        $this->say('gtk = %s', $this->gtk);
        $this->uid = 50000;
    }
    
    /** @var integer */
    private $uid = null;
    
    /** @var integer */
    private $maxUid = 60000;
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::onAllTaskFinished()
     */
    protected function onAllTaskFinished() {
        for ($i=0; $i<10; $i++ ) {
            $url = new URL('https://h5.qzone.qq.com/proxy/domain/base.qzone.qq.com/cgi-bin/user/cgi_userinfo_get_all');
            $url->setParams(array(
                'uin' => $this->uid,
                'vuin' => '568109749',
                'fupdate' => '1',
                'rd' => '0.21662222829593025',
                'g_tk' => $this->gtk,
            ));
            $this->addTask($url);
            $this->uid ++;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::onTaskFinished()
     */
    protected function onTaskFinished($task, $response) {
        $responseContent = $response->getBody()->getContents();
        echo $responseContent;
    }
}