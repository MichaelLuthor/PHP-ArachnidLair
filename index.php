<?php 
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

require_once 'include/autoloader.php';
require_once 'library/php-webdriver/autoloader.php';
echo "Login QQ\n";
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
$cookies = $main->manage()->getCookies();
$main->close();

$cookieValues = array();
foreach ( $cookies as $index => $cookie ) {
    $cookieValues[$cookie['name']] = $cookie['value'];
}

$js = "(function (skey) {
var hash = 5381;
for (var i = 0, len = skey.length;i < len;++i) {
hash += (hash << 5) + skey.charAt(i).charCodeAt();
}
return hash & 2147483647;
})('{$cookieValues['p_skey']}');";
$v8js = new V8Js();
$gtk = $v8js->executeString($js);

echo "g_tk = {$gtk}\n";
require_once 'library/guzzle/autoloader.php';
$db = new PDO('sqlite:E:/qq-user-inf-raw.db');
$maxUid = $db->query('SELECT MAX(uid) FROM user_info');
$defaultMaxUid = 50000;
if ( false === $maxUid ) {
    $db->exec('CREATE TABLE "user_info" ("id"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "uid"  INTEGER, "content"  TEXT)');
    $maxUid = $defaultMaxUid;
} else {
    $maxUid = $maxUid->fetch(PDO::FETCH_ASSOC);
    $maxUid = (null===$maxUid['MAX(uid)']) ? $defaultMaxUid : $maxUid['MAX(uid)'];
}
$insert = $db->prepare('INSERT INTO user_info (uid,content) VALUES (:uid,:content)');
$systemMaxUid = 1000000;

$cookieJar = new CookieJar();
foreach ( $cookies as $cookie ) {
    $setCookie = new SetCookie();
    $setCookie->setPath($cookie['path']);
    $setCookie->setDomain($cookie['domain']);
    $setCookie->setName($cookie['name']);
    $setCookie->setHttpOnly($cookie['httpOnly']);
    $setCookie->setSecure($cookie['secure']);
    $setCookie->setValue($cookie['value']);
    $cookieJar->setCookie($setCookie);
}
for ( $uid=$maxUid; $uid<60000; $uid++ ) {
    $url = new URL('https://h5.qzone.qq.com/proxy/domain/base.qzone.qq.com/cgi-bin/user/cgi_userinfo_get_all');
    $url->setParams(array(
        'uin' => $uid,
        'vuin' => '568109749',
        'fupdate' => '1',
        'rd' => '0.21662222829593025',
        'g_tk' => $gtk,
    ));
    
    try {
        $client = new Client();
        $response = $client->get($url->toString(), array(
            'cookies'=>$cookieJar,
            'verify' => false,
        ));
        $responseContent = $response->getBody()->getContents();
    } catch ( \GuzzleHttp\Exception\ServerException $e ) {
        echo mb_convert_encoding("{$uid} 重试\n", "GBK", "UTF-8");
        sleep(1);
        $uid --;
        continue;
    }
    
    $responseContent = json_decode(substr($responseContent, 10, -2), true);
    $insert->execute(array(':uid'=>$uid, ':content'=>json_encode($responseContent)));
    echo mb_convert_encoding("{$uid} {$responseContent['message']}\n", "GBK", "UTF-8");
}
echo "DONE\n";