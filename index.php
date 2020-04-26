<?php
require('./CustomCurl.php');

$originProtocol = 'http';
$originSite = 'baidu.com';
$thisSite   = 'example.com';

if (!function_exists('getallheaders')) {
    function getallheaders() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$curlObj0 = CustomCurl::init("{$originProtocol}://{$originSite}{$_SERVER['REQUEST_URI']}", $_SERVER['REQUEST_METHOD'])
                ->setCurlOpt(CURLOPT_ENCODING, '');

$headers = getallheaders();

foreach ($headers as $key => $value) {
    $keyArr = ['accept-encoding', 'host', 'referer', 'cookie', 'content-type', 'user-agent', 'content-length'];
    if (in_array(strtolower($key), $keyArr)) {
        continue;
    }
    $curlObj0 = $curlObj0->setHeader($key, str_replace($thisSite, $originSite, $value));
}

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
    $curlObj0 = $curlObj0->set('referer', str_replace($thisSite, $originSite, $_SERVER['HTTP_REFERER']));
}

if (isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE']) {
    $curlObj0 = $curlObj0->setCookies($_SERVER['HTTP_COOKIE']);
}

if ($_SERVER['CONTENT_TYPE']) {
    if (strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
        $curlObj0 = $curlObj0->set('postType', 'json');
    } else if (strpos($_SERVER['CONTENT_TYPE'], 'form') !== false) {
        $curlObj0 = $curlObj0->set('postType', 'form');
    } else {
        $curlObj0 = $curlObj0->set('postType', 'string');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_array($_POST)) {
        $curlObj0 = $curlObj0->set('postFields', $_POST);
    } else {
        $curlObj0 = $curlObj0->set('postFields', file_get_contents("php://input"));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $curlObj0 = $curlObj0->set('postFields', file_get_contents("php://input"));
}

$curlObj0 = $curlObj0->exec();

if ($curlObj0->getStatus()) {
    $headers = explode("\r\n", $curlObj0->getHeader());
    foreach ($headers as $header) {
        if (!$header || strpos(strtolower($header), 'content-length:') > -1 || strpos(strtolower($header), 'content-encoding:') > -1) {
            continue;
        }
        header(str_replace($originSite, $thisSite, $header));
    }

    // 输出关键词替换
    $body = str_replace(
        [
            $originSite
        ], [
            $thisSite
        ], $curlObj0->getBody()
    );

    header('Content-Length: ' . strlen($body));
    echo $body;

    // 调试信息
    // echo '<pre style="text-align: left;">';
    // var_dump($curlObj0->getHeader(), $curlObj0->getCookies(), $_POST, str_replace($thisSite, $originSite, $_SERVER['HTTP_REFERER']), $_COOKIE);
    // echo '</pre>';
} else {
    var_dump($curlObj0->getCurlErrNo());
}
