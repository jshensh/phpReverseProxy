PHP 反向代理
============

基于 [https://github.com/jshensh/php-curl-class](https://github.com/jshensh/php-curl-class) 的一个应用

## 需要修改的配置文件

### nginx.conf

请替换 Line 5 的域名以及 Line 7 的实际路径

### index.php

请替换 Line 4 的源站访问协议、Line 5 的源站域名以及 Line 6 的当前站点域名，Line 63 起的输出内容替换数组视情况修改

## 功能
- [X] 转发用户的 GET 请求
- [X] 转发用户的 POST 请求
- [X] 转发用户的 PUT 请求
- [X] 转发用户的 DELETE 请求
- [X] 转发用户发送的所有 Header（除 Accept-Encoding 和 Host）
- [X] 转发服务器返回的所有 Header（除 Content-Length 和 Content-Encoding）
- [X] 替换服务器返回的内容

## 搭建反代（基于 lnmp）

```shell
#!/bin/sh

originProtocol="http"
originSite="baidu.com"
thisSite="example.com"
# 以上三行需要修改
cd /home/wwwroot
git clone https://github.com/jshensh/phpReverseProxy ${thisSite}
rm -rf ${thisSite}/.git/
cd ${thisSite}
sed -i "s/example.com/${thisSite}/g" ./nginx.conf
mv ./nginx.conf /usr/local/nginx/conf/vhost/${thisSite}.conf
sed -i "s/http/${originProtocol}/g" ./index.php
sed -i "s/example.com/${thisSite}/g" ./index.php
sed -i "s/baidu.com/${originSite}/g" ./index.php
composer require jshensh/php-curl-class
chown -R www:www ./*
lnmp nginx reload
```
