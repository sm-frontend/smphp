# SMPHP框架
一个简单可信赖的php框架。

## 为什么选择SMPHP
> 实战开发总结  

数年业务迭代工作的经验总结，具有很强的实战价值，实践证明，本框架能够高性能地、安全地运行上亿pv的互联网业务

> 优雅简约  

SMPHP是高性能、易扩展的，源代码更优雅、更简约、层次更清晰易懂

> 超轻量级  

做到了真正的轻量级，框架的核心系统基于非常轻量灵活的库，安装包仅有1M,和那些需要大量资源的框架完全相反

> 丰富的类库  

优雅的路由，高效的模板，安全性，丰富的类库，都赋予这个轻巧的框架更多能力


## SMPHP特性及特色类库
- 抽象和分离做到极致
- 兼容性一致性，面向接口编程
- 支持容器类管理对象
- 优雅的源码，单测
- 丰富的安全类库
- 特有类库：Tree、Lock、MobileDetect、Lunar等

## 部署说明

假设代码所在目录为/var/www/

### Apache 配置

``` apache
<VirtualHost *:80>
    DocumentRoot /var/www/sm-framework/public
    ServerName  local.smphp.sm.cn
    RewriteEngine On
    RewriteRule ^/.*$  /index.php [L]
</VirtualHost>
```

### Nginx 配置

``` nginx
server {
    listen 80;
    server_name local.smphp.sm.cn;
    root '/var/www/sm-framework/public';
    index index.php index.html index.htm;

    location / {
        rewrite ^\/(.*)$ /index.php last;
    }

    location ~ \.php {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index /index.php;

        include fastcgi_params;
        fastcgi_split_path_info       ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO       $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    
    }

    location ~ /\.ht {
        deny all;
    
    }
}
```

### PHP 内置服务器配置
开启命令行，键入：
``` linux
php -S localhost:8787 -t /var/www/sm-framework/public
```
浏览器输入localhost:8787即可访问页面
