<?php

declare(strict_types=1);

use RdKafka\Admin\Client;
use RdKafka\Admin\NewTopic;
use RdKafka\Conf;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$options = getopt('t:p::r::b::w::');
if (empty($options)) {
    echo sprintf(
        'Usage: %s -t{topicname} [-p{numberOfPartitions:1}] [-r{replicationFactor:1} [-b{brokerList:kafka:9092} [-w{waitForResultMs:10000}]' . PHP_EOL,
        basename(__FILE__)
    );
    exit();
}

$conf = new Conf();
$conf->set('bootstrap.servers', $options['b'] ?? getenv('KAFKA_BROKERS') ?: 'kafka:9092');
$client = Client::fromConf($conf);
$client->setWaitForResultEventTimeout((int) $options['w'] ?? 10000);
$partitions = $options['p'] ?? 1;
$replicationFactor = $options['r'] ?? 1;

$results = $client->createTopics(
    [
        new NewTopic(
            (string) $options['t'],
            (int) $partitions,
            (int) $replicationFactor
        ),
    ]
);

foreach ($results as $result) {
    if ($result->error === RD_KAFKA_RESP_ERR_NO_ERROR) {
        echo sprintf(
            'Created topic %s with %s partitions and replication factor %s.',
            $result->name,
            $partitions,
            $replicationFactor
        );
    } else {
        echo sprintf(
            'Failed to created topic %s with %s partitions and replication factor %s. Reason: %s (%s)',
            $result->name,
            $partitions,
            $replicationFactor,
            $result->errorString,
            $result->error
        );
    }
    echo PHP_EOL;
}
