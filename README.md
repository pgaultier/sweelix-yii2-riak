Sweelix Yii2 Riak Extension
===========================

This extension provides the [Riak 1.4+](http://basho.com/) integration for the Yii2 framework.

Riak is not affiliated with Sweelix.



Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist sweelix/yii2-nosql "*"
```

or add

```
"sweelix/yii2-nosql": "*"
```

to the require section of your composer.json.


General Usage
-------------

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'riak' => [
            'class' => 'sweelix\yii2\nosql\riak\Connection',
            'dsn' => 'riak:dsn=http://localhost:8098',
        ],
    ],
];
```
