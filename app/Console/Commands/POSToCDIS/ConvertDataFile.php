<?php

namespace App\Console\Commands\POSToCDIS;

use App\Entities\ErrorLog;
use App\Entities\ErrorLogDetail;
use App\Enums\ErrorStatus;
use App\Enums\MappingType;
use App\Enums\Status;
use App\Enums\StorageType;
use App\Enums\UsageType;
use App\Exports\PosToCdisExport;
use App\Repositories\Contracts\FieldMappingRepository;
use App\Repositories\Contracts\SyncEntryRepository;
use App\Services\CDIS\v2\CashBreakdownService;
use App\Services\CDIS\v2\TerminalTransactionService;
use App\Services\CDIS\v2\ZReadService;
use App\Services\CDIS\v2\POSAuditTrailService;
use App\Services\CDIS\v2\CashDrawerService;
use App\Traits\ErrorLogTrait;
use App\Traits\GenericHelper;
use App\Traits\StorageTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ConvertDataFile extends Command
{
    use GenericHelper, StorageTrait, ErrorLogTrait;

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

        $entries = [
            'transaction' => TerminalTransactionService::class,
            'zread' => ZReadService::class,
            'audit_trail' => POSAuditTrailService::class,
            'cash_breakdown' => CashBreakdownService::class,
            'cash_drawer' => CashDrawerService::class,
        ];

        foreach (array_keys($entries) as $entry) {
            Cache::forget('data_mappings_and_file_storage_setup_'. $entry);
        }

        while (true) {
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

                $fieldMappingDetails = Cache::remember('data_mappings_and_file_storage_setup_'.$entry, 60*60, function () use($filters) {
                    return app()
                        ->make(FieldMappingRepository::class)
                        ->list($filters, false, ['fileStorageSetup', 'dataMappings']);
                });

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

                $selectedDisk = $this->intializeDisk($fileStorageSetup, \App\Enums\StorageCommandSelection::CONVERT);

                if (isset($selectedDisk) && is_array($selectedDisk)) {
                    $remoteDiskName = $selectedDisk['remoteDiskName'];
                    $localDiskName = $selectedDisk['localDiskName'];
                } else {
                    return false;
                }

                $entryFolderName = Str::title(str_replace('_', ' ', $entry));

                $localDisk = Storage::disk($localDiskName);
                $sourcePath = $entryFolderName.'/To convert';

                $failedConversionFolderPathErrors = '/'.$entryFolderName.'/Failed conversion/Errors';
              
                app()['config']->set('logging.channels.bonfire.path',  $failedConversionFolderPathErrors);

                $directories = $localDisk->allDirectories($sourcePath);

                $this->doCleanup($localDisk, '/'.$entryFolderName.'/Processed', $entryLogLabel);

                if (! $directories) {
                    $this->createLog(__('message.no_data_to_convert_to_value', ['value' => $this->extension]), 'info', true, [$entryLogLabel]);
                }

                foreach ($directories as $directory) {
                    $folderFileCount = $localDisk->allFiles($directory);
                    $expectedFileCount = substr($directory, -1);
                    $folderName = substr($directory, strrpos($directory, '/') + 1);
                   
                    if (count($folderFileCount) != $expectedFileCount) {
                        $this->setErrorLog(
                            $entryLogLabel,
                            $folderName,
                            $directory,
                            ErrorStatus::CONVERSION_ERROR,
                            '',
                            'Mismatched file count',
                            count($folderFileCount).' files found but '.intval($expectedFileCount).' expected file count inside this folder '.$folderName
                        );
                    }

                    $this->createLog(__('label.converting'). ' :', 'info', true, [$entryLogLabel], [$folderName]);

                    $files = $localDisk->allFiles($directory);
                    $entriesData = (object) array();
                    $fileCsvCollection = array();

                    foreach ($files as $file) {
                        $filename = substr($file, strrpos($file, '/') + 1);

                        try {
                            $entryFileAcronym = explode('_', $filename)[0];
                            $contents = Excel::toArray(new PosToCdisExport, $file, $localDiskName);
                            $fileCsvCollection[$entryFileAcronym] = $filename;

                            foreach ($contents as $index => $content) {
                                $keys = $content[0];

                                $keys = array_map('trim', $keys);

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
                    $levels = []; // expected output should be {"TH":0,"TD":1,"TDD":2,"PM":2,"PR":2,"AD":3,"PD":3}
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
                            $filename = $fileCsvCollection[$entryAcronym];

                            foreach ($mappings as $mapping) {
                                if (strpos($mapping['field'], '.*.') !== false) {
                                    $objectName = explode('.*.', $mapping['field']);
                                    $objectName = $objectName[count($objectName) - 2];
                                } else {
                                    $objectName = $entry;
                                }

                                foreach ($entryData as $entryDatumIndex => $entryDatum) {
                                    $headReferenceEntryAcronym = '';
                                    $referenceValue = '';
                                    $headReferenceIndex = '';

                                    if ($mapping['head_reference']) {
                                        $referenceValue =
                                            $mapping['reference_column_name']
                                                ? $entryDatum->{$mapping['reference_column_name']}
                                                : '';

                                        $headReferenceEntry = explode('.', $mapping['head_reference']);
                                        $headReferenceEntryAcronym = $headReferenceEntry[0];
                                        $headReferenceEntryFieldName = $headReferenceEntry[1];

                                        $discountAddon = false;
                                        try {
                                            if 
                                            (trim($objectName.'.'.$mapping['column_name']) === 'discount.usage_type' 
                                            && (isset($entryDatum->{$mapping['column_name']}) 
                                            && ($entryDatum->{$mapping['column_name']} === UsageType::ADDON 
                                            || $entryDatum->{$mapping['column_name']} === UsageType::BUNDLE))
                                            ) {
                                                $headReferenceEntryAcronym = 'AD';
                                                $headReferenceEntryFieldName = 'id';
    
                                                $discountAddon = true;
                                            }
                                        } catch(Exception $exception) {
                                            $mappingErrors[$entryAcronym][] = array(
                                                'error_type' => 'Reference error',
                                                'description' => $mapping['column_name'].' not found in CSV file',                                                
                                                'meta' => [$mapping['file_name'].'.'.$mapping['reference_column_name'],  $objectName.'.'.$mapping['column_name'], $folderName],
                                                'filename' => $filename,
                                            );
                                            continue;
                                        }
                                        

                                        $headReferenceIndex =
                                            array_search(
                                                $referenceValue,
                                                array_column($entriesData->{$headReferenceEntryAcronym},
                                                    $headReferenceEntryFieldName));

                                        if ($headReferenceIndex === false && $discountAddon) {
                                            $mappingErrors[$entryAcronym][] = array(
                                                'error_type' => 'Reference error',
                                                'description' => $mapping['head_reference'].' with a value of '.$referenceValue.' not found.',
                                                'meta' => [$mapping['file_name'].'.'.$mapping['reference_column_name'],  $objectName.'.'.$mapping['column_name'], $folderName],
                                                'filename' => $filename,
                                            );
                                        }
                                    }

                                    $hierarchyReferences[$entryAcronym][$entryDatumIndex] = array(
                                        'index' => $entryDatumIndex,
                                        'level' => $entryDetail['level'],
                                        'entry_acronym' => $entryAcronym,
                                        'reference_key_name' => $entryDetail['key'],
                                        'reference_column' => $mapping['reference_column_name'],
                                        'id' =>  $entryDatum->id,
                                        'reference_value' => $referenceValue,
                                        'head_reference_entry_acronym' => $headReferenceEntryAcronym,
                                        'head_reference_entry_index' => $headReferenceIndex,
                                    );

                                    $entryDatum = (array) $entryDatum;

                                    if ($mapping['required']) {
                                        $isFieldExists = array_key_exists($mapping['column_name'], $entryDatum);

                                        if ($isFieldExists) {
                                            $fieldValue = $entryDatum[$mapping['column_name']];

                                            if (is_null($fieldValue) || $fieldValue === '') {
                                                $defaultValue = $mapping['default_value'];

                                                if ((is_null($defaultValue) || $defaultValue === '') && ! $mapping['nullable']) {
                                                    $mappingErrors[$entryAcronym][] = array(
                                                        'error_type' => 'No value was set even the default value. This is required.',
                                                        'description' => $mapping['column_name'],
                                                        'meta' => ['Row: '. ($entryDatumIndex + 2)],
                                                        'filename' => $filename,
                                                    );
                                                } else {
                                                    $fieldValue = $mapping['default_value'];
                                                }
                                            }
                                        } else if ($mapping['nullable']) {
                                            $fieldValue = NULL;
                                        } else {
                                            $mappingErrors[$entryAcronym][] = array(
                                                'error_type' => 'Column not found',
                                                'description' => $mapping['column_name'],
                                                'meta' => ['Row: '. ($entryDatumIndex + 2)],
                                                'filename' => $filename,
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

                    if (! $mappingErrors) {
                        try{
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
                        } catch (\Exception $exception) {
                            $failedConversionFolderPath = '/'.$entryFolderName.'/Failed conversion/'.$folderName;
                            $errorMessage = $exception->getMessage().' in '.$exception->getFile().' at line '.$exception->getLine();
                            $this->createLog(
                                $errorMessage,
                                'error',
                                true,
                                [$entryLogLabel]
                            );
                            $this->setErrorLog(
                                $entryLogLabel, 
                                $folderName, 
                                $directory, 
                                ErrorStatus::CONVERSION_ERROR,
                                '', 
                                'Failed conversion',
                                $errorMessage
                            );
                            $this->moveTransactionFolder($localDisk, $failedConversionFolderPath, $directory, $entryLogLabel);
                        }

                    } else {
                        $failedConversionFolderPath = '/'.$entryFolderName.'/Failed conversion/'.$folderName;

                        $this->createLog(
                            __('error.conversion_failed'),
                            'error',
                            true,
                            [$entryLogLabel],
                            [$folderName]
                        );

                        foreach ($mappingErrors as $key => $mappingError) {
                            $this->createLog(
                                '   -> at '. $key,
                                'error',
                                false
                            );

                            foreach ($mappingError as $error) {
                                $this->createLog(
                                    '       • ['.$error['error_type'].'] '.$error['description'],
                                    'error',
                                    false,
                                    [],
                                    $error['meta']
                                );

                                $this->setErrorLog($entryLogLabel, $error['filename'], $directory, ErrorStatus::CONVERSION_ERROR, $key, $error['error_type'], $error['description']);
                            }
                        }
                        $this->moveTransactionFolder($localDisk, $failedConversionFolderPath, $directory, $entryLogLabel);

                        $this->createErrorLogFile($localDisk, $failedConversionFolderPathErrors, ErrorStatus::CONVERSION_ERROR);
                    }
                }
            }

            sleep(3);
        }
    }

    private function moveTransactionFolder($localDisk, $failedConversionFolderPath, $targetDirectory, $entryLogLabel)
    {
        try {
            if ($localDisk->exists($failedConversionFolderPath)) {
                $localDisk->deleteDirectory($failedConversionFolderPath);
            } else {
                $localDisk->move($targetDirectory, $failedConversionFolderPath);
            }
        } catch (\Exception $exception) {
            $this->createLog(
                $exception->getMessage().' in '.$exception->getFile().' at line '.$exception->getLine(),
                'error',
                true,
                [$entryLogLabel]
            );
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
                $index = $reference['index'];

                $data = $entryContent[$keyName][$index];

                if ($reference['head_reference_entry_acronym'] == '' && $reference['head_reference_entry_index'] == '') {
                    $headReference = $reference;
                } else {
                    $headReference = $hierarchyReferences[$reference['entry_acronym']][$reference['index']];
                }

                Arr::set($data, 'headReference', $headReference); //Added to include some additional information during debugging
                Arr::set($data, 'index', $index); //Added to include current index during debugging
              
                $path = array();
                $fileContent = $this->setValue($hierarchyReferences, $headReference, $fileContent, $data, $path, $index);
            }
        }

        $filePath = '/'.$entryFolderName.'/Converted/To sync/'.$fileName.'.'.$this->extension;
        $processedFolderPath = '/'.$entryFolderName.'/Processed/'.$fileName;
        $failedConversionFolderPath = '/'.$entryFolderName.'/Failed conversion/'.$fileName;

        try {
            if (! is_null($serviceClass)) {
                $fileContent = app()->make($serviceClass)->store($fileContent);
            }

            $fileContent = json_encode($fileContent);

            $isMoved = $disk->put($filePath, $fileContent);

            if ($isMoved) {
                if ($disk->exists($processedFolderPath)) {
                    $disk->deleteDirectory($processedFolderPath);
                } else {
                    $disk->move($directory, $processedFolderPath);
                }

                $this->createLog(__('label.converted').'  :', 'info', true, [$entryLogLabel], [$fileName]);
            }
        } catch(\Throwable $exception) {
            $this->createLog(
                $exception->getMessage().' in '.$exception->getFile(). ' at line '. $exception->getLine(),
                'error',
                true,
                [$entryLogLabel],
                [$fileName, 'Failed conversion']
            );

            $this->setErrorLog(
                $entryLogLabel, 
                $fileName, 
                $directory, 
                ErrorStatus::CONVERSION_ERROR,
                '', 
                'Failed conversion',
                $exception->getMessage().' in '.$exception->getFile().' at line '.$exception->getLine()
            );

            if ($disk->exists($failedConversionFolderPath)) {
                $disk->deleteDirectory($failedConversionFolderPath);
            } else {
                $disk->move($directory, $failedConversionFolderPath);
            }

            return false;
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

            $pathArray = explode('.', $actualPath);
             
            $addonKeyPath = array();
            for ($index = 0; $index < count($pathArray) - 2; $index++) {
                $addonKeyPath[] = $pathArray[$index];		
            }

            $lastKeyPath = $pathArray[count($pathArray) - 2];

            unset($pathArray[count($pathArray) - 1]);
            $pathArrayDotNotation = implode('.', $pathArray);
           
            $objectContent = Arr::get($fileContent, $pathArrayDotNotation);
            
            if (is_null($objectContent)) {
                $finalPath = $pathArrayDotNotation.'.0';
            } else {
                $index = count((array) $objectContent);
                $finalPath = $pathArrayDotNotation.'.'.$index;
            }

            if ($lastKeyPath === 'discount') {
                $finalPath = $this->resolveAddonDiscountKeyIndexPath($fileContent, $data, $addonKeyPath, $lastKeyPath, $finalPath);               
            }

            Arr::set($fileContent, $finalPath, $data);

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

    private function getFieldValue($mapping, $entryAcronym, $entryDatumIndex, $entryDatum)
    {
        $entryDatum = (array) $entryDatum;
        
        if ($mapping['required']) {
            $isFieldExists = array_key_exists($mapping['column_name'], $entryDatum);

            if ($isFieldExists) {
                $fieldValue = $entryDatum[$mapping['column_name']];

                if (is_null($fieldValue) || $fieldValue === '') {
                    $defaultValue = $mapping['default_value'];

                    if ((is_null($defaultValue) || $defaultValue === '') && ! $mapping['nullable']) {
                        $mappingErrors[$entryAcronym][] = array(
                            'error_type' => 'No value was set even the default value. This is required.',
                            'description' => $mapping['column_name'],
                            'meta' => ['Row: '.($entryDatumIndex + 2)]
                        );
                    } else {
                        $fieldValue = $mapping['default_value'];
                    }
                }
            } else if ($mapping['nullable']) {
                $fieldValue = NULL;
            } else {
                $mappingErrors[$entryAcronym][] = array(
                    'error_type' => 'Column not found',
                    'description' => $mapping['column_name'],
                    'meta' => ['Row: '. ($entryDatumIndex + 2)]
                );
                $fieldValue = '####BREAK####';
                //break;
            }
        } else {
            $fieldValue = $mapping['default_value'];
        }

        return $fieldValue;
    }

    private function resolveAddonDiscountKeyIndexPath($fileContent, $data, $addonKeyPath, $lastKeyPath, $finalPath){
       
        $addonKeyPathDotNotation = implode('.', $addonKeyPath);

        $referenceValue = Arr::get($data,'headReference.reference_value');
        $referenceAcronym = Arr::get($data,'headReference.head_reference_entry_acronym');

        $resolvedAddonDiscountKeyPath = $finalPath;
        if ($referenceAcronym === 'AD') {
            $addonDiscountKeyPath = Str::substr($addonKeyPathDotNotation, 0, -2);
            $dataArrayAddon =  Arr::get($fileContent, $addonDiscountKeyPath);

            if (isset($dataArrayAddon)) {
                $addonIndex = array_search( $referenceValue, array_column($dataArrayAddon,'id'));

                $finalPathAddonDiscount = $addonDiscountKeyPath.'.'.$addonIndex.'.'.$lastKeyPath;

                $objectContent = Arr::get($fileContent, $finalPathAddonDiscount);

                if (is_null($objectContent)) {
                    $resolvedAddonDiscountKeyPath = $finalPathAddonDiscount.'.0';
                } else {
                    $index = count((array) $objectContent);
                    $resolvedAddonDiscountKeyPath = $finalPathAddonDiscount.'.'.$index;
                }
            }
        }
        return $resolvedAddonDiscountKeyPath;
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

    public function doCleanup($localDisk, $processedFolderPath, $entryLogLabel)
    {
        $fileCleanup = config('filesystems.file_cleanup');
        if ($fileCleanup) {
            $directoryCount = $this->cleanupDirectories($localDisk, $processedFolderPath);
            if ($directoryCount) {
                $this->createLog(__('message.directory_cleaned_up', ['value' => $directoryCount]), 'info', true, [$entryLogLabel]);
            }
        }
    }
}
