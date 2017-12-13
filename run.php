<?php

include_once __DIR__ . '/vendor/autoload.php';

$app = new Symfony\Component\Console\Application;

$app->add(new \Commands\Biglion\GoogleFeedCheck);

$app->run();
