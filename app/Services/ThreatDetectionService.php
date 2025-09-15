<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThreatDetectionService
{
    /**
     * Known malicious file signatures (first few bytes)
     */
    public const MALICIOUS_SIGNATURES = [
        // PE (Windows Executable) signatures
        '4d5a' => 'Windows Executable',
        '5a4d' => 'Windows Executable (reverse)',

        // ELF (Linux Executable) signatures
        '7f454c46' => 'Linux Executable',

        // Mach-O (macOS Executable) signatures
        'feedface' => 'macOS Executable',
        'feedfacf' => 'macOS Executable 64-bit',
        'cefaedfe' => 'macOS Executable (reverse)',

        // Java Class files
        'cafebabe' => 'Java Class File',

        // Suspicious archive signatures
        '504b0304' => 'ZIP Archive', // Will need content inspection
        '526172211a' => 'RAR Archive',

        // Script signatures in disguise
        '3c3f706870' => 'PHP Script',
        '3c25' => 'ASP/JSP Script',
        '3c73637269' => 'HTML Script',
    ];

    /**
     * Suspicious patterns in file names
     */
    public const SUSPICIOUS_FILENAME_PATTERNS = [
        '/\.exe\./i',           // Double extension
        '/\.scr\./i',           // Screen saver (often malware)
        '/\.bat\./i',           // Batch file
        '/\.cmd\./i',           // Command file
        '/\.pif\./i',           // Program information file
        '/\.com\./i',           // COM executable
        '/\.(js|vbs|vbe)\./i',  // Script files
        '/\.reg\./i',           // Registry file
        '/\s+\.(exe|scr|bat)$/i', // Hidden executables
        '/\x00/',               // Null bytes
        '/[^\x20-\x7E]/',       // Non-printable characters
    ];

    /**
     * Suspicious content patterns
     */
    public const SUSPICIOUS_CONTENT_PATTERNS = [
        // Malicious URLs and domains
        '/https?:\/\/(.*\.)?(bit\.ly|tinyurl|t\.co|goo\.gl|ow\.ly)\/[a-zA-Z0-9]+/i',
        '/https?:\/\/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/i', // Direct IP URLs

        // Suspicious commands
        '/powershell.*-encodedcommand/i',
        '/cmd\.exe.*\/c/i',
        '/system\(.*\)/i',
        '/exec\(.*\)/i',
        '/eval\(.*\)/i',

        // Base64 encoded suspicious content
        '/[A-Za-z0-9+\/]{50,}={0,2}/i', // Long base64 strings

        // SQL injection patterns
        '/(union|select|insert|update|delete|drop).*from/i',
        '/\s+(or|and)\s+1\s*=\s*1/i',

        // JavaScript malware patterns
        '/document\.write\s*\(/i',
        '/window\.location\s*=/i',
        '/unescape\s*\(/i',
        '/String\.fromCharCode/i',
    ];

    /**
     * Known malicious hash database (simplified)
     * In production, this would connect to threat intelligence feeds
     */
    public const KNOWN_MALICIOUS_HASHES = [
        // These would be actual malware hashes from threat intelligence
        'd41d8cd98f00b204e9800998ecf8427e', // Empty file (example)
        // Add more known malicious file hashes here
    ];

    /**
     * Upload rate tracking for suspicious activity detection
     */
    private const RATE_LIMIT_WINDOW = 3600; // 1 hour

    private const SUSPICIOUS_UPLOAD_THRESHOLD = 50; // files per hour

    private const BLOCKED_IP_CACHE_TTL = 86400; // 24 hours

    /**
     * Scan file for threats and suspicious content
     */
    public function scanFile(UploadedFile $file): array
    {
        $result = [
            'threats_detected' => false,
            'threats' => [],
            'warnings' => [],
            'risk_score' => 0,
            'scan_details' => [],
        ];

        try {
            // Step 1: Filename analysis
            $this->analyzeFilename($file, $result);

            // Step 2: File signature analysis
            $this->analyzeFileSignature($file, $result);

            // Step 3: Hash-based detection
            $this->performHashAnalysis($file, $result);

            // Step 4: Content pattern analysis
            $this->analyzeContentPatterns($file, $result);

            // Step 5: Behavioral analysis
            $this->performBehavioralAnalysis($file, $result);

            // Step 6: Rate limiting and IP analysis
            $this->analyzeUploadBehavior($result);

            // Calculate final threat level
            $this->calculateThreatLevel($result);

            // Log threat detection results
            $this->logThreatDetection($file, $result);

        } catch (\Exception $e) {
            $result['warnings'][] = 'Threat detection scan failed: '.$e->getMessage();
            $result['risk_score'] += 5;

            Log::error('Threat detection error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Check if IP address is currently blocked
     */
    public function isIpBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    /**
     * Block IP address for suspicious activity
     */
    public function blockIp(string $ip, string $reason, ?int $duration = null): void
    {
        $duration = $duration ?? self::BLOCKED_IP_CACHE_TTL;

        Cache::put("blocked_ip:{$ip}", [
            'blocked_at' => now()->toISOString(),
            'reason' => $reason,
            'duration' => $duration,
        ], $duration);

        Log::warning('IP address blocked for suspicious activity', [
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $duration,
        ]);
    }

    /**
     * Get upload rate for IP address
     */
    public function getUploadRate(string $ip): int
    {
        $cacheKey = "upload_rate:{$ip}:".floor(time() / self::RATE_LIMIT_WINDOW);

        return Cache::get($cacheKey, 0);
    }

    /**
     * Increment upload rate for IP address
     */
    public function incrementUploadRate(string $ip): int
    {
        $cacheKey = "upload_rate:{$ip}:".floor(time() / self::RATE_LIMIT_WINDOW);
        $currentRate = Cache::get($cacheKey, 0);
        $newRate = $currentRate + 1;

        Cache::put($cacheKey, $newRate, self::RATE_LIMIT_WINDOW);

        return $newRate;
    }

    /**
     * Analyze filename for suspicious patterns
     */
    private function analyzeFilename(UploadedFile $file, array &$result): void
    {
        $filename = $file->getClientOriginalName();
        $result['scan_details']['filename_analysis'] = 'passed';

        foreach (self::SUSPICIOUS_FILENAME_PATTERNS as $pattern) {
            if (preg_match($pattern, $filename)) {
                $result['threats'][] = 'Suspicious filename pattern detected';
                $result['threats_detected'] = true;
                $result['risk_score'] += 10;
                $result['scan_details']['filename_analysis'] = 'failed';
                break;
            }
        }

        // Check for extremely long filenames (potential buffer overflow attempt)
        if (strlen($filename) > 255) {
            $result['warnings'][] = 'Unusually long filename detected';
            $result['risk_score'] += 2;
        }

        // Check for hidden file indicators
        if (str_starts_with($filename, '.') && strlen($filename) > 1) {
            $result['warnings'][] = 'Hidden file detected';
            $result['risk_score'] += 1;
        }
    }

    /**
     * Analyze file signature (magic numbers)
     */
    private function analyzeFileSignature(UploadedFile $file, array &$result): void
    {
        $result['scan_details']['signature_analysis'] = 'passed';

        try {
            $fileHandle = fopen($file->getRealPath(), 'rb');
            $header = fread($fileHandle, 16); // Read first 16 bytes
            fclose($fileHandle);

            $headerHex = bin2hex($header);

            foreach (self::MALICIOUS_SIGNATURES as $signature => $description) {
                if (str_starts_with($headerHex, $signature)) {
                    $result['threats'][] = "Dangerous file type detected: {$description}";
                    $result['threats_detected'] = true;
                    $result['risk_score'] += 15;
                    $result['scan_details']['signature_analysis'] = 'failed';
                    break;
                }
            }

            // Check for embedded executables in other file types
            $this->checkForEmbeddedExecutables($headerHex, $result);

        } catch (\Exception $e) {
            $result['warnings'][] = 'Could not analyze file signature';
            $result['risk_score'] += 1;
        }
    }

    /**
     * Perform hash-based threat detection
     */
    private function performHashAnalysis(UploadedFile $file, array &$result): void
    {
        $result['scan_details']['hash_analysis'] = 'passed';

        try {
            $md5Hash = md5_file($file->getRealPath());
            $sha256Hash = hash_file('sha256', $file->getRealPath());

            // Check against known malicious hashes
            if (in_array($md5Hash, self::KNOWN_MALICIOUS_HASHES)) {
                $result['threats'][] = 'File matches known malware signature';
                $result['threats_detected'] = true;
                $result['risk_score'] += 20;
                $result['scan_details']['hash_analysis'] = 'failed';
            }

            // In production, you would query external threat intelligence services here
            // For example: VirusTotal API, Hybrid Analysis, etc.

            $result['scan_details']['file_hashes'] = [
                'md5' => $md5Hash,
                'sha256' => $sha256Hash,
            ];

        } catch (\Exception $e) {
            $result['warnings'][] = 'Could not perform hash analysis';
            $result['risk_score'] += 1;
        }
    }

    /**
     * Analyze content for suspicious patterns
     */
    private function analyzeContentPatterns(UploadedFile $file, array &$result): void
    {
        $result['scan_details']['content_analysis'] = 'passed';

        try {
            $fileSize = $file->getSize();

            // Only scan first part of large files for performance
            $scanSize = min($fileSize, 1024 * 1024); // 1MB max

            $fileHandle = fopen($file->getRealPath(), 'rb');
            $content = fread($fileHandle, $scanSize);
            fclose($fileHandle);

            // Convert to string for pattern matching
            $contentString = bin2hex($content);
            $textContent = $this->extractReadableText($content);

            foreach (self::SUSPICIOUS_CONTENT_PATTERNS as $pattern) {
                if (preg_match($pattern, $textContent)) {
                    $result['threats'][] = 'Suspicious content pattern detected';
                    $result['threats_detected'] = true;
                    $result['risk_score'] += 8;
                    $result['scan_details']['content_analysis'] = 'failed';
                    break;
                }
            }

            // Check for high entropy (potentially encrypted/obfuscated content)
            $entropy = $this->calculateEntropy($content);
            if ($entropy > 7.8) { // High entropy threshold
                $result['warnings'][] = 'High entropy content detected (possible obfuscation)';
                $result['risk_score'] += 3;
            }

        } catch (\Exception $e) {
            $result['warnings'][] = 'Could not analyze file content';
            $result['risk_score'] += 1;
        }
    }

    /**
     * Perform behavioral analysis
     */
    private function performBehavioralAnalysis(UploadedFile $file, array &$result): void
    {
        $result['scan_details']['behavioral_analysis'] = 'passed';

        // Analyze file size anomalies
        $fileSize = $file->getSize();
        $extension = strtolower($file->getClientOriginalExtension());

        // Detect suspiciously small files with dangerous extensions
        if ($fileSize < 100 && in_array($extension, ['exe', 'dll', 'bat', 'cmd'])) {
            $result['warnings'][] = 'Unusually small executable file';
            $result['risk_score'] += 5;
        }

        // Detect files with mismatched size expectations
        $expectedSizeRanges = [
            'txt' => [1, 10 * 1024 * 1024], // 1 byte to 10MB
            'pdf' => [1024, 100 * 1024 * 1024], // 1KB to 100MB
            'jpg' => [1024, 50 * 1024 * 1024], // 1KB to 50MB
            'mp4' => [10 * 1024, 500 * 1024 * 1024], // 10KB to 500MB
        ];

        if (isset($expectedSizeRanges[$extension])) {
            [$minSize, $maxSize] = $expectedSizeRanges[$extension];
            if ($fileSize < $minSize || $fileSize > $maxSize) {
                $result['warnings'][] = "File size unusual for {$extension} format";
                $result['risk_score'] += 2;
            }
        }
    }

    /**
     * Analyze upload behavior for rate limiting and suspicious patterns
     */
    private function analyzeUploadBehavior(array &$result): void
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();

        $result['scan_details']['behavioral_analysis'] = 'passed';

        // Check if IP is already blocked
        if ($this->isIpBlocked($ip)) {
            $result['threats'][] = 'Upload from blocked IP address';
            $result['threats_detected'] = true;
            $result['risk_score'] += 25;

            return;
        }

        // Increment and check upload rate
        $uploadRate = $this->incrementUploadRate($ip);

        if ($uploadRate > self::SUSPICIOUS_UPLOAD_THRESHOLD) {
            $result['warnings'][] = 'High upload frequency detected';
            $result['risk_score'] += 5;

            // Block IP if rate is extremely high
            if ($uploadRate > self::SUSPICIOUS_UPLOAD_THRESHOLD * 2) {
                $this->blockIp($ip, 'Excessive upload rate detected', 7200); // 2 hours
                $result['threats'][] = 'IP blocked for excessive upload activity';
                $result['threats_detected'] = true;
                $result['risk_score'] += 20;
            }
        }

        // Check for suspicious user agents
        if (empty($userAgent) || strlen($userAgent) < 10) {
            $result['warnings'][] = 'Missing or suspicious user agent';
            $result['risk_score'] += 2;
        }

        // Check for automated/bot patterns
        $botPatterns = [
            '/bot/i', '/crawler/i', '/spider/i', '/curl/i', '/wget/i',
            '/python/i', '/script/i', '/automated/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $result['warnings'][] = 'Automated upload detected';
                $result['risk_score'] += 3;
                break;
            }
        }
    }

    /**
     * Calculate final threat level
     */
    private function calculateThreatLevel(array &$result): void
    {
        $riskScore = $result['risk_score'];

        if ($riskScore >= 20) {
            $result['threat_level'] = 'critical';
        } elseif ($riskScore >= 15) {
            $result['threat_level'] = 'high';
        } elseif ($riskScore >= 10) {
            $result['threat_level'] = 'medium';
        } elseif ($riskScore >= 5) {
            $result['threat_level'] = 'low';
        } else {
            $result['threat_level'] = 'minimal';
        }
    }

    /**
     * Check for embedded executables in file headers
     */
    private function checkForEmbeddedExecutables(string $headerHex, array &$result): void
    {
        // Look for executable signatures deeper in the file
        $executableSignatures = ['4d5a', '7f454c46', 'feedface'];

        foreach ($executableSignatures as $signature) {
            if (strpos($headerHex, $signature) !== false && strpos($headerHex, $signature) > 0) {
                $result['warnings'][] = 'Potential embedded executable detected';
                $result['risk_score'] += 5;
                break;
            }
        }
    }

    /**
     * Extract readable text from binary content
     */
    private function extractReadableText(string $content): string
    {
        // Extract printable ASCII characters
        return preg_replace('/[^\x20-\x7E]/', '', $content);
    }

    /**
     * Calculate entropy of content (measure of randomness)
     */
    private function calculateEntropy(string $content): float
    {
        $frequencies = array_count_values(str_split($content));
        $length = strlen($content);
        $entropy = 0;

        foreach ($frequencies as $frequency) {
            $probability = $frequency / $length;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }

    /**
     * Log threat detection results
     */
    private function logThreatDetection(UploadedFile $file, array $result): void
    {
        $logLevel = $result['threats_detected'] ? 'warning' : 'info';

        Log::channel('security')->{$logLevel}('File threat detection completed', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'threat_level' => $result['threat_level'] ?? 'unknown',
            'threats_detected' => $result['threats_detected'],
            'risk_score' => $result['risk_score'],
            'threats' => $result['threats'],
            'warnings' => $result['warnings'],
            'user_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
