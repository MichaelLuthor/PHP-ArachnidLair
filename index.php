<?php 
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

require_once 'include/autoloader.php';
require_once 'library/guzzle/autoloader.php';
require_once 'library/php-webdriver/autoloader.php';
ArachnidLair::setup()->loadSpider('QQ/UserInfoSpider')->foraging();

$db = new PDO('sqlite:E:/qq-user-inf-raw.db');
$maxUid = $db->query('SELECT MAX(uid) FROM user_info');
$defaultMaxUid = 50000;
if ( false === $maxUid ) {
    $db->exec('CREATE TABLE "user_info" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "uid"  INTEGER, "content"  TEXT)');
    $maxUid = $defaultMaxUid;
} else {
    $maxUid = $maxUid->fetch(PDO::FETCH_ASSOC);
    $maxUid = (null===$maxUid['MAX(uid)']) ? $defaultMaxUid : $maxUid['MAX(uid)'];
}
$insert = $db->prepare('INSERT INTO user_info (uid,content) VALUES (:uid,:content)');
$systemMaxUid = 1000000;

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