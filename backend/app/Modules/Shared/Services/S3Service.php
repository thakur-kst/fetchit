<?php

namespace Modules\Shared\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * S3 Service
 *
 * Handles S3/MinIO file uploads and URL construction.
 * Provides reusable methods for S3 operations across modules.
 *
 * @package Modules\Shared\Services
 */
class S3Service
{
    /**
     * Upload file to S3 and return the file path
     *
     * @param UploadedFile $file
     * @param string $path Path relative to bucket root (e.g., 'customer-logos/GL4947/file.png')
     * @param string $visibility File visibility ('public' or 'private') - Note: ACLs may be disabled on bucket
     * @return string Path of the uploaded file (relative to bucket root)
     * @throws \Exception
     */
    public function uploadFile(UploadedFile $file, string $path, string $visibility = 'public'): string
    {
        $diskName = $this->getStorageDisk();
        $storage = Storage::disk($diskName);
        
        // Normalize path - remove leading slash
        $path = ltrim($path, '/');
        $dirName = dirname($path);
        $fileName = basename($path);
        
        // If dirname is '.' or empty, use root
        if ($dirName === '.' || empty($dirName)) {
            $dirName = '';
        }
        
        // Use put() method - read file contents and upload directly
        $fileContents = file_get_contents($file->getRealPath());
        
        // For AWS S3 buckets with ACL disabled (common for newer buckets),
        // don't pass visibility parameter - use bucket policy for public access instead
        // Try without visibility first (works for buckets with ACL disabled)
        $success = $storage->put($path, $fileContents);
        
        // If that fails, it might be because the bucket requires visibility
        // But since most modern buckets have ACL disabled, we'll just check if upload succeeded
        if (!$success) {
            Log::error('S3 Upload failed - put() returned false', [
                'path' => $path,
                'disk' => $diskName,
                'bucket' => config("filesystems.disks.{$diskName}.bucket"),
            ]);
            throw new \Exception('File upload failed: unable to upload file to S3. Check bucket permissions and ACL settings.');
        }
        
        // Verify file exists
        if (!$storage->exists($path)) {
            Log::error('File upload verification failed', [
                'path' => $path,
                'disk' => $diskName,
                'bucket' => config("filesystems.disks.{$diskName}.bucket"),
            ]);
            throw new \Exception('File upload failed: file not found after upload');
        }

        // Return the path (not URL)
        return $path;
    }

    /**
     * Construct public URL from file path
     *
     * @param string $filePath Path relative to bucket root
     * @return string Public URL
     * @throws \Exception
     */
    public function constructUrl(string $filePath): string
    {
        $config = $this->getConfig();
        $diskName = $this->getStorageDisk();

        if (empty($config['bucket'])) {
            throw new \Exception('Bucket name is not configured');
        }

        // Use custom URL if provided
        if (!empty($config['url'])) {
            return $this->constructUrlFromCustomUrl($config['url'], $config['bucket'], $filePath);
        }

        // For real AWS S3 (no endpoint or AWS endpoint), construct AWS URL
        // Don't use the endpoint for URL construction - use region-based URL
        if ($diskName === 's3') {
            // Check if endpoint is AWS S3 endpoint or empty
            $endpoint = $config['endpoint'] ?? '';
            $endpointLower = strtolower($endpoint);
            
            // If empty or AWS endpoint, construct AWS S3 URL from region
            if (empty($endpoint) || str_contains($endpointLower, 'amazonaws.com')) {
                return $this->constructAwsS3Url($config['bucket'], $filePath, $config['region'] ?? 'us-east-1');
            }
        }

        // Construct from endpoint (MinIO or custom S3-compatible)
        return $this->constructUrlFromEndpoint($config['endpoint'], $config['bucket'], $filePath, $config['use_path_style']);
    }


