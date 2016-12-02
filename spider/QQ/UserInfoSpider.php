<?php
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
class UserInfoSpider extends AbstractSpirder {
    /** Uses */
    use TraitSpiderWebDriver,
        TraitSpiderJavaScript,
        TraitSpiderStorageDB;
    
    /** 屏蔽时间的秒数 */
    const BLOCK_TIME = 3600;
        
    /** @var array */
    private $accounts = array(
        '568109749' => array('password'=>'michael1215'),
        '2971307115' => array('password'=>'ginhappy@1215'),
    );
    
    /** @var string */
    private $activeQQ = null;
    
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
        
        $this->loginQQ();
    }
    
    /** @var integer */
    private $uid = null;
    
    /** @var integer */
    private $maxUid = 1000000;
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::generateTasks()
     */
    protected function generateTasks($isInit) {
        for ($i=0; $i<1000; $i++ ) {
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
        if ( !$isInit ) {
            $this->timeCounter(10*60);
        }
    }
    
    /**
     * {@inheritDoc}
     * @see AbstractSpirder::beforeTaskStarted()
     */
    protected function beforeTaskStarted(&$task) {
        $task['url']->set('g_tk', $this->gtk);
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
        if ( '-99997' == $responseContent['code'] ) { # 频率过于频繁
            $this->addTask($task);
            $this->accounts[$this->activeQQ]['is_blocking'] = true;
            $this->accounts[$this->activeQQ]['blocked_time'] = time();
            $this->loginQQ();
            return;
        }
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
    
    /**
     * @return void
     */
    private function loginQQ() {
        $account = $this->getAvailableQQ();
        
        $this->say('Login QQ : %s', $account['uid']);
        $host = 'http://127.0.0.1:4444/wd/hub';
        /* @var $main \Facebook\WebDriver\Remote\RemoteWebDriver */
        $main = RemoteWebDriver::create($host, DesiredCapabilities::chrome());
        $main->get('http://qzone.qq.com/');
        /* @var $loginFrame \Facebook\WebDriver\WebDriver */
        $loginFrame = $main->switchTo()->frame('login_frame');
        $loginFrame->findElement(WebDriverBy::id('switcher_plogin'))->click();
        $loginFrame->findElement(WebDriverBy::id('u'))->sendKeys($account['uid']);
        $loginFrame->findElement(WebDriverBy::id('p'))->sendKeys($account['password']);
        $loginFrame->findElement(WebDriverBy::id('login_button'))->click();
        $this->timeCounter(20);
        $cookieValues = $this->getCookieValuesFromWebDriver($main);
        $this->setCookiesByWebDriver($main);
        $main->close();
        
        if ( !isset($cookieValues['p_skey']) ) {
            $this->say('Retry login QQ...');
            return $this->loginQQ();
        }
        
        $this->gtk = $this->runJS("(function (skey) {
            var hash = 5381;
            for (var i = 0, len = skey.length;i < len;++i) {
            hash += (hash << 5) + skey.charAt(i).charCodeAt();
        }
            return hash & 2147483647;
        })('{$cookieValues['p_skey']}');");
        
        $this->say('gtk = %s', $this->gtk);
    }
    
    /**
     * @return array
     */
    private function getAvailableQQ() {
        $accountUid = null;
        $password = null;
        
        $waitTime = 0;
        $now = time();
        foreach ( $this->accounts as $account => &$accountInfo ) {
            if ( !isset($accountInfo['is_blocking']) ) {
                $accountInfo['is_blocking'] = false;
                $accountInfo['blocked_time'] = null;
            }
            if ( $accountInfo['is_blocking'] ) {
                if ( $now > $accountInfo['blocked_time']+self::BLOCK_TIME ) {
                    $accountInfo['is_blocking'] = false;
                    $accountInfo['blocked_time'] = null;
                } else {
                    $newWaitTime = $accountInfo['blocked_time'] + self::BLOCK_TIME - $now; 
                    if ( 0===$waitTime ) {
                        $waitTime = $newWaitTime;
                    }
                    if ( $newWaitTime < $waitTime ) {
                        $waitTime = $newWaitTime;
                    }
                }
            }
            if ( !$accountInfo['is_blocking'] && null===$accountUid ) {
                $accountUid = $account;
                $password = $accountInfo['password'];
            }
        }
        if ( null === $accountUid ) {
            $this->timeCounter($waitTime);
            return $this->getAvailableQQ();
        }
        
        $this->activeQQ = $accountUid;
        return array('uid'=>$accountUid, 'password'=>$password);
    }
}