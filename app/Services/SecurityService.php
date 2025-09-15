<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Security Service for Unified Markdown Learning Materials System
 *
 * Provides comprehensive security features including:
 * - File upload security validation
 * - Content security scanning
 * - Malicious content detection
 * - Access control enforcement
 * - Security audit logging
 * - Threat detection and prevention
 */
class SecurityService
{
    /**
     * Allowed file types for uploads
     */
    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',

        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',

        // Audio/Video
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'video/mp4',
        'video/webm',
        'video/ogg',

        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ];

    /**
     * Dangerous file extensions that should never be allowed
     */
    private const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
        'php', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb', 'sh', 'bash',
        'ps1', 'psm1', 'psd1', 'msi', 'dll', 'scf', 'lnk', 'inf',
        'reg', 'cpl', 'ws', 'wsf', 'wsh', 'hta', 'msp', 'mst',
    ];

    /**
     * Maximum file sizes by type (in bytes)
     */
    private const MAX_FILE_SIZES = [
        'image' => 5 * 1024 * 1024,      // 5MB
        'document' => 25 * 1024 * 1024,  // 25MB
        'audio' => 50 * 1024 * 1024,     // 50MB
        'video' => 100 * 1024 * 1024,    // 100MB
        'archive' => 50 * 1024 * 1024,   // 50MB
    ];

    /**
     * Malicious content patterns to detect
     */
    private const MALICIOUS_PATTERNS = [
        // Script injection patterns
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/on\w+\s*=/i',
        '/data:text\/html/i',

        // PHP code injection
        '/<\?php/i',
        '/<\?=/i',
        '/<%/i',

        // SQL injection patterns
        '/union\s+select/i',
        '/drop\s+table/i',
        '/insert\s+into/i',
        '/delete\s+from/i',

        // Command injection
        '/;\s*rm\s+/i',
        '/;\s*wget\s+/i',
        '/;\s*curl\s+/i',
        '/;\s*nc\s+/i',

        // File inclusion
        '/\.\.\/\.\.\//i',
        '/file:\/\//i',
        '/ftp:\/\//i',

        // Suspicious URLs
        '/https?:\/\/(?:\d{1,3}\.){3}\d{1,3}/i', // IP addresses
        '/bit\.ly|tinyurl|t\.co|goo\.gl/i', // URL shorteners
    ];

    /**
     * Validate uploaded file for security
     */
    public function validateFileUpload(UploadedFile $file): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'security_level' => 'unknown',
        ];

        try {
            // Check file size
            $sizeValidation = $this->validateFileSize($file);
            if (! $sizeValidation['valid']) {
                $result['errors'][] = $sizeValidation['error'];

                return $result;
            }

            // Check file type and extension
            $typeValidation = $this->validateFileType($file);
            if (! $typeValidation['valid']) {
                $result['errors'][] = $typeValidation['error'];

                return $result;
            }

            // Check for dangerous file signatures
            $signatureValidation = $this->validateFileSignature($file);
            if (! $signatureValidation['valid']) {
                $result['errors'][] = $signatureValidation['error'];

                return $result;
            }

            // Scan file content for malicious patterns
            $contentScan = $this->scanFileContent($file);
            if (! $contentScan['safe']) {
                $result['errors'] = array_merge($result['errors'], $contentScan['threats']);

                return $result;
            }

            // Check filename for suspicious patterns
            $filenameValidation = $this->validateFilename($file->getClientOriginalName());
            if (! $filenameValidation['valid']) {
                $result['warnings'][] = $filenameValidation['warning'];
            }

            $result['valid'] = true;
            $result['security_level'] = $contentScan['security_level'];
            $result['file_type'] = $typeValidation['category'];

            // Log successful validation
            $this->logSecurityEvent('file_upload_validated', [
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'security_level' => $result['security_level'],
            ]);

        } catch (\Exception $e) {
            $result['errors'][] = 'Security validation failed: '.$e->getMessage();
            $this->logSecurityEvent('file_validation_error', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Scan content for malicious patterns
     */
    public function scanContentSecurity(string $content): array
    {
        $threats = [];
        $suspiciousCount = 0;

        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $threats[] = [
                    'type' => 'malicious_pattern',
                    'pattern' => $pattern,
                    'match' => $matches[0] ?? '',
                    'severity' => 'high',
                ];
                $suspiciousCount++;
            }
        }

        // Additional content analysis
        $additionalThreats = $this->analyzeContentStructure($content);
        $threats = array_merge($threats, $additionalThreats);

        $securityLevel = $this->calculateSecurityLevel($threats, $suspiciousCount);

        return [
            'safe' => empty($threats),
            'threats' => $threats,
            'security_level' => $securityLevel,
            'scan_timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Validate URL for security
     */
    public function validateUrl(string $url): array
    {
        $result = [
            'valid' => false,
            'safe' => false,
            'warnings' => [],
            'security_level' => 'unknown',
        ];

        // Basic URL format validation
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $result['warnings'][] = 'Invalid URL format';

            return $result;
        }

        $parsedUrl = parse_url($url);

        // Check protocol
        if (! in_array($parsedUrl['scheme'] ?? '', ['http', 'https'])) {
            $result['warnings'][] = 'Only HTTP and HTTPS protocols are allowed';

            return $result;
        }

        // Check for suspicious domains
        $domainCheck = $this->validateDomain($parsedUrl['host'] ?? '');
        if (! $domainCheck['safe']) {
            $result['warnings'] = array_merge($result['warnings'], $domainCheck['warnings']);
        }

        // Check for suspicious URL patterns
        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $url)) {
                $result['warnings'][] = 'URL contains suspicious patterns';
                break;
            }
        }

        $result['valid'] = true;
        $result['safe'] = empty($result['warnings']);
        $result['security_level'] = $result['safe'] ? 'safe' : 'suspicious';

        return $result;
    }

    /**
     * Generate secure file token for access control
     */
    public function generateSecureFileToken(string $filePath, int $userId, int $expiryMinutes = 60): string
    {
        $payload = [
            'file_path' => $filePath,
            'user_id' => $userId,
            'expires_at' => now()->addMinutes($expiryMinutes)->timestamp,
            'nonce' => Str::random(16),
        ];

        $token = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $token, config('app.key'));

        return $token.'.'.$signature;
    }

    /**
     * Validate secure file token
     */
    public function validateSecureFileToken(string $token, string $filePath, int $userId): bool
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return false;
            }

            [$payloadToken, $signature] = $parts;

            // Verify signature
            $expectedSignature = hash_hmac('sha256', $payloadToken, config('app.key'));
            if (! hash_equals($expectedSignature, $signature)) {
                return false;
            }

            // Decode payload
            $payload = json_decode(base64_decode($payloadToken), true);
            if (! $payload) {
                return false;
            }

            // Validate payload
            if (
                $payload['file_path'] !== $filePath ||
                $payload['user_id'] !== $userId ||
                $payload['expires_at'] < now()->timestamp
            ) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logSecurityEvent('token_validation_error', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'user_id' => $userId,
            ]);

            return false;
        }
    }

    /**
     * Sanitize content for safe display
     */
    public function sanitizeContent(string $content, array $options = []): string
    {
        // Remove potential XSS vectors
        $content = preg_replace(self::MALICIOUS_PATTERNS, '', $content);

        // Additional sanitization based on options
        if ($options['strip_html'] ?? false) {
            $content = strip_tags($content);
        }

        if ($options['escape_html'] ?? true) {
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        return $content;
    }

    /**
     * Check if user has permission to access file
     */
    public function authorizeFileAccess(string $filePath, int $userId): bool
    {
        // Check if file belongs to user's topics
        $topicExists = DB::table('topics')
            ->join('units', 'topics.unit_id', '=', 'units.id')
            ->join('subjects', 'units.subject_id', '=', 'subjects.id')
            ->where('subjects.user_id', $userId)
            ->where(function ($query) use ($filePath) {
                $query->where('topics.content_assets', 'like', "%{$filePath}%")
                    ->orWhere('topics.embedded_images', 'like', "%{$filePath}%");
            })
            ->exists();

        if (! $topicExists) {
            $this->logSecurityEvent('unauthorized_file_access', [
                'file_path' => $filePath,
                'user_id' => $userId,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Private helper methods
     */
    private function validateFileSize(UploadedFile $file): array
    {
        $fileType = $this->getFileTypeCategory($file->getMimeType());
        $maxSize = self::MAX_FILE_SIZES[$fileType] ?? self::MAX_FILE_SIZES['document'];

        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of '.$this->formatBytes($maxSize),
            ];
        }

        return ['valid' => true];
    }

    private function validateFileType(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check if mime type is allowed
        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return [
                'valid' => false,
                'error' => "File type '{$mimeType}' is not allowed for security reasons",
            ];
        }

        // Check if extension is dangerous
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            return [
                'valid' => false,
                'error' => "File extension '.{$extension}' is not allowed for security reasons",
            ];
        }

        return [
            'valid' => true,
            'category' => $this->getFileTypeCategory($mimeType),
        ];
    }

    private function validateFileSignature(UploadedFile $file): array
    {
        $fileHandle = fopen($file->getRealPath(), 'rb');
        if (! $fileHandle) {
            return [
                'valid' => false,
                'error' => 'Unable to read file for security validation',
            ];
        }

        $header = fread($fileHandle, 1024);
        fclose($fileHandle);

        // Check for executable signatures
        $executableSignatures = [
            'MZ',      // PE/DOS executable
            "\x7fELF", // ELF executable
            "\xca\xfe\xba\xbe", // Mach-O universal binary
            "PK\x03\x04", // ZIP (could contain executable)
        ];

        foreach ($executableSignatures as $signature) {
            if (str_starts_with($header, $signature)) {
                // Allow ZIP files but scan them more thoroughly
                if ($signature === "PK\x03\x04" && $file->getMimeType() === 'application/zip') {
                    continue;
                }

                return [
                    'valid' => false,
                    'error' => 'File appears to be an executable or suspicious binary',
                ];
            }
        }

        return ['valid' => true];
    }

    private function scanFileContent(UploadedFile $file): array
    {
        $threats = [];
        $securityLevel = 'safe';

        // Only scan text-based files to avoid false positives
        $mimeType = $file->getMimeType();
        $textTypes = ['text/', 'application/json', 'application/xml'];

        $isTextFile = false;
        foreach ($textTypes as $type) {
            if (str_starts_with($mimeType, $type)) {
                $isTextFile = true;
                break;
            }
        }

        if (! $isTextFile) {
            return [
                'safe' => true,
                'threats' => [],
                'security_level' => 'safe',
            ];
        }

        try {
            $content = file_get_contents($file->getRealPath());
            $contentScan = $this->scanContentSecurity($content);

            return $contentScan;

        } catch (\Exception $e) {
            return [
                'safe' => false,
                'threats' => [['type' => 'scan_error', 'message' => $e->getMessage()]],
                'security_level' => 'unknown',
            ];
        }
    }

    private function validateFilename(string $filename): array
    {
        $suspicious = [];

        // Check for suspicious characters
        if (preg_match('/[<>:"|?*\\\\\/]/', $filename)) {
            $suspicious[] = 'Filename contains suspicious characters';
        }

        // Check for very long filename
        if (strlen($filename) > 255) {
            $suspicious[] = 'Filename is too long';
        }

        // Check for hidden file patterns
        if (str_starts_with($filename, '.')) {
            $suspicious[] = 'Hidden file detected';
        }

        return [
            'valid' => empty($suspicious),
            'warning' => implode(', ', $suspicious),
        ];
    }

    private function analyzeContentStructure(string $content): array
    {
        $threats = [];

        // Check for base64 encoded content (could hide malicious payloads)
        if (preg_match('/[A-Za-z0-9+\/]{20,}={0,2}/', $content, $matches)) {
            $decoded = base64_decode($matches[0], true);
            if ($decoded && $this->containsSuspiciousContent($decoded)) {
                $threats[] = [
                    'type' => 'base64_encoded_threat',
                    'severity' => 'medium',
                    'description' => 'Base64 encoded content contains suspicious patterns',
                ];
            }
        }

        // Check for redirects or loops
        $redirectCount = preg_match_all('/location\.href|window\.location|document\.location/i', $content);
        if ($redirectCount > 0) {
            $threats[] = [
                'type' => $redirectCount > 5 ? 'excessive_redirects' : 'redirects_detected',
                'severity' => $redirectCount > 5 ? 'high' : 'medium',
                'description' => $redirectCount > 5 ? 'Content contains excessive redirect attempts' : 'Content contains redirect attempts',
            ];
        }

        // Check for obfuscated code patterns
        if (preg_match('/eval\s*\(|Function\s*\(|setTimeout\s*\(.*string/i', $content)) {
            $threats[] = [
                'type' => 'code_obfuscation',
                'severity' => 'high',
                'description' => 'Content contains potentially obfuscated code',
            ];
        }

        return $threats;
    }

    private function containsSuspiciousContent(string $content): bool
    {
        $suspiciousPatterns = [
            '/eval\(/i',
            '/exec\(/i',
            '/system\(/i',
            '/shell_exec\(/i',
            '/passthru\(/i',
            '/<script/i',
            '/javascript:/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function calculateSecurityLevel(array $threats, int $suspiciousCount): string
    {
        if (empty($threats)) {
            return 'safe';
        }

        $highSeverityCount = count(array_filter($threats, fn ($t) => ($t['severity'] ?? '') === 'high'));

        if ($highSeverityCount > 0) {
            return 'dangerous';
        }

        if ($suspiciousCount > 3) {
            return 'suspicious';
        }

        return 'warning';
    }

    private function validateDomain(string $domain): array
    {
        $warnings = [];

        // Check for IP address instead of domain
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            $warnings[] = 'URL uses IP address instead of domain name';
        }

        // Check for suspicious TLDs
        $suspiciousTlds = ['.tk', '.ml', '.ga', '.cf', '.bit', '.onion'];
        foreach ($suspiciousTlds as $tld) {
            if (str_ends_with($domain, $tld)) {
                $warnings[] = "Domain uses suspicious TLD: {$tld}";
                break;
            }
        }

        // Check for URL shorteners
        $shorteners = ['bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly', 'short.link'];
        if (in_array($domain, $shorteners)) {
            $warnings[] = 'URL uses a URL shortening service';
        }

        return [
            'safe' => empty($warnings),
            'warnings' => $warnings,
        ];
    }

    private function getFileTypeCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (in_array($mimeType, ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'])) {
            return 'archive';
        }

        return 'document';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function logSecurityEvent(string $event, array $data): void
    {
        Log::channel('security')->info($event, array_merge($data, [
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));
    }
}
