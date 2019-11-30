<?php

declare(strict_types=1);

use RdKafka\Admin\Client;
use RdKafka\Admin\NewTopic;
use RdKafka\Conf;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$options = getopt('t:p::r::');
if (empty($options)) {
    echo sprintf(
        "Usage: %s -t{topicname} [-p{numberOfPartitions:1}] [-r{replicationFactor:1}]" . PHP_EOL,
        basename(__FILE__)
    );
    exit();
}

$conf = new Conf();
$conf->set('metadata.broker.list', 'kafka:9092');
$client = Client::fromConf($conf);

$result = $client->createTopics(
    [
        new NewTopic(
            (string)$options['t'],
            ((int)$options['p']) ?: 1,
            ((int)$options['r']) ?: 1
        ),
    ]
);

var_dump($result);
