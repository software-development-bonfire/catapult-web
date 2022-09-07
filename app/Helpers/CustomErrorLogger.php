<?php

namespace App\Helpers;

use Carbon\Carbon;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

class CustomErrorLogger
{

    private $logger = null;

    private $filename = "";
    private $errorFolder = null;
    private $channel = "bonfire";
    private $failSafe = true;
    private $includeStackTrace = true;

    public function __construct($channel, $errorFolder, $failSafe = true)
    {
        $this->failSafe = $failSafe;
        $this->channel = $channel;
        $this->errorFolder = $errorFolder ?? storage_path();
        $this->filename = "catapult-".Carbon::now()->format('Y-m-d');
        $this->getChannel();
    }

    private function getChannel()
    {
        $this->logger = new Logger($this->channel);

        if ($this->failSafe) {
            $this->logger->pushHandler(new WhatFailureGroupHandler($this->getHandlers()));
        } else {
            $this->logger->pushHandler(new GroupHandler($this->getHandlers()));
        }
    }

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

    public function logError($method, $file, $content = null, array $context = array(), $hasDate = false)
    {
        $date = $hasDate ? '['.Carbon::now()->format('Y-m-d H:i:s').']' : '';
        $constructedMessage = $date.'['.$method.']['.$file.']: '.$content;
        $this->logger->error($constructedMessage, $context);
    }
}
