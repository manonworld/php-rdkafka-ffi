<?php

declare(strict_types=1);

namespace RdKafka;

use PHPUnit\Framework\TestCase;
use RdKafka\FFI\Api;

/**
 * @covers \RdKafka\Queue
 * @covers \RdKafka\Event
 */
class QueueTest extends TestCase
{
    public function testConsumeViaQueue(): void
    {
        $producer = new Producer();
        $producer->addBrokers(KAFKA_BROKERS);
        $producerTopic = $producer->newTopic(KAFKA_TEST_TOPIC);
        $producerTopic->produce(0, 0, __METHOD__);
        $producer->flush((int) KAFKA_TEST_TIMEOUT_MS);

        $consumer = new Consumer();
        $consumer->addBrokers(KAFKA_BROKERS);

        $queue = $consumer->newQueue();

        $consumerTopic = $consumer->newTopic(KAFKA_TEST_TOPIC);
        $consumerTopic->consumeQueueStart(0, rd_kafka_offset_tail(1), $queue);

        $message = $queue->consume((int) KAFKA_TEST_TIMEOUT_MS);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame(__METHOD__, $message->payload);
    }

    /**
     * @group ffiOnly
     */
    public function testPoll(): void
    {
        $conf = new Conf();
        $conf->set('log.queue', 'true');  // route log events to main queue
        $conf->set('debug', 'consumer');
        $conf->set('log_level', (string) LOG_DEBUG);

        $consumer = new Consumer($conf);

        $mainQueue = new Queue(Api::getFFI()->rd_kafka_queue_get_main($consumer->getCData()));

        $event = $mainQueue->poll((int) KAFKA_TEST_TIMEOUT_MS);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(4 /* RD_KAFKA_EVENT_LOG */, $event->type());
        $this->assertSame('Log', $event->name());
        $this->assertSame(0 /* RD_KAFKA_RESP_ERR_NO_ERROR */, $event->error());
        $this->assertSame('Success', $event->errorString());
        $this->assertFalse($event->errorIsFatal());
    }
}
