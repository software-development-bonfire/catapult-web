<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\DataMapping;
use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Entities\FieldMappingList;
use App\Enums\Acronym;
use App\Enums\ApiEndpoint;
use App\Enums\Directory;
use App\Enums\Disk;
use App\Enums\FileNameIdentifier;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Exports\PosToCdisExport;
use App\Http\Requests\PosToCdisValidation;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Traits\GenericHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\CatapultToJsonFormatService;
use App\Services\SyncDatabaseService;
use Illuminate\Support\Facades\Lang;
use stdClass;

class ConvertDataFile extends Command
{
    use GenericHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:convert-data-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert csv files to json format';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CatapultToJsonFormatService $catapultToJsonFormatService, SyncDatabaseService $syncDatabaseService)
    {
        parent::__construct();
        $this->catapultToJsonFormatService = $catapultToJsonFormatService;
        $this->syncDatabaseService = $syncDatabaseService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line("Conversion started..");
        $this->line('');

        $filters = (object) array('type' => MappingType::CDIS_TO_POS);
        $syncEntries = app()->make(SyncEntryRepository::class)->list($filters);

        foreach ($syncEntries as $syncEntry) {
            $this->syncEntries[$syncEntry->name] = $syncEntry->alias;
        }

        while (true) {
            $entries = [
                'transaction',
                'zread',
                'audit_trail',
                'cash_breakdown',
                'cash_drawer',
            ];

            foreach ($entries as $entry) {
                $remoteDiskName = '';
                $localDiskName = '';

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['remoteSetup', 'dataMappings']);

                $entryLabel = '('.$entry.') ';

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->warn('No field mapping details. Please contact administrator. '.$entryLabel);
                    continue;
                }

                $remoteSetup = $fieldMappingDetails->remoteSetup;
                $dataMappings = $fieldMappingDetails->dataMappings;

                $entryLabel .= '('.StorageType::getDescription($remoteSetup->storage_type).')';

                if ($remoteSetup->storage_type == StorageType::FTP) {
                    $remoteDiskName = 'pos_ftp_remote_sync_data_file';
                    $localDiskName = 'pos_ftp_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $remoteSetup->host);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $remoteSetup->username);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $remoteSetup->password);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $remoteSetup->port);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $remoteSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $remoteSetup->local_path);
                } else if ($remoteSetup->storage_type == StorageType::LOCAL_NETWORK) {
                    $remoteDiskName = 'pos_local_remote_sync_data_file';
                    $localDiskName = 'pos_local_local_sync_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $remoteSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $remoteSetup->local_path);
                } else {
                    $error = [
                        'endpoint' => 'N/A',
                        'filename' => 'N/A',
                        'status' => Lang::get('error.failed_conversion'),
                        'sheet' => 'N/A',
                        'error_type' => 'Configuration error',
                        'description' => 'No remote setup configuration'
                    ];

                    $errorExist = ErrorLogDetail::where([
                        'error_type' => $error['error_type'],
                        'description' => $error['description']
                    ])->first();

                    if (! $errorExist) {
                        $this->errorLogService->store($error);
                    }
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);
                $sourcePath = $entryFolderName.'/To Convert';
                $directories = $localDisk->allDirectories($sourcePath);

                foreach ($directories as $directory) {
                    $this->info('Converting ('.$directory.')');
                    $fileCount = substr($directory, -1);
                    $folderName = substr($directory, strrpos($directory, '/') + 1);
                    $files = $localDisk->allFiles($directory);
                    $entriesData = (object) array();

                    foreach ($files as $file) {
                        $filename = substr($file, strrpos($file, '/') + 1);
                        $entryFileAcronym = explode('_', $filename)[0];
                        $contents = Excel::toArray(new PosToCdisExport, $file, $localDiskName);

                        foreach ($contents as $index => $content) {
                            $keys = $content[0];
                            unset($content[0]);

                            $content = array_values($content);

                            if (! isset($entriesData->{$entryFileAcronym})) {
                                $entriesData->{$entryFileAcronym} = array();
                            }

                            foreach ($content as $dataIndex => $data) {
                                foreach ($data as $datumIndex => $datum) {
                                    if (! isset($entriesData->{$entryFileAcronym}[$dataIndex])) {
                                        $entriesData->{$entryFileAcronym}[$dataIndex] = (object) array();
                                    }
                                    $entriesData->{$entryFileAcronym}[$dataIndex]->{$keys[$datumIndex]} = $datum;
                                }
                            }
                        }
                    }

                    $entryHierarchy = [];
                    $levels = [];
                    foreach ($dataMappings as $dataMapping) {
                        $field = explode('.*.', $dataMapping->field);

                        if (! isset($entryHierarchy[$dataMapping->file_name])) {
                            unset($field[count($field) - 1]);

                            $dotNotationPattern = implode('.*.', $field);
                            $dotNotationPattern =
                                $dotNotationPattern == ''
                                    ? $entry
                                    : $entry.'.*.'.$dotNotationPattern;
                            $keyPattern = explode('.*.', $dotNotationPattern);
                            $keyName = $keyPattern[count($keyPattern) - 1];
                            $level = count($field);
                            $levels[$dataMapping->file_name] = count($field);
                            $entryHierarchy[$dataMapping->file_name] = array(
                                'level' => $level,
                                'dot_notation_pattern' => $dotNotationPattern,
                                'key' => $keyName

                            );
                        }
                    }
                    array_multisort($levels, SORT_ASC, $entryHierarchy);

                    $entryContent = [];
                    $hierarchyReferences = [];

                    foreach ($entryHierarchy as $entryAcronym => $entryDetail) {
                        $mappings = $dataMappings->where('file_name', $entryAcronym)->toArray();
                        if (isset($entriesData->{$entryAcronym})) {
                            $entryData = $entriesData->{$entryAcronym};

                            foreach ($mappings as $mapping) {
                                if (strpos($mapping['field'], '.*.') !== false) {
                                    $objectName = explode('.*.', $mapping['field']);
                                    $objectName = $objectName[count($objectName) - 2];
                                } else {
                                    $objectName = $entry;
                                }

                                $headReferenceEntry = '';
                                $headReferenceEntryAcronym = '';
                                $headReferenceEntryFieldName = '';
                                $referenceValue = '';
                                $headReferenceIndex = '';

                                foreach ($entryData as $entryDatumIndex => $entryDatum) {
                                    if (! array_key_exists($entryAcronym, $hierarchyReferences)) {
                                        if ($mapping['head_reference']) {
                                            $referenceValue = $mapping['reference_column_name'] ? $entryDatum->{$mapping['reference_column_name']} : '';
                                            $headReferenceEntry = explode('.', $mapping['head_reference']);
                                            $headReferenceEntryAcronym = $headReferenceEntry[0];
                                            $headReferenceEntryFieldName = $headReferenceEntry[1];
                                            $headReferenceIndex = array_search($referenceValue, array_column($entriesData->{$headReferenceEntryAcronym}, $headReferenceEntryFieldName));
                                        }

                                        $hierarchyReferences[$entryAcronym][$entryDatumIndex] = array(
                                            'index' => $entryDatumIndex,
                                            'level' => $entryDetail['level'],
                                            'entry_acronym' => $entryAcronym,
                                            'reference_key_name' => $entryDetail['key'],
                                            'reference_column' => $mapping['reference_column_name'],
                                            'reference_value' => $referenceValue,
                                            'head_reference_entry_acronym' => $headReferenceEntryAcronym,
                                            'head_reference_entry_index' => (int) $headReferenceIndex,
                                        );
                                    }

                                    $entryDatum = (array) $entryDatum;

                                    if ($mapping['required']) {
                                        $fieldValue = $entryDatum[$mapping['column_name']];
                                    } else {
                                        $fieldValue = $mapping['default_value'];
                                    }

                                    if (! isset($entryContent[$objectName][$entryDatumIndex])) {
                                        $entryContent[$objectName][$entryDatumIndex] = (object) array();
                                    }

                                    $entryContent[$objectName][$entryDatumIndex]->{$mapping['column_name']} = $fieldValue;
                                }
                            }
                        }
                    }

                    $this->convertToFile($hierarchyReferences, $entryContent);
                }

                $this->info(
                    $directories
                        ? 'Sync processing... '.$entryLabel
                        : 'No file to be sync '.$entryLabel);

                foreach ($directories as $directory) {
                    $this->info('Converting (' . $directory . ')');
                }
            }

