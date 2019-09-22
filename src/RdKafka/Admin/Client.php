<?php
declare(strict_types=1);

namespace RdKafka\Admin;

use Assert\Assert;
use RdKafka;
use RdKafka\Api;
use RdKafka\Conf;
use RdKafka\Event;
use RdKafka\Exception;
use RdKafka\Metadata;
use RdKafka\Producer;
use RdKafka\Queue;
use RdKafka\Topic;

class Client extends Api
{
    private RdKafka $kafka;

    private function __construct(RdKafka $kafka)
    {
        $this->kafka = $kafka;

        parent::__construct();
    }

    public static function fromConf(Conf $conf)
    {
        return new self(new Producer($conf));
    }

    public static function fromConsumer(Consumer $consumer): self
    {
        return new self($consumer);
    }

    public static function fromProducer(Producer $producer): self
    {
        return new self($producer);
    }

    /**
     * @param ConfigResource[] $resources
     * @param AlterConfigsOptions $options
     * @return ConfigResource[]
     */
    public function alterConfigs(array $resources, AlterConfigsOptions $options = null): array
    {
        // rd_kafka_AlterConfigs
    }

    /**
     * @param ConfigResource[] $resources
     * @param DescribeConfigsOptions $options
     * @return ConfigResource[]
     */
    public function describeConfigs(array $resources, DescribeConfigsOptions $options = null): array
    {
        // todo:
        // assert params
        // create queue
        // call rd_kafka_DescribeConfigs
        // wait for result event on queue - blocking!
        // convert result event to result
        // clean up
        // return result
    }

    /**
     * @param NewPartitions[] $partitions
     * @param CreatePartitionsOptions $options
     * @return TopicResult[]
     */
    public function createPartitions(array $partitions, CreatePartitionsOptions $options = null): array
    {
        // rd_kafka_CreatePartitions
        Assert::that($partitions)->notEmpty()->all()->isInstanceOf(NewPartitions::class);

        $queue = new Queue($this->kafka);

        $partitions_ptr = self::$ffi->new('rd_kafka_NewPartitions_t*[' . count($partitions) . ']');
        foreach ($partitions as $i => $partition) {
            $partitions_ptr[$i] = $partition->getCData();
        }

        self::$ffi->rd_kafka_CreatePartitions(
            $this->kafka->getCData(),
            $partitions_ptr,
            count($partitions),
            $options ? $options->getCData() : null,
            $queue->getCData()
        );

        $event = $this->waitForResultEvent($queue, RD_KAFKA_EVENT_CREATEPARTITIONS_RESULT);

        $eventResult = self::$ffi->rd_kafka_event_CreatePartitions_result($event->getCData());

        $size = \FFI::new('size_t');
        $result = self::$ffi->rd_kafka_CreatePartitions_result_topics($eventResult, \FFI::addr($size));

        $topicResult = [];
        for ($i = 0; $i < (int)$size->cdata; $i++) {
            $topicResult[] = new TopicResult($result[$i]);
        }

        return $topicResult;
    }

    /**
     * @param NewTopic[] $topics
     * @param CreateTopicsOptions $options
     * @return TopicResult[]
     * @throws Exception
     */
    public function createTopics(array $topics, CreateTopicsOptions $options = null): array
    {
        Assert::that($topics)->notEmpty()->all()->isInstanceOf(NewTopic::class);

        $queue = new Queue($this->kafka);

        $topics_ptr = self::$ffi->new('rd_kafka_NewTopic_t*[' . count($topics) . ']');
        foreach ($topics as $i => $topic) {
            $topics_ptr[$i] = $topic->getCData();
        }

        self::$ffi->rd_kafka_CreateTopics(
            $this->kafka->getCData(),
            $topics_ptr,
            count($topics),
            $options ? $options->getCData() : null,
            $queue->getCData()
        );

        $event = $this->waitForResultEvent($queue, RD_KAFKA_EVENT_CREATETOPICS_RESULT);

        $eventResult = self::$ffi->rd_kafka_event_CreateTopics_result($event->getCData());

        $size = \FFI::new('size_t');
        $result = self::$ffi->rd_kafka_CreateTopics_result_topics($eventResult, \FFI::addr($size));

        $topicResult = [];
        for ($i = 0; $i < (int)$size->cdata; $i++) {
            $topicResult[] = new TopicResult($result[$i]);
        }

        return $topicResult;
    }

    /**
     * @param DeleteTopic[] $topics
     * @param DeleteTopicsOptions $options
     * @return TopicResult[]
     * @throws Exception
     */
    public function deleteTopics(array $topics, DeleteTopicsOptions $options = null): array
    {
        Assert::that($topics)->notEmpty()->all()->isInstanceOf(DeleteTopic::class);

        $queue = new Queue($this->kafka);

        $topics_ptr = self::$ffi->new('rd_kafka_DeleteTopic_t*[' . count($topics) . ']');
        foreach ($topics as $i => $topic) {
            $topics_ptr[$i] = $topic->getCData();
        }

        self::$ffi->rd_kafka_DeleteTopics(
            $this->kafka->getCData(),
            $topics_ptr,
            count($topics),
            $options ? $options->getCData() : null,
            $queue->getCData()
        );

        $event = $this->waitForResultEvent($queue, RD_KAFKA_EVENT_DELETETOPICS_RESULT);

        $eventResult = self::$ffi->rd_kafka_event_DeleteTopics_result($event->getCData());

        $size = \FFI::new('size_t');
        $result = self::$ffi->rd_kafka_DeleteTopics_result_topics($eventResult, \FFI::addr($size));

        $topicResult = [];
        for ($i = 0; $i < (int)$size->cdata; $i++) {
            $topicResult[] = new TopicResult($result[$i]);
        }

        return $topicResult;
    }

    /**
     * @param bool $all_topics
     * @param Topic $only_topic
     * @param int $timeout_ms
     *
     * @return Metadata
     * @throws Exception
     */
    public function getMetadata(bool $all_topics, ?Topic $only_topic, int $timeout_ms): Metadata
    {
        return $this->kafka->getMetadata($all_topics, $only_topic, $timeout_ms);
    }

    public function newCreateTopicsOptions(): CreateTopicsOptions
    {
        return new CreateTopicsOptions($this->kafka);
    }

    public function newCreatePartitionsOptions(): CreatePartitionsOptions
    {
        return new CreatePartitionsOptions($this->kafka);
    }

    public function newAlterConfigsOptions(): AlterConfigsOptions
    {
        return new AlterConfigsOptions($this->kafka);
    }

    public function newDeleteTopicsOptions(): DeleteTopicsOptions
    {
        return new DeleteTopicsOptions($this->kafka);
    }

    public function newDescribeConfigsOptions(): DescribeConfigsOptions
    {
        return new DescribeConfigsOptions($this->kafka);
    }

    private function waitForResultEvent(Queue $queue, int $eventType): Event
    {
        do {
            $event = $queue->poll(50);
        } while ($event == null);

        if ($event->error() !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new Exception($event->errorString());
        }

        if ($event->type() !== $eventType) {
            throw new Exception(sprintf(
                'Expected %d result event, not %d.',
                $eventType,
                $event->type()
            ));
        }

        return $event;
    }
}
