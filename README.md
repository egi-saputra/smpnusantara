root@localhost:/var/www/simstal.smknusantara.id# cat /etc/nginx/sites-enabled/simstal.smknusantara.id

# HTTP → HTTPS

server {

listen 80;

listen [::]:80;

server_name simstal.smknusantara.id;

return 301 https://simstal.smknusantara.id$request_uri;

}

# MAIN LARAVEL

server {

listen 443 ssl http2;

listen [::]:443 ssl http2;

server_name simstal.smknusantara.id;

    root /var/www/simstal.smknusantara.id/html/public;

    index index.php index.html index.htm;

    client_max_body_size 100M;

    ssl_certificate /etc/letsencrypt/live/simstal.smknusantara.id/fullchain.pem;

    ssl_certificate_key /etc/letsencrypt/live/simstal.smknusantara.id/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;

    add_header X-Content-Type-Options "nosniff" always;

    charset utf-8;

# Laravel

    location / {

        try_files $uri $uri/ /index.php?$query_string;

    }

    location ~ \.php$ {

        include snippets/fastcgi-php.conf;

        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        fastcgi_hide_header X-Powered-By;

    }

    location ~ /\.(?!well-known).* {

        deny all;

    }

    # Storage

    location /storage {

        alias /var/www/simstal.smknusantara.id/html/storage/app/public;

        access_log off;

        expires 30d;

    }

    # Static files

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {

        expires 30d;

        access_log off;

        add_header Cache-Control "public, no-transform";

    }

    # Favicon & robots

    location = /favicon.ico { access_log off; log_not_found off; }

    location = /robots.txt  { access_log off; log_not_found off; }

}

root@localhost:/var/www/simstal.smknusantara.id# nginx -t

systemctl reload nginx

curl -I https://simstal.smknusantara.id/phpmyadmin

nginx: the configuration file /etc/nginx/nginx.conf syntax is ok

nginx: configuration file /etc/nginx/nginx.conf test is successful

HTTP/2 401

date: Wed, 01 Jul 2026 07:33:05 GMT

content-type: text/html; charset=utf-8

server: cloudflare

www-authenticate: Basic realm="Restricted"

x-frame-options: SAMEORIGIN

x-content-type-options: nosniff

cf-cache-status: DYNAMIC

speculation-rules: "/cdn-cgi/speculation"

report-to: {"group":"cf-nel","max_age":604800,"endpoints":[{"url":"https://a.nel.cloudflare.com/report/v4?s=QMnTrN5RQ2yeI0IROfNpb9UZtqzqPFm9bvq6K35lVpXm%2BQtBsaetwXJe5Or86xF%2BsLoYH9gHbcS56i58EGLmf8t1Vzw0kuFkf0z9Z0Xb%2BXBofkQTr9dxugtS3AgeIaLPRRE2c6bnd40xWt2uh8bpIqWzT9GRQw%3D%3D"}]}

nel: {"report_to":"cf-nel","success_fraction":0.0,"max_age":604800}

cf-ray: a143b873bd67ee16-CGK

alt-svc: h3=":443"; ma=86400

root@localhost:/var/www/simstal.smknusantara.id#
