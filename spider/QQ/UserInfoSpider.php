<?php
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
class UserInfoSpider extends AbstractSpirder {
    /** Uses */
    use TraitSpiderWebDriver,
        TraitSpiderJavaScript,
        TraitSpiderStorageDB;
    
    /** @var integer */
    private $gtk = null;
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::init()
     */
    protected function init() {
        parent::init();
        
        $this->setDBConfig(array(
            'database_type' => 'sqlite',
            'database_file' => 'E:/qq-user-inf-raw.db',
        ));
        $this->uid = $this->max('user_information', 'uid');
        if ( null === $this->uid ) {
            $this->uid = 50000;
        } 
        
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
    }
    
    /** @var integer */
    private $uid = null;
    
    /** @var integer */
    private $maxUid = 100000;
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::generateTasks()
     */
    protected function generateTasks() {
        for ($i=0; $i<10; $i++ ) {
            if ( $this->maxUid < $this->uid  ) {
                break;
            }
            $url = new URL('https://h5.qzone.qq.com/proxy/domain/base.qzone.qq.com/cgi-bin/user/cgi_userinfo_get_all');
            $url->setParams(array(
                'uin' => $this->uid,
                'vuin' => '568109749',
                'fupdate' => '1',
                'rd' => '0.21662222829593025',
                'g_tk' => $this->gtk,
            ));
            $this->addTask($url, array('uid'=>$this->uid));
            $this->uid ++;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::onTaskFinished()
     */
    protected function onTaskFinished($task, $response) {
        $responseContent = $response->getBody()->getContents();
        $responseContent = json_decode(substr($responseContent, 10, -2), true);
        echo mb_convert_encoding("{$task['option']['uid']} {$responseContent['message']}\n", "GBK", "UTF-8");
        
        $record = array(
            'uid' => $task['option']['uid'],
            'response_code' => $responseContent['code'],
            'response_message' => $responseContent['message'],
        );
        if ( isset($responseContent['data']) ) {
            $record = array_merge($record, $responseContent['data']);
            unset($record['uin']);
        }
        foreach ( $record as $key => $value ) {
            if ( is_array($value) ) {
                $record[$key] = json_encode($value);
            }
        }
        
        $this->insert('user_information', $record);
    }
}