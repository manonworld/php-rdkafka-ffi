<?php

use RdKafka\Conf;
use RdKafka\Producer;

/**
 * @Groups({"Producer"})
 */
class ProducerBench
{
    /**
     * @Warmup(10)
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchProduce1Message()
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', 'kafka:9092');
        $producer = new Producer($conf);
        $topic = $producer->newTopic('benchmarks');

        $topic->produce(0, 0, 'bench', 'mark');

        while ($producer->getOutQLen() > 0) {
            $producer->poll(0);
        }
    }

    /**
     * @Warmup(1)
     * @Revs(100)
     * @Iterations(5)
     */
    public function benchProduce100Messages()
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', 'kafka:9092');
        $producer = new Producer($conf);
        $topic = $producer->newTopic('benchmarks');

        for ($i = 0; $i < 100; $i++) {
            $topic->produce(0, 0, 'bench', 'mark');
            $producer->poll(0);
        }

        while ($producer->getOutQLen() > 0) {
            $producer->poll(0);
        }
    }
}