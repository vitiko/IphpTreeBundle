<?php

$path = $container->getParameter ('kernel.test_env_dir').'/db.sqllite';


$container->loadFromExtension('doctrine', array(
    'dbal' => array(
        'driver' => 'pdo_sqlite',
        'path' => $path,
    ),
));

/*
$container->loadFromExtension('doctrine', array(
    'dbal' => array(
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'name' => 'default',
        'user' => 'root',
        'dbname' => 'iphptree_test'
    ),
));
*/