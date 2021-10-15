<?php

namespace App\Console\Commands\POSToCDIS;

use App\Enums\MappingType;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Exports\PosToCdisExport;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Services\CDIS\v2\CashBreakdownService;
use App\Services\CDIS\v2\TerminalTransactionService;
use App\Services\CDIS\v2\ZReadService;
use App\Services\CDIS\v2\POSAuditTrailService;
use App\Services\CDIS\v2\CashDrawerService;
use App\Traits\GenericHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ConvertDataFile extends Command
{
    use GenericHelper;

    public $extension = 'json';
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
    protected $description = 'Convert pos data file to different file format';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createLog(__('info.conversion_started'), 'info', true);

        $filters = (object) array('type' => MappingType::CDIS_TO_POS);
        $syncEntries = app()->make(SyncEntryRepository::class)->list($filters);

        foreach ($syncEntries as $syncEntry) {
            $this->syncEntries[$syncEntry->name] = $syncEntry->alias;
        }

        while (true) {
            $entries = [
                'transaction' => TerminalTransactionService::class,
                'zread' => ZReadService::class,
                'audit_trail' => POSAuditTrailService::class,
                'cash_breakdown' => CashBreakdownService::class,
                'cash_drawer' => CashDrawerService::class,
            ];

            $entriesMaxLength = max(array_map('strlen', array_keys($entries)));

            foreach ($entries as $entry => $serviceClass) {
                $spaces = ($entriesMaxLength - strlen($entry)) / 2;
                $entryLogLabel = str_repeat(' ', ceil($spaces)).$entry.str_repeat(' ', floor($spaces));

                $remoteDiskName = '';
                $localDiskName = '';

                $filters = (object) [
                    'data_entry' => $entry,
                    'status' => Status::ACTIVE,
                ];

                $fieldMappingDetails = app()
                    ->make(FieldMappingRepository::class)
                    ->list($filters, false, ['fileStorageSetup', 'dataMappings']);

                if (count($fieldMappingDetails) > 0) {
                    $fieldMappingDetails = $fieldMappingDetails[0];
                } else {
                    $this->createLog(
                        __('error.no_field_mapping_detected'),
                        'error',
                        true,
                        [$entryLogLabel]
                    );
                    continue;
                }

                $fileStorageSetup = $fieldMappingDetails->fileStorageSetup;
                $dataMappings = $fieldMappingDetails->dataMappings;

                if ($fileStorageSetup->storage_type == StorageType::FTP) {
                    $remoteDiskName = 'pos_ftp_remote_convert_data_file';
                    $localDiskName = 'pos_ftp_local_convert_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'ftp');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.host', $fileStorageSetup->host);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.username', $fileStorageSetup->username);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.password', $fileStorageSetup->password);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.port', $fileStorageSetup->port);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
                } else if ($fileStorageSetup->storage_type == StorageType::LOCAL_NETWORK) {
                    $remoteDiskName = 'pos_local_remote_convert_data_file';
                    $localDiskName = 'pos_local_local_convert_data_file';

                    resolve('filesystem')->forgetDisk($remoteDiskName);
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$remoteDiskName.'.root', $fileStorageSetup->remote_path);

                    resolve('filesystem')->forgetDisk($localDiskName);
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.driver', 'local');
                    app()['config']->set('filesystems.disks.'.$localDiskName.'.root', $fileStorageSetup->local_path);
                } else {
                    return false;
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);
                $sourcePath = $entryFolderName.'/To convert';
                $directories = $localDisk->allDirectories($sourcePath);

                if (! $directories) {
                    $this->createLog(__('message.no_data_to_convert_to_value', ['value' => $this->extension]), 'info', true, [$entryLogLabel]);
                }

                foreach ($directories as $directory) {
                    $folderFileCount = $localDisk->allFiles($directory);
                    $expectedFileCount = substr($directory, -1);

                    if ($folderFileCount == $expectedFileCount) {
                        continue;
                    }

                    $folderName = substr($directory, strrpos($directory, '/') + 1);
                    $this->createLog(__('label.converting'). ' :', 'info', true, [$entryLogLabel], [$folderName]);

                    $files = $localDisk->allFiles($directory);
                    $entriesData = (object) array();

                    foreach ($files as $file) {
                        $filename = substr($file, strrpos($file, '/') + 1);

                        try {
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
                                    if (! isset($entriesData->{$entryFileAcronym}[$dataIndex])) {
                                        $entriesData->{$entryFileAcronym}[$dataIndex] = (object) array();
                                    }
                                    foreach ($data as $datumIndex => $datum) {
                                        $entriesData->{$entryFileAcronym}[$dataIndex]->{$keys[$datumIndex]} = $datum;
                                    }
                                }
                            }
                        } catch(\Exception $exception) {
                            $this->createLog($exception->getMessage(), 'error', true, [$entryLogLabel], [$filename]);

                            continue;
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
                    $entryContentForDatabase = [];
                    $hierarchyReferences = [];
                    $mappingErrors = [];

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

                                $headReferenceEntryAcronym = '';
                                $referenceValue = '';
                                $headReferenceIndex = '';

                                foreach ($entryData as $entryDatumIndex => $entryDatum) {
                                    if ($mapping['head_reference']) {
                                        $referenceValue =
                                            $mapping['reference_column_name']
                                                ? $entryDatum->{$mapping['reference_column_name']}
                                                : '';
                                        $headReferenceEntry = explode('.', $mapping['head_reference']);
                                        $headReferenceEntryAcronym = $headReferenceEntry[0];
                                        $headReferenceEntryFieldName = $headReferenceEntry[1];
                                        $headReferenceIndex =
                                            array_search(
                                                $referenceValue,
                                                array_column($entriesData->{$headReferenceEntryAcronym},
                                                    $headReferenceEntryFieldName));
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

                                    $entryDatum = (array) $entryDatum;

                                    if ($mapping['required']) {

                                        $isFieldExists = array_key_exists($mapping['column_name'], $entryDatum);

                                        if ($isFieldExists) {
                                            $fieldValue = $entryDatum[$mapping['column_name']];
                                        } else {
                                            $mappingErrors[$file][] = array(
                                                'error_type' => 'Missing column',
                                                'description' => $mapping['column_name'],
                                            );

                                            break;
                                        }
                                    } else {
                                        $fieldValue = $mapping['default_value'];
                                    }

                                    if (! isset($entryContent[$objectName][$entryDatumIndex])) {
                                        $entryContent[$objectName][$entryDatumIndex] = array();
                                    }

                                    $fieldPath = explode('.*.', $mapping['field']);
                                    $fieldPath = $fieldPath[count($fieldPath) - 1];

                                    Arr::set(
                                        $entryContent,
                                        $objectName.'.'.$entryDatumIndex.'.'.$fieldPath, $fieldValue);

                                    Arr::set(
                                        $entryContentForDatabase,
                                        $entryAcronym.'.'.$entryDatumIndex.'.'.str_replace('.', '_', $fieldPath), $fieldValue);
                                }
                            }
                        }
                    }

                    $this->convertToFile(
                        $hierarchyReferences,
                        $entry,
                        $entryContent,
                        $folderName,
                        $entryFolderName,
                        $directory,
                        $localDisk,
                        $entryLogLabel,
                        $serviceClass);
                }
            }

            sleep(5);
        }
    }

    /**
     * Convert to file.
     *
     * @param  array  $hierarchyReferences
     * @param  string  $entry
     * @param  array  $entryContent
     * @param  string  $fileName
     * @param  string  $entryFolderName
     * @param  string  $directory
     * @param  Filesystem  $disk
     * @param  string  $entryLogLabel
     * @param  mixed  $serviceClass
     *
     * @return bool
     */
    public function convertToFile(
        $hierarchyReferences,
        $entry,
        $entryContent,
        $fileName,
        $entryFolderName,
        $directory,
        $disk,
        $entryLogLabel,
        $serviceClass
    ) {
        $fileContent = array($entry => (object) []);

        foreach ($hierarchyReferences as $entryAcronym => $hierarchyReference) {
            foreach ($hierarchyReference as $reference) {
                $keyName = $reference['reference_key_name'];
                foreach ($entryContent[$keyName] as $dataIndex => $data) {
                    $path = array();
                    $fileContent = $this->setValue($hierarchyReferences, $reference, $fileContent, $data, $path, $dataIndex);
                }
            }
        }

        $filePath = '/'.$entryFolderName.'/Converted/To sync/'.$fileName.'.'.$this->extension;
        $processedFolderPath = '/'.$entryFolderName.'/Processed/'.$fileName;

        try {
            if (! is_null($serviceClass)) {
                $fileContent = app()->make($serviceClass)->store($fileContent);
            }
        } catch(\Exception $exception) {
            $this->createLog($exception->getMessage(), 'error', true, [$entryLogLabel], [$fileName]);

            return;
        }

        $fileContent = json_encode($fileContent);

        $isMoved = $disk->put($filePath, $fileContent);

        if ($isMoved) {
            if ($disk->exists($processedFolderPath)) {
                $disk->deleteDirectory($processedFolderPath);
            } else {
                $disk->move($directory, $processedFolderPath);
            }

            $this->createLog(__('label.converted'). '  :', 'info', true, [$entryLogLabel], [$fileName]);
        }

        return $isMoved;
    }

    /**
     * Set value from array path.
     *
     * @param  array  $hierarchyReferences
     * @param  array  $reference
     * @param  array  $fileContent
     * @param  array  $data
     * @param  array  $path
     * @param  int  $dataIndex
     *
     * @return array
     */
    public function setValue($hierarchyReferences, $reference, $fileContent, $data, $path, $dataIndex)
    {
        $headReferenceEntryAcronym = $reference['head_reference_entry_acronym'];
        $keyName = $reference['reference_key_name'];
        $headReferenceEntryIndex = $reference['head_reference_entry_index'];

        if ($reference['level'] == 0) {
            $actualPath = '';
            $path[$reference['reference_key_name']] = $dataIndex;
            $arrayOfPath = array_reverse($path);
            foreach ($arrayOfPath as $key => $value) {
                if (array_key_first($arrayOfPath) == $key) {
                    $actualPath .= $key.'.'.$value;
                } else {
                    $actualPath .= '.'.$key.'.'.$value;
                }
            }

            Arr::set($fileContent, $actualPath, $data);

            return $fileContent;
        } else {
            $path[$keyName] = $dataIndex;

            return $this->setValue(
                $hierarchyReferences,
                $hierarchyReferences[$headReferenceEntryAcronym][$headReferenceEntryIndex],
                $fileContent,
                $data,
                $path,
                $headReferenceEntryIndex);
        }
    }

    /**
     * Get sync entry alias.
     *
     * @param  string  $name
     * @return string
     */
    public function getSyncEntryAlias($name = null)
    {
        $entries = $this->syncEntries;

        return is_null($name) ? $entries : $entries[$name];
    }
}
