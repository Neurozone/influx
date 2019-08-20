# InFlux


## Basics

Rewriting of LeedRss reader with routing and twig as template system

## Recommended Version

The recommended and most stable version is none as the reader is still in development

## Installation

Please see 

## Requirements

* A web server. All of the following have been used:
  * nginx

* PHP 7

## Example for Nginx server
~~~~ 
server
{

  listen 80;
  server_name tld;
      return 301 https://$server_name$request_uri;
}

server {
  listen 443 ssl http2;
  listen [::]:443 ssl http2;

  server_name tld;

  root CHANGE_ME;
  ssl_certificate /etc/nginx/ssl/tld.crt;
  ssl_certificate_key /etc/nginx/ssl/tld.key;

  access_log /var/log/nginx/access.tld.log main;
  error_log /var/log/nginx/error.tld.log;

  client_max_body_size 24M;
  client_body_buffer_size 128k;

  client_header_buffer_size 5120k;
  large_client_header_buffers 16 5120k;

  location / {
    index index.php;
    try_files $uri $uri/ /index.php?$args;
  }

  location ~ /\.ht {
    deny all;
  }

  location ~ ^/\.user\.ini {
    deny all;
  }

  location ~ \.php$ {
    try_files $uri =404;
    fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_param REMOTE_ADDR $http_x_forwarded_for;
  }

  # Global restrictions configuration file.
  # Designed to be included in any server {} block.
  location = /favicon.ico {
    log_not_found off;
    access_log off;
  }

  # robots.txt fallback to index.php
  location = /robots.txt {
    allow all;
    try_files $uri $uri/ /index.php?$args @robots;
    access_log off;
    log_not_found off;
  }

  # Deny all attempts to access hidden files such as .htaccess, .htpasswd, .DS_Store (Mac) excepted .well-known directory.
  # Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)
  location ~ /\.(?!well-known\/) {
    deny all;
  }

}
~~~~ 
## Upgrading

## TODO

* Forgotten password change with email link
* API with JWT 

## License

AGPL-3.0-or-later