    /**
     * Delete file from S3
     *
     * @param string $path Path relative to bucket root
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        return Storage::disk($this->getStorageDisk())->delete($path);
    }

    /**
     * Check if file exists in S3
     *
     * @param string $path Path relative to bucket root
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk($this->getStorageDisk())->exists($path);
    }

    /**
     * Generate a presigned URL for temporary access to a file
     *
     * @param string $path Path relative to bucket root
     * @param int $expirationMinutes Expiration time in minutes (default: 60 minutes)
     * @param array $options Additional options (e.g., 'ResponseContentDisposition' for download filename)
     * @return string Presigned URL
     * @throws \Exception
     */
    public function generatePresignedUrl(string $path, int $expirationMinutes = 60, array $options = []): string
    {
        $diskName = $this->getStorageDisk();
        $storage = Storage::disk($diskName);
        
        // Normalize path
        $path = ltrim($path, '/');
        
        // Check if file exists
        if (!$storage->exists($path)) {
            throw new \Exception("File not found: {$path}");
        }
        
        // Generate presigned URL using Laravel's temporaryUrl method
        // This works for both AWS S3 and S3-compatible services like MinIO
        try {
            $expiration = now()->addMinutes($expirationMinutes);
            $presignedUrl = $storage->temporaryUrl($path, $expiration, $options);
            
            return $presignedUrl;
        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL', [
                'path' => $path,
                'disk' => $diskName,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to generate presigned URL: " . $e->getMessage());
        }
    }

    /**
     * Get or generate presigned URL for a filename with Redis caching
     *
     * @param string $filename Filename (e.g., "69564350af133_1767261008.png")
     * @param string $configPath Config path to get path prefix (e.g., "customer.logo.path_prefix")
     * @param int $expirationMinutes Expiration time in minutes (default: from config or 10080)
     * @return string Presigned URL
     * @throws \Exception
     */
    public function getOrGeneratePresignedUrl(string $filename, string $configPath, ?int $expirationMinutes = null): string
    {
        // Get path prefix from config
        $pathPrefix = config($configPath);
        if (!$pathPrefix) {
            throw new \Exception("Configuration path not found: {$configPath}");
        }

        // Construct full path
        $filePath = trim($pathPrefix, '/') . '/' . $filename;

        // Redis cache key
        $cacheKey = "presigned_url:{$filePath}";

        // Try to get from cache
        $cachedUrl = \Cache::get($cacheKey);

        if ($cachedUrl) {
            // Verify if cached URL is still accessible
            if ($this->isPresignedUrlAccessible($cachedUrl)) {
                return $cachedUrl;
            }
            // If not accessible, remove from cache and generate new one
            \Cache::forget($cacheKey);
        }

        // Get expiration from parameter or config
        if ($expirationMinutes === null) {
            $expirationMinutes = config('customer.presigned_url.expiration_minutes', 10080);
        }

        // Generate new presigned URL
        $presignedUrl = $this->generatePresignedUrl($filePath, $expirationMinutes);

        // Cache the presigned URL (cache for slightly less than expiration to ensure it's always valid)
        $cacheTTL = now()->addMinutes($expirationMinutes - 60); // Cache 1 hour less than expiration
        \Cache::put($cacheKey, $presignedUrl, $cacheTTL);

        return $presignedUrl;
    }

