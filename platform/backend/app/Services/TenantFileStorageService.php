<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class TenantFileStorageService
{
    /**
     * Store a file for the current tenant
     *
     * @param UploadedFile $file
     * @param string $directory Directory within tenant folder (e.g., 'products', 'categories')
     * @param string $disk Disk to use ('tenant' for private, 'tenant_public' for public)
     * @return string|false The path where the file was stored or false on failure
     */
    public function store(UploadedFile $file, string $directory = '', string $disk = 'tenant')
    {
        if (!tenant()->exists()) {
            throw new \Exception('No tenant context available for file storage');
        }

        $storeId = tenant()->id;
        $path = $this->getTenantPath($storeId, $directory);

        return $file->store($path, $disk);
    }

    /**
     * Store a file with a specific name for the current tenant
     *
     * @param UploadedFile $file
     * @param string $name Filename to use
     * @param string $directory Directory within tenant folder
     * @param string $disk Disk to use
     * @return string|false
     */
    public function storeAs(UploadedFile $file, string $name, string $directory = '', string $disk = 'tenant')
    {
        if (!tenant()->exists()) {
            throw new \Exception('No tenant context available for file storage');
        }

        $storeId = tenant()->id;
        $path = $this->getTenantPath($storeId, $directory);

        return $file->storeAs($path, $name, $disk);
    }

    /**
     * Get the full URL for a tenant file
     *
     * @param string $path Path returned from store() method
     * @param string $disk Disk where file is stored
     * @return string
     */
    public function url(string $path, string $disk = 'tenant_public'): string
    {
        return Storage::disk($disk)->url($path);
    }

    /**
     * Delete a file for the current tenant
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function delete(string $path, string $disk = 'tenant'): bool
    {
        if (!tenant()->exists()) {
            throw new \Exception('No tenant context available for file deletion');
        }

        // Verify path starts with current tenant's folder
        $storeId = tenant()->id;
        if (!str_starts_with($path, "store_{$storeId}/")) {
            throw new \Exception('Attempted to delete file from different tenant');
        }

        return Storage::disk($disk)->delete($path);
    }

    /**
     * Get all files in a directory for current tenant
     *
     * @param string $directory
     * @param string $disk
     * @return array
     */
    public function files(string $directory = '', string $disk = 'tenant'): array
    {
        if (!tenant()->exists()) {
            throw new \Exception('No tenant context available');
        }

        $storeId = tenant()->id;
        $path = $this->getTenantPath($storeId, $directory);

        return Storage::disk($disk)->files($path);
    }

    /**
     * Check if a file exists for current tenant
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function exists(string $path, string $disk = 'tenant'): bool
    {
        if (!tenant()->exists()) {
            return false;
        }

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get the tenant-specific path
     *
     * @param int $storeId
     * @param string $directory
     * @return string
     */
    private function getTenantPath(int $storeId, string $directory = ''): string
    {
        $basePath = "store_{$storeId}";

        if (empty($directory)) {
            return $basePath;
        }

        return $basePath . '/' . trim($directory, '/');
    }

    /**
     * Store base64 encoded file
     *
     * @param string $base64Content
     * @param string $filename
     * @param string $directory
     * @param string $disk
     * @return string|false
     */
    public function storeBase64(string $base64Content, string $filename, string $directory = '', string $disk = 'tenant')
    {
        if (!tenant()->exists()) {
            throw new \Exception('No tenant context available for file storage');
        }

        $storeId = tenant()->id;
        $path = $this->getTenantPath($storeId, $directory) . '/' . $filename;

        // Decode base64
        $fileData = base64_decode($base64Content);

        return Storage::disk($disk)->put($path, $fileData) ? $path : false;
    }
}
