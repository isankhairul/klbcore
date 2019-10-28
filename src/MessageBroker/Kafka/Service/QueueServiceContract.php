<?php
declare( strict_types=1 );

namespace Klb\Core\MessageBroker\Kafka\Service;

use Enqueue\RdKafka\RdKafkaContext;
use Phalcon\Config;

/**
 * Interface QueueServiceContract
 *
 * @package App\Services\Contract
 * @see     link(https://php-enqueue.github.io/transport/kafka/)
 *
 * @todo
 *
 *
 * // connects to localhost:9092
 * $connectionFactory = new RdKafkaConnectionFactory();
 *
 * // same as above
 * $connectionFactory = new RdKafkaConnectionFactory('kafka:');
 *
 * // same as above
 * $connectionFactory = new RdKafkaConnectionFactory([]);
 *
 * // connect to Kafka broker at example.com:1000 plus custom options
 * $connectionFactory = new RdKafkaConnectionFactory([
 *     'global' => [
 *         'group.id' => uniqid('', true),
 *         'metadata.broker.list' => 'example.com:1000',
 *         'enable.auto.commit' => 'false',
 *     ],
 *     'topic' => [
 *        'auto.offset.reset' => 'beginning',
 *     ],
 * ]);
 *
 */
interface QueueServiceContract
{

    /**
     * QueueService constructor.
     *
     * @param Config $config
     */
    public function __construct( Config $config );

    /**
     * @param string $topic
     * @param        $data
     * @param array  $headers
     * @param array  $properties
     *
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\Exception\InvalidDestinationException
     * @throws \Interop\Queue\Exception\InvalidMessageException
     */
    public function publisher( string $topic, $data, array $properties = [], array $headers = [] ): void;

    /**
     * @param string   $topic
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws \Exception
     */
    public function consumer( string $topic, callable $callback, int $timeout = 0 ): void;

    /**
     * @return RdKafkaContext
     */
    public function getContext(): RdKafkaContext;

    /**
     * Close
     */
    public function close(): void;

}