    /**
     * Check if presigned URL is still accessible
     *
     * @param string $url Presigned URL
     * @return bool
     */
    public function isPresignedUrlAccessible(string $url): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode >= 200 && $httpCode < 400;
        } catch (\Exception $e) {
            Log::warning('Failed to check presigned URL accessibility', [
                'url' => substr($url, 0, 100) . '...',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Extract file path from S3 URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractPathFromUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl) {
            return null;
        }

        $path = $parsedUrl['path'] ?? '';
        $path = ltrim($path, '/');

        // Remove bucket name from path if present (path-style endpoint)
        $bucket = env('AWS_BUCKET') ?: config('filesystems.disks.s3.bucket');
        if ($bucket && str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        return $path ?: null;
    }

    /**
     * Get the storage disk name to use (s3 or minio)
     *
     * @return string
     */
    private function getStorageDisk(): string
    {
        // Check if explicitly configured
        $disk = env('FILESYSTEM_S3_DISK');
        if ($disk && in_array($disk, ['s3', 'minio'], true)) {
            return $disk;
        }

        // Auto-detect based on endpoint
        $endpoint = env('AWS_ENDPOINT') ?: config('filesystems.disks.s3.endpoint');
        
        // If endpoint is not set or empty, use real AWS S3
        if (empty($endpoint)) {
            return 's3';
        }

        // Check if endpoint points to MinIO (contains 'minio' or is localhost)
        $endpointLower = strtolower($endpoint);
        if (str_contains($endpointLower, 'minio') || 
            str_contains($endpointLower, 'localhost') || 
            str_contains($endpointLower, '127.0.0.1')) {
            return 'minio';
        }

        // Check if endpoint is an AWS S3 endpoint (amazonaws.com)
        // For real AWS S3, we should NOT set endpoint - let SDK auto-detect
        // But if it's set to AWS endpoint, treat it as real AWS S3
        if (str_contains($endpointLower, 'amazonaws.com')) {
            return 's3';
        }

        // Default to s3 for real AWS S3
        return 's3';
    }

    /**
     * Get S3 configuration values
     *
     * @return array
     */
    private function getConfig(): array
    {
        $diskName = $this->getStorageDisk();
        
        return [
            'bucket' => env('AWS_BUCKET') ?: config("filesystems.disks.{$diskName}.bucket"),
            'endpoint' => env('AWS_ENDPOINT') ?: config("filesystems.disks.{$diskName}.endpoint"),
            'use_path_style' => (bool) (env('AWS_USE_PATH_STYLE_ENDPOINT') ?: config("filesystems.disks.{$diskName}.use_path_style_endpoint", false)),
            'url' => env('AWS_URL') ?: config("filesystems.disks.{$diskName}.url"),
            'region' => env('AWS_DEFAULT_REGION') ?: config("filesystems.disks.{$diskName}.region", 'us-east-1'),
        ];
    }

    /**
     * Construct URL from custom URL configuration
     *
     * @param string $customUrl
     * @param string $bucket
     * @param string $filePath
     * @return string
     */
    private function constructUrlFromCustomUrl(string $customUrl, string $bucket, string $filePath): string
    {
        $customUrl = rtrim($customUrl, '/');
        $parsedUrl = parse_url($customUrl);
        $urlPath = trim($parsedUrl['path'] ?? '', '/');

        // Check if URL already includes bucket
        if ($urlPath === $bucket || str_ends_with($urlPath, '/' . $bucket)) {
            return $customUrl . '/' . $filePath;
        }

        // Build host with port
        $urlHost = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? '');
        if (isset($parsedUrl['port'])) {
            $urlHost .= ':' . $parsedUrl['port'];
        }

        return $urlHost . '/' . $bucket . '/' . $filePath;
    }

    /**
     * Construct URL from endpoint
     *
     * @param string $endpoint
     * @param string $bucket
     * @param string $filePath
     * @param bool $usePathStyle
     * @return string
     * @throws \Exception
     */
    private function constructUrlFromEndpoint(string $endpoint, string $bucket, string $filePath, bool $usePathStyle): string
    {
        if (empty($endpoint)) {
            throw new \Exception('S3 endpoint is not configured');
        }

        $parsed = parse_url($endpoint);
        
        if (empty($parsed['host'])) {
            throw new \Exception('Invalid S3 endpoint configuration: host is missing');
        }

        $host = $parsed['host'];
        $scheme = $parsed['scheme'] ?? 'http';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        // Path-style: http://host:port/bucket/path (used by MinIO)
        if ($usePathStyle) {
            return $scheme . '://' . $host . $port . '/' . $bucket . '/' . $filePath;
        }

        // Virtual-hosted-style: http://bucket.host:port/path (used by AWS S3)
        return $scheme . '://' . $bucket . '.' . $host . $port . '/' . $filePath;
    }

    /**
     * Construct AWS S3 URL (for real AWS S3, not MinIO)
     *
     * @param string $bucket
     * @param string $filePath
     * @param string $region
     * @return string
     */
    private function constructAwsS3Url(string $bucket, string $filePath, string $region = 'us-east-1'): string
    {
        // AWS S3 virtual-hosted-style URL format
        // https://bucket-name.s3.region.amazonaws.com/path/to/file
        // For us-east-1, it's just s3.amazonaws.com (no region)
        if ($region === 'us-east-1') {
            return 'https://' . $bucket . '.s3.amazonaws.com/' . $filePath;
        }

        return 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/' . $filePath;
    }

}

