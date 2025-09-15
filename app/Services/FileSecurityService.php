<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileSecurityService
{
    /**
     * Educational content focused file type whitelist
     */
    public const ALLOWED_FILE_TYPES = [
        // Images
        'jpg' => [
            'mime_types' => ['image/jpeg'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'category' => 'image',
            'educational_value' => 'high',
        ],
        'jpeg' => [
            'mime_types' => ['image/jpeg'],
            'max_size' => 5 * 1024 * 1024,
            'category' => 'image',
            'educational_value' => 'high',
        ],
        'png' => [
            'mime_types' => ['image/png'],
            'max_size' => 5 * 1024 * 1024,
            'category' => 'image',
            'educational_value' => 'high',
        ],
        'gif' => [
            'mime_types' => ['image/gif'],
            'max_size' => 2 * 1024 * 1024, // 2MB for GIFs
            'category' => 'image',
            'educational_value' => 'medium',
        ],
        'webp' => [
            'mime_types' => ['image/webp'],
            'max_size' => 3 * 1024 * 1024,
            'category' => 'image',
            'educational_value' => 'high',
        ],
        'svg' => [
            'mime_types' => ['image/svg+xml'],
            'max_size' => 1 * 1024 * 1024, // 1MB for SVGs
            'category' => 'image',
            'educational_value' => 'high',
            'requires_content_scan' => true, // SVGs can contain scripts
        ],

        // Documents
        'pdf' => [
            'mime_types' => ['application/pdf'],
            'max_size' => 25 * 1024 * 1024, // 25MB
            'category' => 'document',
            'educational_value' => 'high',
            'requires_content_scan' => true,
        ],
        'doc' => [
            'mime_types' => ['application/msword'],
            'max_size' => 15 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'high',
        ],
        'docx' => [
            'mime_types' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'max_size' => 15 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'high',
        ],
        'xls' => [
            'mime_types' => ['application/vnd.ms-excel'],
            'max_size' => 10 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'medium',
        ],
        'xlsx' => [
            'mime_types' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'max_size' => 10 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'medium',
        ],
        'ppt' => [
            'mime_types' => ['application/vnd.ms-powerpoint'],
            'max_size' => 20 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'high',
        ],
        'pptx' => [
            'mime_types' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'max_size' => 20 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'high',
        ],
        'txt' => [
            'mime_types' => ['text/plain'],
            'max_size' => 1 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'medium',
            'requires_content_scan' => true,
        ],
        'csv' => [
            'mime_types' => ['text/csv', 'application/csv'],
            'max_size' => 5 * 1024 * 1024,
            'category' => 'document',
            'educational_value' => 'medium',
        ],

        // Audio
        'mp3' => [
            'mime_types' => ['audio/mpeg', 'audio/mp3'],
            'max_size' => 50 * 1024 * 1024, // 50MB
            'category' => 'audio',
            'educational_value' => 'high',
        ],
        'wav' => [
            'mime_types' => ['audio/wav', 'audio/wave'],
            'max_size' => 100 * 1024 * 1024, // 100MB
            'category' => 'audio',
            'educational_value' => 'medium',
        ],
        'ogg' => [
            'mime_types' => ['audio/ogg'],
            'max_size' => 50 * 1024 * 1024,
            'category' => 'audio',
            'educational_value' => 'medium',
        ],
        'm4a' => [
            'mime_types' => ['audio/mp4', 'audio/m4a'],
            'max_size' => 50 * 1024 * 1024,
            'category' => 'audio',
            'educational_value' => 'high',
        ],

        // Video
        'mp4' => [
            'mime_types' => ['video/mp4'],
            'max_size' => 200 * 1024 * 1024, // 200MB
            'category' => 'video',
            'educational_value' => 'high',
        ],
        'webm' => [
            'mime_types' => ['video/webm'],
            'max_size' => 200 * 1024 * 1024,
            'category' => 'video',
            'educational_value' => 'high',
        ],
        'mov' => [
            'mime_types' => ['video/quicktime'],
            'max_size' => 200 * 1024 * 1024,
            'category' => 'video',
            'educational_value' => 'medium',
        ],
        'avi' => [
            'mime_types' => ['video/x-msvideo'],
            'max_size' => 200 * 1024 * 1024,
            'category' => 'video',
            'educational_value' => 'medium',
        ],

        // Educational archives (limited and controlled)
        'zip' => [
            'mime_types' => ['application/zip'],
            'max_size' => 50 * 1024 * 1024,
            'category' => 'archive',
            'educational_value' => 'medium',
            'requires_content_scan' => true,
            'requires_extraction_limit' => true,
        ],
    ];

    /**
     * Strictly forbidden file types (security risk)
     */
    public const FORBIDDEN_FILE_TYPES = [
        // Executables
        'exe', 'msi', 'app', 'deb', 'rpm', 'dmg',
        // Scripts
        'js', 'vbs', 'bat', 'cmd', 'ps1', 'sh', 'scr',
        // Web files with potential scripts
        'html', 'htm', 'php', 'asp', 'jsp', 'cfm',
        // Suspicious archives
        'rar', 'tar', 'gz', '7z', 'bz2',
        // Database files
        'sql', 'db', 'sqlite', 'mdb',
        // System files
        'dll', 'so', 'dylib', 'sys', 'ini', 'cfg',
    ];

    /**
     * Content scanning patterns for inappropriate material
     */
    public const INAPPROPRIATE_CONTENT_PATTERNS = [
        // Violence-related terms
        '/\b(kill|murder|violence|weapon|gun|knife|bomb)\b/i',
        // Adult content
        '/\b(sex|porn|adult|xxx|explicit)\b/i',
        // Harmful instructions
        '/\b(suicide|self-harm|dangerous|illegal)\b/i',
        // Hate speech indicators
        '/\b(hate|racist|discriminat)\b/i',
    ];

    /**
     * Maximum file sizes per user role
     */
    public const ROLE_BASED_LIMITS = [
        'admin' => [
            'max_total_storage' => 10 * 1024 * 1024 * 1024, // 10GB
            'max_single_file' => 500 * 1024 * 1024, // 500MB
            'upload_rate_limit' => 100, // files per hour
        ],
        'parent' => [
            'max_total_storage' => 2 * 1024 * 1024 * 1024, // 2GB
            'max_single_file' => 200 * 1024 * 1024, // 200MB
            'upload_rate_limit' => 50, // files per hour
        ],
        'child' => [
            'max_total_storage' => 500 * 1024 * 1024, // 500MB
            'max_single_file' => 50 * 1024 * 1024, // 50MB
            'upload_rate_limit' => 20, // files per hour
        ],
        'guest' => [
            'max_total_storage' => 100 * 1024 * 1024, // 100MB
            'max_single_file' => 10 * 1024 * 1024, // 10MB
            'upload_rate_limit' => 5, // files per hour
        ],
    ];

    protected ThreatDetectionService $threatDetection;

    protected FileIntegrityService $integrityService;

    public function __construct(
        ThreatDetectionService $threatDetection,
        FileIntegrityService $integrityService
    ) {
        $this->threatDetection = $threatDetection;
        $this->integrityService = $integrityService;
    }

    /**
     * Comprehensive file validation with security scanning
     */
    public function validateFile(UploadedFile $file, string $userRole = 'guest', array $context = []): array
    {
        $validationResult = [
            'valid' => false,
            'file_info' => $this->getFileInfo($file),
            'security_checks' => [],
            'warnings' => [],
            'errors' => [],
            'risk_level' => 'unknown',
        ];

        try {
            // Step 1: Basic file validation
            $this->performBasicValidation($file, $validationResult);

            // Step 2: Extension and MIME type validation
            $this->validateFileType($file, $validationResult);

            // Step 3: Size validation based on user role
            $this->validateFileSize($file, $userRole, $validationResult);

            // Step 4: Content security scanning
            $this->performContentSecurityScan($file, $validationResult);

            // Step 5: Threat detection
            $this->performThreatDetection($file, $validationResult);

            // Step 6: File integrity check
            $this->performIntegrityCheck($file, $validationResult);

            // Step 7: Educational content assessment
            $this->assessEducationalValue($file, $validationResult);

            // Final risk assessment
            $this->calculateRiskLevel($validationResult);

            // Log security validation
            $this->logSecurityValidation($file, $validationResult, $context);

            $validationResult['valid'] = empty($validationResult['errors']);

        } catch (\Exception $e) {
            $validationResult['errors'][] = 'Security validation failed: '.$e->getMessage();
            $validationResult['risk_level'] = 'high';

            Log::error('File security validation error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
        }

        return $validationResult;
    }

    /**
     * Validate file against educational content guidelines
     */
    public function validateEducationalContent(UploadedFile $file): array
    {
        $result = [
            'appropriate' => true,
            'educational_value' => 'unknown',
            'content_warnings' => [],
            'suggestions' => [],
        ];

        $extension = strtolower($file->getClientOriginalExtension());

        if (isset(self::ALLOWED_FILE_TYPES[$extension])) {
            $fileConfig = self::ALLOWED_FILE_TYPES[$extension];
            $result['educational_value'] = $fileConfig['educational_value'];

            // Content scanning for text-based files
            if ($fileConfig['requires_content_scan'] ?? false) {
                $content = $this->extractTextContent($file);
                $this->scanContentForInappropriateMaterial($content, $result);
            }
        }

        return $result;
    }

    /**
     * Get comprehensive file information
     */
    public function getFileInfo(UploadedFile $file): array
    {
        return [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => strtolower($file->getClientOriginalExtension()),
            'hash_sha256' => hash_file('sha256', $file->getRealPath()),
            'hash_md5' => hash_file('md5', $file->getRealPath()),
            'upload_timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Check if file type is explicitly forbidden
     */
    public function isForbiddenFileType(string $extension): bool
    {
        return in_array(strtolower($extension), self::FORBIDDEN_FILE_TYPES);
    }

    /**
     * Get maximum allowed file size for user role
     */
    public function getMaxFileSizeForRole(string $role): int
    {
        return self::ROLE_BASED_LIMITS[$role]['max_single_file'] ?? self::ROLE_BASED_LIMITS['guest']['max_single_file'];
    }

    /**
     * Generate secure filename
     */
    public function generateSecureFilename(UploadedFile $file, int $topicId): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('YmdHis');
        $random = Str::random(16);
        $hash = substr(hash('sha256', $file->getClientOriginalName().$timestamp), 0, 8);

        return "secure_{$topicId}_{$timestamp}_{$hash}_{$random}.{$extension}";
    }

    /**
     * Perform basic file validation
     */
    private function performBasicValidation(UploadedFile $file, array &$result): void
    {
        $result['security_checks']['basic_validation'] = 'passed';

        // Check if file was uploaded successfully
        if (! $file->isValid()) {
            $result['errors'][] = 'File upload failed or corrupted';
            $result['security_checks']['basic_validation'] = 'failed';

            return;
        }

        // Check if file is actually uploaded
        if (! is_uploaded_file($file->getRealPath())) {
            $result['errors'][] = 'Security violation: File was not uploaded through HTTP POST';
            $result['security_checks']['basic_validation'] = 'failed';

            return;
        }

        // Check for null bytes in filename (security vulnerability)
        if (strpos($file->getClientOriginalName(), "\0") !== false) {
            $result['errors'][] = 'Security violation: Null bytes detected in filename';
            $result['security_checks']['basic_validation'] = 'failed';

            return;
        }

        // Check filename length
        if (strlen($file->getClientOriginalName()) > 255) {
            $result['errors'][] = 'Filename too long (maximum 255 characters)';
            $result['security_checks']['basic_validation'] = 'failed';

            return;
        }
    }

    /**
     * Validate file type and MIME type
     */
    private function validateFileType(UploadedFile $file, array &$result): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $result['security_checks']['file_type_validation'] = 'passed';

        // Check if extension is forbidden
        if ($this->isForbiddenFileType($extension)) {
            $result['errors'][] = "File type '{$extension}' is forbidden for security reasons";
            $result['security_checks']['file_type_validation'] = 'failed';

            return;
        }

        // Check if extension is allowed
        if (! isset(self::ALLOWED_FILE_TYPES[$extension])) {
            $result['errors'][] = "File type '{$extension}' is not allowed for educational content";
            $result['security_checks']['file_type_validation'] = 'failed';

            return;
        }

        // Validate MIME type matches extension
        $allowedMimeTypes = self::ALLOWED_FILE_TYPES[$extension]['mime_types'];
        if (! in_array($mimeType, $allowedMimeTypes)) {
            $result['errors'][] = 'File content does not match extension (MIME type mismatch)';
            $result['security_checks']['file_type_validation'] = 'failed';

            return;
        }

        $result['file_category'] = self::ALLOWED_FILE_TYPES[$extension]['category'];
        $result['educational_value'] = self::ALLOWED_FILE_TYPES[$extension]['educational_value'];
    }

    /**
     * Validate file size based on user role
     */
    private function validateFileSize(UploadedFile $file, string $userRole, array &$result): void
    {
        $fileSize = $file->getSize();
        $extension = strtolower($file->getClientOriginalExtension());

        $result['security_checks']['size_validation'] = 'passed';

        // Check file type specific limit
        if (isset(self::ALLOWED_FILE_TYPES[$extension])) {
            $typeMaxSize = self::ALLOWED_FILE_TYPES[$extension]['max_size'];
            if ($fileSize > $typeMaxSize) {
                $result['errors'][] = "File too large for type '{$extension}' (max: ".$this->formatBytes($typeMaxSize).')';
                $result['security_checks']['size_validation'] = 'failed';

                return;
            }
        }

        // Check user role limit
        $roleMaxSize = $this->getMaxFileSizeForRole($userRole);
        if ($fileSize > $roleMaxSize) {
            $result['errors'][] = "File too large for user role '{$userRole}' (max: ".$this->formatBytes($roleMaxSize).')';
            $result['security_checks']['size_validation'] = 'failed';

            return;
        }

        // Warning for large files
        if ($fileSize > 10 * 1024 * 1024) { // 10MB
            $result['warnings'][] = 'Large file detected. Consider compressing if possible.';
        }
    }

    /**
     * Perform content security scanning
     */
    private function performContentSecurityScan(UploadedFile $file, array &$result): void
    {
        $result['security_checks']['content_scan'] = 'passed';

        $extension = strtolower($file->getClientOriginalExtension());
        $requiresContentScan = self::ALLOWED_FILE_TYPES[$extension]['requires_content_scan'] ?? false;

        if ($requiresContentScan) {
            try {
                $content = $this->extractTextContent($file);
                $this->scanContentForInappropriateMaterial($content, $result);
            } catch (\Exception $e) {
                $result['warnings'][] = 'Could not scan file content: '.$e->getMessage();
            }
        }

        // Special handling for SVG files (can contain JavaScript)
        if ($extension === 'svg') {
            $this->scanSvgForScripts($file, $result);
        }

        // Special handling for ZIP files
        if ($extension === 'zip') {
            $this->scanArchiveContents($file, $result);
        }
    }

    /**
     * Perform threat detection
     */
    private function performThreatDetection(UploadedFile $file, array &$result): void
    {
        $result['security_checks']['threat_detection'] = 'passed';

        try {
            $threatResult = $this->threatDetection->scanFile($file);

            if ($threatResult['threats_detected']) {
                $result['errors'][] = 'Security threats detected: '.implode(', ', $threatResult['threats']);
                $result['security_checks']['threat_detection'] = 'failed';
            }

            if (! empty($threatResult['warnings'])) {
                $result['warnings'] = array_merge($result['warnings'], $threatResult['warnings']);
            }
        } catch (\Exception $e) {
            $result['warnings'][] = 'Threat detection service unavailable: '.$e->getMessage();
        }
    }

    /**
     * Perform file integrity check
     */
    private function performIntegrityCheck(UploadedFile $file, array &$result): void
    {
        $result['security_checks']['integrity_check'] = 'passed';

        try {
            $integrityResult = $this->integrityService->validateFile($file);

            if (! $integrityResult['valid']) {
                $result['errors'][] = 'File integrity check failed: '.$integrityResult['reason'];
                $result['security_checks']['integrity_check'] = 'failed';
            }
        } catch (\Exception $e) {
            $result['warnings'][] = 'Integrity check service unavailable: '.$e->getMessage();
        }
    }

    /**
     * Assess educational value
     */
    private function assessEducationalValue(UploadedFile $file, array &$result): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (isset(self::ALLOWED_FILE_TYPES[$extension])) {
            $educationalValue = self::ALLOWED_FILE_TYPES[$extension]['educational_value'];

            if ($educationalValue === 'low') {
                $result['warnings'][] = 'File type has limited educational value. Consider using more appropriate formats.';
            }
        }
    }

    /**
     * Calculate overall risk level
     */
    private function calculateRiskLevel(array &$result): void
    {
        $riskScore = 0;

        // Errors add significant risk
        $riskScore += count($result['errors']) * 10;

        // Warnings add minor risk
        $riskScore += count($result['warnings']) * 2;

        // Failed security checks add major risk
        foreach ($result['security_checks'] as $check => $status) {
            if ($status === 'failed') {
                $riskScore += 15;
            }
        }

        // Determine risk level
        if ($riskScore >= 20) {
            $result['risk_level'] = 'high';
        } elseif ($riskScore >= 10) {
            $result['risk_level'] = 'medium';
        } elseif ($riskScore >= 5) {
            $result['risk_level'] = 'low';
        } else {
            $result['risk_level'] = 'minimal';
        }
    }

    /**
     * Extract text content from file for scanning
     */
    private function extractTextContent(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        switch ($extension) {
            case 'txt':
                return file_get_contents($file->getRealPath());
            case 'svg':
                return file_get_contents($file->getRealPath());
            case 'pdf':
                // Would need a PDF text extraction library
                return ''; // Placeholder
            default:
                return '';
        }
    }

    /**
     * Scan content for inappropriate material
     */
    private function scanContentForInappropriateMaterial(string $content, array &$result): void
    {
        foreach (self::INAPPROPRIATE_CONTENT_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['errors'][] = 'Inappropriate content detected in file';
                $result['security_checks']['content_scan'] = 'failed';

                return;
            }
        }
    }

    /**
     * Scan SVG files for embedded scripts
     */
    private function scanSvgForScripts(UploadedFile $file, array &$result): void
    {
        $content = file_get_contents($file->getRealPath());

        // Check for script tags or JavaScript
        if (preg_match('/<script/i', $content) || preg_match('/javascript:/i', $content)) {
            $result['errors'][] = 'SVG file contains potentially dangerous scripts';
            $result['security_checks']['content_scan'] = 'failed';
        }
    }

    /**
     * Scan archive contents
     */
    private function scanArchiveContents(UploadedFile $file, array &$result): void
    {
        // This would require ZipArchive or similar to inspect contents
        // For now, we'll add a warning
        $result['warnings'][] = 'Archive files require manual review for security';
    }

    /**
     * Log security validation results
     */
    private function logSecurityValidation(UploadedFile $file, array $result, array $context): void
    {
        Log::channel('security')->info('File security validation completed', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'risk_level' => $result['risk_level'],
            'validation_passed' => $result['valid'],
            'errors_count' => count($result['errors']),
            'warnings_count' => count($result['warnings']),
            'context' => $context,
            'user_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Format bytes for human readable display
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
