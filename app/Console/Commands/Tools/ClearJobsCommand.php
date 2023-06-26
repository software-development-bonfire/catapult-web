<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ClearJobsCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clear:jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all of the jobs from the specified queue';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $connection = $this->argument('connection')
                        ?: $this->laravel['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queueName = $this->getQueue($connection);

        $queue = $this->laravel['queue']->connection($connection);

        // We need to validate first if the queue is clearable instance,
        // if not then proceed the other way to clear jobs by queue name
        if ($queue instanceof ClearableQueue) {
            $count = $queue->clear($queueName);

            $this->info(sprintf(__('message.clearing_count_jobs'), $count, Str::plural('job', $count), $queueName));
        } else {
            try {
                $count = $this->clear($queueName);

                $this->info(sprintf(__('message.clearing_count_jobs'), $count, Str::plural('job', $count), $queueName));
            } catch (\Exception $e) {
                $this->error(sprintf(__('message.clearing_is_not_supported'), (new ReflectionClass($queue))->getShortName()));
            }
        }

        // We need to restart the broadcasting queue
        $this->call('queue:restart');

        return 0;
    }


    /**
     * Delete all of the jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear($queue)
    {
        return DB::table('jobs')->where('queue', $this->getQueue($queue))->delete();
    }

    /**
     * Get the queue name to clear.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     *  Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, __('info.the_name_of_queue_connection')],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, __('info.the_name_of_queue')],

            ['force', null, InputOption::VALUE_NONE, __('info.force_operation_in_production')],
        ];
    }
}