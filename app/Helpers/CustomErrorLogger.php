<?php

namespace App\Helpers;

use Carbon\Carbon;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

/**
 * Helper CustomErrorLogger
 * @package App\Helpers
 */
class CustomErrorLogger
{

    private $logger = null;

    private $filename = "";
    private $errorFolder = null;
    private $channel = "bonfire";
    private $failSafe = true;
    private $includeStackTrace = true;

     /**
     * Constructor
     * 
     * @param string  $channel
     * @param string  $errorFolder
     * @param boolean  $failSafe
     */
    public function __construct($channel, $errorFolder, $failSafe = true)
    {
        $this->failSafe = $failSafe;
        $this->channel = $channel;
        $this->errorFolder = $errorFolder ?? storage_path();
        $this->filename = "catapult-".Carbon::now()->format('Y-m-d');
        $this->getChannel();
    }

    /**
     * Construct and get channel with push handler
     */
    private function getChannel()
    {
        $this->logger = new Logger($this->channel);

        if ($this->failSafe) {
            $this->logger->pushHandler(new WhatFailureGroupHandler($this->getHandlers()));
        } else {
            $this->logger->pushHandler(new GroupHandler($this->getHandlers()));
        }
    }

    /**
     * Get handlers with defined handler and formatter
     */
    private function getHandlers()
    {
        $handlers = [];
        $formatter = new LineFormatter(null, null, true, true);

        if ($this->includeStackTrace) {
            $formatter->includeStacktraces(true);
        }
        $fileHandler = new RotatingFileHandler($this->errorFolder."/{$this->filename}.log",  0, Logger::DEBUG, true, 0666, false);
        $fileHandler->setFormatter($formatter);
        $handlers[] = $fileHandler;

        return $handlers;
    }

    /**
     * Set log with default ERROR level
     * 
     * @param string  $method
     * @param string  $file
     * @param string  $content
     * @param array  $context
     * @param boolean  $hasDate
     */
    public function logError($method, $file, $content = null, array $context = array(), $hasDate = false)
    {
        $date = $hasDate ? '['.Carbon::now()->format('Y-m-d H:i:s').']' : '';
        $constructedMessage = $date.'['.$method.']['.$file.']: '.$content;
        $this->logger->error($constructedMessage, $context);
    }
}
