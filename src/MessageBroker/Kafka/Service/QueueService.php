<?php

declare( strict_types=1 );

namespace Klb\Core\MessageBroker\Kafka\Service;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;
use Phalcon\Config;

/**
 * Class QueueService
 *
 * @package App\Services
 *
 *
 */
class QueueService implements QueueServiceContract
{

    /**
     * @var RdKafkaContext|\Interop\Queue\Context
     */
    private $context;

    /**
     * @inheritDoc
     */
    public function __construct( Config $kafka )
    {

        $commit = $kafka->global->enable_auto_commit ?? 'false';
        if ( is_bool( $commit ) ) {
            if ( $commit === true ) {
                $commit = 'true';
            } else {
                $commit = 'false';
            }
        }

        $connectionFactory = new RdKafkaConnectionFactory( [
            'global'       => [
                'group.id'             => uniqid( '', true ),
                'metadata.broker.list' => $kafka->global->metadata_broker_list ?? 'localhost:9092',
                'enable.auto.commit'   => $commit,
            ],
            'topic'        => [
                'auto.offset.reset' => 'beginning',
            ],
            'dr_msg_cb'    => $kafka->dr_msg_cb ?? null,
            'error_cb'     => $kafka->error_cb ?? null,
            'rebalance_cb' => $kafka->rebalance_cb ?? null,
            //            'partitioner'  => RD_KAFKA_PARTITION_UA,                          // https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka-topicconf.setpartitioner.html
            'log_level'    => $kafka->log_level ?? null,
            'commit_async' => $kafka->commit_async ?? false,
        ] );

        $this->context = $connectionFactory->createContext();
    }

    /**
     * @inheritDoc
     */
    public function publisher( string $topic, $data, array $properties = [], array $headers = []): void
    {
        $message = $this->context->createMessage( is_string($data) ? $data : json_encode($data), $properties, $headers );

        $this->context->createProducer()->send( $this->context->createTopic( $topic ), $message );
    }

    /**
     * @inheritDoc
     */
    public function consumer( string $topic, callable $callback, int $timeout = 0 ): void
    {
        /** @var \Enqueue\RdKafka\RdKafkaContext $context */

        $consumer = $this->context->createConsumer( $this->context->createTopic( $topic ) );

        // Enable async commit to gain better performance (true by default since version 0.9.9).
        //$consumer->setCommitAsync(true);

        $message = $consumer->receive( $timeout );

        try {
            $callback( $message );
            $consumer->acknowledge( $message );
        } catch ( \Exception $exception ) {
            $consumer->reject( $message );
            throw $exception;
        }
    }

    /**
     * @inheritDoc
     */
    public function getContext(): RdKafkaContext
    {
        return $this->context;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->context->close();
    }
}
