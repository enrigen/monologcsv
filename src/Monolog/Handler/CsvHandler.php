<?php

namespace Enrigen\Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Stores to a csv file
 *
 * Can be used to store big loads to physical files and import them later into another system that can handle CSV
 *
 */
class CsvHandler extends StreamHandler
{
    const DELIMITER = ',';
    const ENCLOSURE = '\'';
    const ESCAPE_CHAR = '\\';

    /**
     * @inheritdoc
     */
    protected function streamWrite($stream, array $record): void
    {
        if (is_array($record['formatted'])) {
            foreach ($record['formatted'] as $key => $info) {
                if (is_array($info)) {
                    $record['formatted'][$key] = json_encode($info);
                }
            }
        }
        $formatted = (array)$record['formatted'];
        if (version_compare(PHP_VERSION, '5.5.4', '>=') && !defined('HHVM_VERSION')) {
            
            // Split del message per creare più colonne
            $i = 0;

            // Datetime
            $datetime   = $formatted['datetime'];
            $formatted[$i] = $datetime; $i++;
            unset($formatted['datetime']);

            // Log info
            $level      = $formatted['level'];
            $level_name = $formatted['level_name'];
            $channel    = $formatted['channel'];

            unset($formatted['level']);
            unset($formatted['level_name']);
            unset($formatted['channel']);

            $formatted[$i] = $level; $i++;
            $formatted[$i] = $level_name; $i++;
            $formatted[$i] = $channel; $i++;

            // Custom data
            $message = $formatted['message'];
            $message = explode(",", $message);
            
            unset($formatted['message']);
            unset($formatted['context']);
            unset($formatted['extra']);

            foreach($message as $column) {
                $formatted[$i] = $column;
                $i++;
            }

            fputcsv($stream, $formatted, static::DELIMITER, static::ENCLOSURE, static::ESCAPE_CHAR);
            return;
        }
        fputcsv($stream, $formatted, static::DELIMITER, static::ENCLOSURE);
    }

    /**
     * Gets the default formatter.
     *
     * Overwrite this if the LineFormatter is not a good default for your handler.
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter();
    }
}
