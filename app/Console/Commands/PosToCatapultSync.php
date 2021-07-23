<?php

namespace App\Console\Commands;

use App\Entities\RemoteSetup;
use App\Entities\SyncFileReference;
use App\Enums\Directory;
use App\Enums\Disk;
use App\Enums\Status;
use App\Enums\UserType;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class PosToCatapultSync extends Command implements ShouldQueue
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:PosToCatapultSync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync files from POS FTP to Catapult Local';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while (true) {
            $this->line('Syncing started..');
            $remote_setup = RemoteSetup::where('status', Status::ACTIVE)->first();
        
            if ($remote_setup) {
                resolve('filesystem')->forgetDisk(Disk::FTP_POST_TO_CDIS);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.host', $remote_setup->host);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.username', $remote_setup->username);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.password', $remote_setup->password);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.port', $remote_setup->port);
                app()['config']->set('filesystems.disks.'.Disk::FTP_POST_TO_CDIS.'.root', $remote_setup->path);
            }
            
            $endpoints = [
                [
                    'name' => 'Transaction',
                    'root' => $remote_setup->path.Directory::FTP_TRANSACTION_TO_FETCH,
                    'source_path' => Directory::FTP_TRANSACTION_TO_FETCH,
                    'move_to' => Directory::FTP_TRANSACTION_FETCHED,
                    'local_path' => Directory::FOR_CONVERSION_TRANSACTION_TO_CONVERT
                ],
                [
                    'name' => 'Zread',
                    'root' => $remote_setup->path.Directory::FTP_ZREAD_TO_FETCH,
                    'source_path' => Directory::FTP_ZREAD_TO_FETCH,
                    'move_to' => Directory::FTP_ZREAD_FETCHED,
                    'local_path' => Directory::FOR_CONVERSION_ZREAD_TO_CONVERT
                ],
                [
                    'name' => 'Audit Trail',
                    'root' => $remote_setup->path.Directory::FTP_AUDIT_TRAIL_TO_FETCH,
                    'source_path' => Directory::FTP_AUDIT_TRAIL_TO_FETCH,
                    'move_to' => Directory::FTP_AUDIT_TRAIL_FETCHED,
                    'local_path' => Directory::FOR_CONVERSION_AUDIT_TRAIL_TO_CONVERT
                ],
                [
                    'name' => 'Cash Breakdown',
                    'root' => $remote_setup->path.Directory::FTP_CASH_BREAKDOWN_TO_FETCH,
                    'source_path' => Directory::FTP_CASH_BREAKDOWN_TO_FETCH,
                    'move_to' => Directory::FTP_CASH_BREAKDOWN_FETCHED,
                    'local_path' => Directory::FOR_CONVERSION_CASH_BREAKDOWN_TO_CONVERT
                ],
                [
                    'name' => 'Cash Drawer',
                    'root' => $remote_setup->path.Directory::FTP_CASH_DRAWER_TO_FETCH,
                    'source_path' => Directory::FTP_CASH_DRAWER_TO_FETCH,
                    'move_to' => Directory::FTP_CASH_DRAWER_FETCHED,
                    'local_path' => Directory::FOR_CONVERSION_CASH_DRAWER_TO_CONVERT
                ],
            ];
            
            $disk = Storage::disk(Disk::FTP_POST_TO_CDIS);
    
            foreach ($endpoints as $endpoint) {
                $directories = $disk->allDirectories($endpoint['source_path']);
    
                foreach ($directories as $directory) {
                    $file_count = substr($directory, -1);
    
                    $files = $disk->allFiles($directory);
    
                    if (count($files) == $file_count) {
                        $return = $this->Collection($files, $disk, $endpoint, $remote_setup);
                    }

                    if ($return) {
                        $local = Storage::disk(Disk::LOCAL_POS_TO_CDIS)->allFiles($return);
                        if (count($files) == count($local)) {
                            $disk->move($endpoint['source_path'].'/'.$return, $endpoint['move_to'].'/'.$return);
                        }
                    }
                }
            }

            if ($directories) {
                $this->info('Sync successful');
            } else {
                $this->info('No file to be sync');
            }
            sleep(10);
        }
    }

    /**
     * download, transfer and store file reference resource
     *
     * @param  array  $files
     * @param  object  $disk
     * @param  array  $endpoint
     * @param  object  $remote_setup
     * 
     */
    public function Collection($files, $disk, $endpoint, $remote_setup)
    {
        try {
            foreach($files as $file) {
                $extension = substr($file, strrpos($file, '.') + 1);

                $filename = substr($file, strrpos($file, '/') + 1);

                $path = substr($file, 0, strrpos($file, '/'));

                $folder_name = substr($path, strrpos($path, '/') + 1);

                if ($extension == 'csv' || $extension == 'xlsx' || $extension == 'xls') {

                    $savedFileLocally = $this->saveToLocal($filename, $disk, $endpoint, $path, $folder_name);
                    $exists = SyncFileReference::where('filename', $filename)->first();

                    if ($savedFileLocally && ! $exists) {

                        $dataReference = SyncFileReference::create([
                            'filename' => $filename,
                            'extension' => $extension,
                            'ftp_path' => $remote_setup->path.$endpoint['move_to'].'/'.$folder_name,
                            'last_modified' => Carbon::parse($disk->lastModified($file))->format('Y-m-d h:m:s'),
                        ]);
                    }
                }
            }
            return $folder_name;
        } catch (\Throwable $th) {
           return ['error1' => $th->getMessage()];
       }
    }

    /**
     * move FTP file from "To fetch" to "Fetch"
     *
     * @param  string  $filename
     * @param  string  $path
     * @param  string  $folder_name
     * @param  array  $endpoint
     * 
     */
    public function saveToLocal($filename, $disk, $endpoint, $path, $folder_name)
    {
        try {
            resolve('filesystem')->forgetDisk(Disk::LOCAL_POS_TO_CDIS);
            app()['config']->set('filesystems.disks.'.Disk::LOCAL_POS_TO_CDIS.'.root', public_path($endpoint['local_path']));

            if(Storage::disk(Disk::LOCAL_POS_TO_CDIS)->exists($filename)) {
                $copyToLocal = false;
            } else {
                $copyToLocal = Storage::disk(Disk::LOCAL_POS_TO_CDIS)->put($folder_name.'/'.$filename, $disk->get($path.'/'.$filename));
            }

            if ($copyToLocal) {
                return true;
            } else {
                Storage::disk(Disk::LOCAL_POS_TO_CDIS)->delete($filename);
                return false;
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    /**
     * move FTP file from "To fetch" to "Fetch"
     *
     * @param  string  $folder_name
     * @param  string  $filename
     * @param  array  $endpoint
     * 
     */
    public function moveFileToFetched($folder_name, $filename, $endpoint, $disk)
    {
        try {
            $directory = substr($endpoint['move_to'], 1).'/'.$folder_name;

            $old_file = $endpoint['source_path'].'/'.$folder_name.'/'.$filename;
            
            $new_file = $directory.'/'.$filename;
            
            $allDirectories = $disk->allDirectories($endpoint['move_to']);

            $folder_exist = in_array($directory, $allDirectories);
            
            if (! $folder_exist) {
                $disk->makeDirectory($directory);
            }

            $disk->move($old_file, $new_file);

            return true;
        } catch (\Throwable $th) {
            return $th;
        }
    }
}
