# Kollus Upload Proxy Application

Kollus Upload Proxy Application for Some Company.

## Requirement

* php 5.5 above
  - composer

## Install the Application

Unzip source after in which you want to install Application.

```
wget https://github.com/yupmin-ct/kollus-upload-proxy/archive/0.0.X.tar.gz
tar xvfz kollus-upload-proxy-0.0.X.tar.gz
cd kollus-upload-proxy
```

Install composer

```
curl -sS https://getcomposer.org/installer | php
```

Run this command from the directory 

```
php composer.phar update
chmod 777 logs cache
```

And [Setting Web configuration](http://www.slimframework.com/docs/start/web-servers.html).

## Use the Application

Run this command from the directory for Doctrine console After creating config.yml and Setting Databas

```
cp .config.yml config.yml
```

And Edit config.yml

```
nano config.yml
```

And Init database.

```
php vendor/bin/doctrine orm:schema-tool:update --force
```

That's it!

## Api url list

* /{service_account_key}/upload/create_url
* /{service_account_key}/upload/channel_callback
* /{service_account_key}/upload/list

## Setting cronjob at 1 hour

```
php src/console clear-callback-data serviceAccountKey [afterSeconds]
```

## Reset Database tables

```
php vendor/bin/doctrine orm:schema-tool:drop --force
php vendor/bin/doctrine orm:schema-tool:create
```

## Reference

* [Slim + Silly + Doctrine Skeleton](https://github.com/yupmin/slim-silly-doctrine)
* [Slim Skeleton](https://github.com/slimphp/Slim-Skeleton)
* [Silly](http://mnapoli.fr/silly/)
* [Doctrine](http://www.doctrine-project.org/)