            sleep(3);
        }
    }

    public function convertToFile($hierarchyReferences, $entryContent)
    {
        $fileContent = array('transaction' => []);

        foreach ($hierarchyReferences as $entryAcronym => $hierarchyReference) {
            foreach ($hierarchyReference as $reference) {
                $keyName = $reference['reference_key_name'];
                foreach ($entryContent[$keyName] as $index => $data) {
                    $path = $keyName;
                    $this->setValue($hierarchyReferences, $reference, $fileContent, $data, $path);
                }
            }
        }

        var_dump($fileContent); die();
    }

    public function setValue($hierarchyReference, $reference, $fileContent, $data, $path)
    {
        $headReferenceEntryAcronym = $reference['head_reference_entry_acronym'];
        $referenceValue = $reference['reference_value'];
        $keyName = $reference['reference_key_name'];
        $headReferenceEntryIndex = $reference['head_reference_entry_index'];


        if ($reference['level'] == 0) {
            Arr::set($fileContent, $path, Arr::flatten($data));
            var_dump($fileContent);
        } else {
            if ($referenceValue && $headReferenceEntryAcronym) {
                $path .= $keyName.'.'.$headReferenceEntryIndex;

                $this->setValue(
                    $hierarchyReference,
                    $hierarchyReference[$headReferenceEntryAcronym][$headReferenceEntryIndex],
                    $fileContent,
                    $data,
                    $path);
            }
        }
    }

    /**
     * Get sync entry alias.
     *
     * @param  string  $name
     */
    public function getSyncEntryAlias($name = null)
    {
        $entries = $this->syncEntries;

        return is_null($name) ? $entries : $entries[$name];
    }
}
