<?php
require('./vendor/autoload.php');

use CustomCurl\Client;

$originProtocol = 'http';
$originSite     = 'baidu.com';
$thisSite       = 'example.com';

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

class ReverseProxy
{
    private $flags = [
            'FILENAME_SENT'       => false,
            'HEADER_SENT'         => false,
            'CURL_DOWNLOAD_FILE'  => false,
            'CURL_VALID_RESPONSE' => true
        ];
    private $runtimeData = [
            'headerSize'       => -1,
            'outputBuffer'     => [
                'header' => [],
                'body'   => ''
            ]
        ];
    private $config = [
            'replace' => null,
        ];

    public function curlCallback($ch, $data)
    {
        $info = curl_getinfo($ch);

        if ($this->runtimeData['headerSize'] < $info['header_size']) {
            $this->runtimeData['headerSize'] = $info['header_size'];

            if (!$this->flags['HEADER_SENT']) {
                if (strpos(strtolower($data), 'content-disposition: attachment') !== false) {
                    $this->flags['FILENAME_SENT'] = true;
                    $this->flags['CURL_DOWNLOAD_FILE'] = true;
                }
                if (rtrim($data, "\r\n")) {
                    $this->runtimeData['outputBuffer']['header'][] = rtrim($data, "\r\n");
                }
            }
            // header 结束
            if ($data === "\r\n") {
                $this->runtimeData['headerSize'] += 2;
                if (!$this->flags['HEADER_SENT'] && $this->flags['CURL_DOWNLOAD_FILE']) {
                    foreach ($this->runtimeData['outputBuffer']['header'] as $header) {
                        if (strpos(strtolower($header), 'content-encoding:') !== false) {
                            continue;
                        }
                        if ($this->config['replace']) {
                            header(str_replace($this->config['replace'][0], $this->config['replace'][1], $header), false);
                        } else {
                            header($header, false);
                        }
                    }
                    if (!$this->flags['FILENAME_SENT']) {
                        header('Content-Disposition: attachment; filename=' . basename($this->config['filePath']));
                    }
                    $this->flags['HEADER_SENT'] = true;
                }
            }
        } else {
            // Data 段数据处理
            if ($this->flags['CURL_DOWNLOAD_FILE']) {
                echo $data;
                ob_flush();
                flush();
            } else {
                $this->runtimeData['outputBuffer']['body'] .= $data;
            }
        }

        return strlen($data);
    }

    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);

        $curlObj = Client::init("{$this->config['originProtocol']}://{$this->config['originSite']}{$_SERVER['REQUEST_URI']}", $_SERVER['REQUEST_METHOD'])
            ->setCurlOpt(CURLOPT_ENCODING, '')
            ->setHeader('Expect', '')
            ->set('timeout', 0)
            ->set('reRequest', 1)
            ->set('followLocation', 0);

        $headers = getallheaders();

        foreach ($headers as $key => $value) {
            $keyArr = ['accept-encoding', 'host', 'referer', 'cookie', 'user-agent', 'content-length', 'expect'];
            if (in_array(strtolower($key), $keyArr)) {
                continue;
            }
            $curlObj = $curlObj->setHeader($key, str_replace($this->config['thisSite'], $this->config['originSite'], $value));
        }

        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            $curlObj = $curlObj->set('referer', str_replace($this->config['thisSite'], $this->config['originSite'], $_SERVER['HTTP_REFERER']));
        }

        if (isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE']) {
            $curlObj = $curlObj->setCookies(str_replace($this->config['thisSite'], $this->config['originSite'], $_SERVER['HTTP_COOKIE']));
        }

        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            $curlObj = $curlObj->set('postType', 'string')
                ->set('postFields', file_get_contents("php://input"));
        }

        $res = $curlObj->setCurlOpt(CURLOPT_WRITEFUNCTION, [$this, 'curlCallback'])->exec();

        if ($res->getStatus()) {
            if (!$this->flags['CURL_DOWNLOAD_FILE']) {
                if ($this->config['replace']) {
                    $this->runtimeData['outputBuffer']['body'] = str_replace($this->config['replace'][0], $this->config['replace'][1], $this->runtimeData['outputBuffer']['body']);
                }

                foreach ($this->runtimeData['outputBuffer']['header'] as $header) {
                    if (strpos(strtolower($header), 'content-length:') !== false) {
                        header('Content-Length: ' . strlen($this->runtimeData['outputBuffer']['body']), false);
                        continue;
                    }
                    if (strpos(strtolower($header), 'content-encoding:') !== false) {
                        continue;
                    }
                    if (strpos(strtolower($header), 'transfer-encoding:') !== false) {
                        continue;
                    }
                    if ($this->config['replace']) {
                        header(str_replace($this->config['replace'][0], $this->config['replace'][1], $header), false);
                    } else {
                        header($header, false);
                    }
                }

                echo $this->runtimeData['outputBuffer']['body'];
            }
        } else {
            throw new \Exception('Curl error (' . $res->getCurlErrNo() . '): ' . $res->getCurlErr(), $res->getCurlErrNo());
        }
    }
}

try {
    new ReverseProxy([
        'replace' => [
            [
                $originSite
            ], [
                $thisSite
            ]
        ],
        'originProtocol' => $originProtocol,
        'originSite' =>$originSite,
        'thisSite' => $thisSite
    ]);
} catch (\Exception $e) {
    echo '<h1>Proxy Error</h1><p>' . $e->getMessage() . '</p>';
}
