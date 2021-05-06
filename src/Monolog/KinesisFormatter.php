<?php

namespace PodPoint\KinesisLogger\Monolog;

use Monolog\Formatter\NormalizerFormatter;

class KinesisFormatter extends NormalizerFormatter
{
    /**
     * The application name.
     *
     * @var string
     */
    private $name;

    /**
     * The application environment.
     *
     * @var string
     */
    private $environment;

    /**
     * KinesisFormatter constructor.
     *
     * @param string $name
     * @param string|null $environment
     */
    public function __construct(string $name, string $environment = null)
    {
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->name = $name;
        $this->environment = $environment ?? app()->environment();
    }

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $record = parent::format($record);

        if (empty($record['datetime'])) {
            $record['datetime'] = gmdate('c');
        }

        $message = [
            'timestamp' => $record['datetime'],
            'host' => gethostname(),
            'project' => $this->name,
            'env' => $this->environment,
            'message' => $record['message'],
            'channel' => $record['channel'],
            'level' => $record['level_name'],
            'extra' => $record['extra'],
            'context' => $record['context'],
        ];

        return [
            'Data' => $this->toJson($message),
            'PartitionKey' => $record['channel']
        ];
    }

    /**
     * Formats a set of log records.
     *
     * @param  array $records A set of records to format
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        $kinesisRecords = [];

        foreach ($records as $record) {
            $kinesisRecords[] = $this->format($record);
        }

        return ['Records' => $kinesisRecords];
    }
}
