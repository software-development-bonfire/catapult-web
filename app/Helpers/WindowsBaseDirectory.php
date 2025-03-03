<?php

namespace App\Helpers;

class WindowsBaseDirectory
{
    /**
     * @return string|null
     */
    public function getAllUsersProfilePath(): ?string
    {
        return getenv('ALLUSERSPROFILE') ?: null;
    }

    /**
     * @return string|null
     */
    public function getAppDataPath(): ?string
    {
        return getenv('APPDATA') ?: null;
    }

    /**
     * @return string|null
     */
    public function getComSpecPath(): ?string
    {
        return getenv('COMSPEC') ?: null;
    }

    /**
     * @return string|null
     */
    public function getCommonProgramFilesPath(): ?string
    {
        return getenv('COMMONPROGRAMFILES') ?: null;
    }

    /**
     * @return string|null
     */
    public function getCommonProgramFilesX86Path(): ?string
    {
        return getenv('COMMONPROGRAMFILES(X86)') ?: null;
    }

    /**
     * @return string|null
     */
    public function getDriverDataPath(): ?string
    {
        return getenv('DriverData') ?: null;
    }

    /**
     * @return string|null
     */
    public function getEnvironment(): string
    {
        return strtoupper(substr(PHP_OS, 0, 3));
    }

    /**
     * @return string|null
     */
    public function getHomeDrive(): ?string
    {
        return $this->getHomeDrivePath();
    }

    /**
     * @return string|null
     */
    public function getHomeDrivePath(): ?string
    {
        return getenv('HOMEDRIVE') ?: null;
    }

    /**
     * @return string|null
     */
    public function getHomePath(): ?string
    {
        return getenv('HOMEPATH') ?: null;
    }

    /**
     * @return string|null
     */
    public function getLocalAppDataPath(): ?string
    {
        return getenv('LOCALAPPDATA') ?: null;
    }

    /**
     * @return string|null
     */
    public function getLogonServerPath(): ?string
    {
        return getenv('LOGONSERVER') ?: null;
    }

    /**
     * @return string|null
     */
    public function getOneDrivePath(): ?string
    {
        return getenv('ONEDRIVE') ?: null;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return getenv('PATH') ?: null;
    }

    /**
     * @return string|null
     */
    public function getPathExt(): ?string
    {
        return getenv('PATHEXT') ?: null;
    }

    /**
     * @return string|null
     */
    public function getProgramDataPath(): ?string
    {
        return getenv('PROGRAMDATA') ?: null;
    }

    /**
     * @return string|null
     */
    public function getProgramFilesPath(): ?string
    {
        return getenv('PROGRAMFILES') ?: null;
    }

    /**
     * @return string|null
     */
    public function getProgramFilesX86Path(): ?string
    {
        return getenv('PROGRAMFILES(X86)') ?: null;
    }

    /**
     * @return string|null
     */
    public function getPublicPath(): ?string
    {
        return getenv('PUBLIC') ?: null;
    }

    /**
     * @return string|null
     */
    public function getSystemDrivePath(): ?string
    {
        return getenv('SYSTEMDRIVE') ?: null;
    }

    /**
     * @return string|null
     */
    public function getSystemRootPath(): ?string
    {
        return getenv('SYSTEMROOT') ?: null;
    }

    /**
     * @return string|null
     */
    public function getTempPath(): ?string
    {
        return getenv('TEMP') ?: null;
    }

    /**
     * @return string|null
     */
    public function getUserDomain(): ?string
    {
        return getenv('USERDOMAIN') ?: null;
    }

    /**
     * @return string|null
     */
    public function getUserDomainRoaminProfile(): ?string
    {
        return getenv('USERDOMAIN_ROAMINGPROFILE') ?: null;
    }

    /**
     * @return string|null
     */
    public function getUserProfilePath(): ?string
    {
        return getenv('USERPROFILE') ?: null;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return getenv('USERNAME') ?: null;
    }

    /**
     * @return string|null
     */
    public function getWindirPath(): ?string
    {
        return getenv('WINDIR') ?: null;
    }

    /**
     * @return string|null
     */
    public function getMyDocumentsPath(): ?string
    {
        return $this->getUserProfilePath() . DIRECTORY_SEPARATOR . 'Documents';
    }

    /**
     * @return string|null
     */
    public function getMyPituresPath(): ?string
    {
        return $this->getUserProfilePath() . DIRECTORY_SEPARATOR . 'Pictures';
    }

    /**
     * @return string|null
     */
    public function getMyVideosPath(): ?string
    {
        return $this->getUserProfilePath() . DIRECTORY_SEPARATOR . 'Videos';
    }


    /**
     * An array of all paths with underscore delimited key names
     *
     * @return array
     */
    public function getAllPaths(): array
    {
        return [
            'all_users_profile'        => $this->getAllUsersProfilePath(),
            'app_data'                 => $this->getAppDataPath(),
            'com_spec'                 => $this->getComSpecPath(),
            'common_program_files'     => $this->getCommonProgramFilesPath(),
            'common_program_files_x86' => $this->getCommonProgramFilesX86Path(),
            'driver_data'              => $this->getDriverDataPath(),
            'home_drive'               => $this->getHomeDrivePath(),
            'home'                     => $this->getHomePath(),
            'local_app_data'           => $this->getLocalAppDataPath(),
            'logon_server'             => $this->getLogonServerPath(),
            'one_drive'                => $this->getOneDrivePath(),
            'path'                     => $this->getPath(),
            'program_data'             => $this->getProgramDataPath(),
            'program_files'            => $this->getProgramFilesPath(),
            'program_files_x86'        => $this->getProgramFilesX86Path(),
            'public'                   => $this->getPublicPath(),
            'system_drive'             => $this->getSystemDrivePath(),
            'system_root'              => $this->getSystemRootPath(),
            'temp'                     => $this->getTempPath(),
            'user_profile'             => $this->getUserProfilePath(),
            'win_dir'                  => $this->getWindirPath(),
            'my_documents'             => $this->getMyDocumentsPath(),
            'my_pictures'              => $this->getMyPituresPath(),
        ];
    }

    /**
     * An array of all paths with the envvar as key
     *
     * @return array
     */
    public function getAllEnvironmentPaths(): array
    {
        $paths = $this->getAllPaths();
        $envvars = [];

        foreach ($paths as $key => $path) {
            $envvar_key = str_replace(['_', 'x86'], ['', '(x86)'], $key);
            $envvars[$envvar_key] = $path;
        }

        $envvars = array_change_key_case($envvars, CASE_UPPER);

        return $envvars;
    }

    /**
     * Determine if the current runtime environment is Windows
     *
     * @return bool
     */
    public function isWindowsEnvironment(): bool
    {
        return stripos(strtoupper(PHP_OS), 'WIN') === 0;
    }
}
