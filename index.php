<?php
require('./customCurl.php');

$originProtocol = 'http';
$originSite = 'baidu.com';
$thisSite   = 'example.com';

CustomCurl::setConf('userAgent', $_SERVER['HTTP_USER_AGENT']);

$curlObj0 = CustomCurl::init("{$originProtocol}://{$originSite}{$_SERVER['REQUEST_URI']}", $_SERVER['REQUEST_METHOD']);

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
    $curlObj0 = $curlObj0->set('referer', str_replace($thisSite, $originSite, $_SERVER['HTTP_REFERER']));
}

if (isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE']) {
    $curlObj0 = $curlObj0->setCookies($_SERVER['HTTP_COOKIE']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_array($_POST)) {
        $curlObj0 = $curlObj0->set('postFields', $_POST);
    } else {
        $curlObj0 = $curlObj0->set('postFields', file_get_contents("php://input"));
    }
}

$curlObj0 = $curlObj0->exec();

if ($curlObj0->getStatus()) {
    $headers = explode("\r\n", $curlObj0->getHeader());
    foreach ($headers as $header) {
        if (!$header) {
            continue;
        }
        header($header);
    }

    // 输出关键词替换
    echo str_replace(
        [
            $originSite,
            "document.domain"
        ], [
            $thisSite,
            "'{$originSite}'"
        ], $curlObj0->getBody()
    );

    // 调试信息
    // echo '<pre style="text-align: left;">';
    // var_dump($curlObj0->getHeader(), $curlObj0->getCookies(), $_POST, str_replace($thisSite, $originSite, $_SERVER['HTTP_REFERER']), $_COOKIE);
    // echo '</pre>';
} else {
    var_dump($curlObj0->getCurlErrNo());
}