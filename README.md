PHP 反向代理
============

基于 [https://github.com/jshensh/phpCurlClass](https://github.com/jshensh/phpCurlClass) 的一个应用

## 需要修改的配置文件

### nginx.conf

请替换 Line 5 的域名以及 Line 7 的实际路径

### index.php

请替换 Line 4 的源站访问协议、Line 5 的源站域名以及 Line 6 的当前站点域名，Line 40 起的输出内容替换数组视情况修改

## 功能
- [X] 转发用户的 GET 请求
- [X] 转发用户的 POST 请求
- [] 转发用户的 PUT 请求
- [] 转发用户的 DELETE 请求
- [X] 转发服务器返回的所有 Header
- [X] 替换服务器返回的内容
- [X] 转发用户提交的 Cookies
- [X] 转发用户的 User Agent