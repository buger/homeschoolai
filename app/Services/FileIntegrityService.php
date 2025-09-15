<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FileIntegrityService
{
    /**
     * Known file format headers and their expected properties
     */
    public const FILE_FORMAT_SPECS = [
        'pdf' => [
            'header' => '25504446', // %PDF
            'footer_pattern' => '%%EOF',
            'min_size' => 100,
            'max_header_offset' => 0,
        ],
        'jpg' => [
            'header' => 'ffd8ff',
            'footer' => 'ffd9',
            'min_size' => 100,
            'max_header_offset' => 0,
        ],
        'jpeg' => [
            'header' => 'ffd8ff',
            'footer' => 'ffd9',
            'min_size' => 100,
            'max_header_offset' => 0,
        ],
        'png' => [
            'header' => '89504e470d0a1a0a',
            'footer' => '49454e44ae426082',
            'min_size' => 100,
            'max_header_offset' => 0,
        ],
        'gif' => [
            'header' => ['474946383761', '474946383961'], // GIF87a, GIF89a
            'footer' => '003b',
            'min_size' => 100,
            'max_header_offset' => 0,
        ],
        'mp4' => [
            'header_patterns' => ['66747970', '00000020667479706d703432'], // ftyp
            'min_size' => 1000,
            'max_header_offset' => 8,
        ],
        'zip' => [
            'header' => '504b0304',
            'min_size' => 22,
            'max_header_offset' => 0,
        ],
        'docx' => [
            'header' => '504b0304', // ZIP format
            'content_signature' => 'word/document.xml',
            'min_size' => 1000,
            'max_header_offset' => 0,
        ],
        'xlsx' => [
            'header' => '504b0304', // ZIP format
            'content_signature' => 'xl/workbook.xml',
            'min_size' => 1000,
            'max_header_offset' => 0,
        ],
        'pptx' => [
            'header' => '504b0304', // ZIP format
            'content_signature' => 'ppt/presentation.xml',
            'min_size' => 1000,
            'max_header_offset' => 0,
        ],
    ];

    /**
     * Corrupted file indicators
     */
    public const CORRUPTION_INDICATORS = [
        'truncated_header' => 'File header appears truncated',
        'missing_footer' => 'File footer missing or corrupted',
        'invalid_structure' => 'File structure is invalid',
        'size_mismatch' => 'File size does not match format expectations',
        'checksum_failure' => 'File checksum validation failed',
        'encoding_error' => 'File encoding appears corrupted',
    ];

    /**
     * Validate file integrity and structure
     */
    public function validateFile(UploadedFile $file): array
    {
        $result = [
            'valid' => true,
            'reason' => null,
            'integrity_checks' => [],
            'warnings' => [],
            'file_info' => [],
        ];

        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $fileSize = $file->getSize();
            $filePath = $file->getRealPath();

            $result['file_info'] = [
                'extension' => $extension,
                'size' => $fileSize,
                'mime_type' => $file->getMimeType(),
            ];

            // Step 1: Basic file accessibility
            $this->checkFileAccessibility($filePath, $result);

            // Step 2: Format-specific validation
            if (isset(self::FILE_FORMAT_SPECS[$extension])) {
                $this->validateFileFormat($filePath, $extension, $result);
            }

            // Step 3: Content consistency checks
            $this->performContentConsistencyChecks($file, $result);

            // Step 4: Corruption detection
            $this->detectCorruption($filePath, $fileSize, $result);

            // Step 5: File completeness verification
            $this->verifyFileCompleteness($filePath, $extension, $result);

            // Log integrity check results
            $this->logIntegrityCheck($file, $result);

        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['reason'] = 'Integrity check failed: '.$e->getMessage();

            Log::error('File integrity validation error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Generate file checksum for future integrity verification
     */
    public function generateChecksum(UploadedFile $file): array
    {
        $filePath = $file->getRealPath();

        return [
            'md5' => hash_file('md5', $filePath),
            'sha256' => hash_file('sha256', $filePath),
            'crc32' => hash_file('crc32', $filePath),
            'file_size' => $file->getSize(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Verify file against stored checksum
     */
    public function verifyChecksum(string $filePath, array $storedChecksum): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $currentChecksum = [
            'md5' => hash_file('md5', $filePath),
            'sha256' => hash_file('sha256', $filePath),
            'file_size' => filesize($filePath),
        ];

        return $currentChecksum['md5'] === $storedChecksum['md5'] &&
               $currentChecksum['sha256'] === $storedChecksum['sha256'] &&
               $currentChecksum['file_size'] === $storedChecksum['file_size'];
    }

    /**
     * Detect file tampering by analyzing metadata and structure
     */
    public function detectTampering(UploadedFile $file): array
    {
        $result = [
            'tampered' => false,
            'indicators' => [],
            'confidence' => 0,
        ];

        try {
            $filePath = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());

            // Check for timestamp anomalies
            $this->checkTimestampAnomalies($file, $result);

            // Check for metadata inconsistencies
            $this->checkMetadataConsistency($file, $result);

            // Check for structural anomalies
            $this->checkStructuralAnomalies($filePath, $extension, $result);

            // Check for embedded content anomalies
            $this->checkEmbeddedContentAnomalies($filePath, $extension, $result);

        } catch (\Exception $e) {
            Log::warning('File tampering detection failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Check basic file accessibility
     */
    private function checkFileAccessibility(string $filePath, array &$result): void
    {
        $result['integrity_checks']['accessibility'] = 'passed';

        if (! file_exists($filePath)) {
            $result['valid'] = false;
            $result['reason'] = 'File does not exist';
            $result['integrity_checks']['accessibility'] = 'failed';

            return;
        }

        if (! is_readable($filePath)) {
            $result['valid'] = false;
            $result['reason'] = 'File is not readable';
            $result['integrity_checks']['accessibility'] = 'failed';

            return;
        }

        if (filesize($filePath) === 0) {
            $result['valid'] = false;
            $result['reason'] = 'File is empty';
            $result['integrity_checks']['accessibility'] = 'failed';

            return;
        }
    }

    /**
     * Validate file format against known specifications
     */
    private function validateFileFormat(string $filePath, string $extension, array &$result): void
    {
        $spec = self::FILE_FORMAT_SPECS[$extension];
        $result['integrity_checks']['format_validation'] = 'passed';

        try {
            $fileHandle = fopen($filePath, 'rb');
            $fileSize = filesize($filePath);

            // Check minimum file size
            if ($fileSize < $spec['min_size']) {
                $result['valid'] = false;
                $result['reason'] = "File too small for {$extension} format";
                $result['integrity_checks']['format_validation'] = 'failed';
                fclose($fileHandle);

                return;
            }

            // Read header for validation
            $headerSize = max(32, $spec['max_header_offset'] + 16);
            $header = fread($fileHandle, $headerSize);
            $headerHex = bin2hex($header);

            // Validate header signature
            $this->validateHeaderSignature($headerHex, $spec, $extension, $result);

            // Validate footer if specified
            if (isset($spec['footer']) || isset($spec['footer_pattern'])) {
                $this->validateFooterSignature($fileHandle, $fileSize, $spec, $result);
            }

            // Special validation for compound document formats
            if (isset($spec['content_signature'])) {
                $this->validateContentSignature($filePath, $spec['content_signature'], $result);
            }

            fclose($fileHandle);

        } catch (\Exception $e) {
            $result['warnings'][] = "Could not validate {$extension} format: ".$e->getMessage();
            if (isset($fileHandle) && is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }
    }

    /**
     * Validate header signature
     */
    private function validateHeaderSignature(string $headerHex, array $spec, string $extension, array &$result): void
    {
        $headerValid = false;

        if (isset($spec['header'])) {
            $expectedHeaders = is_array($spec['header']) ? $spec['header'] : [$spec['header']];

            foreach ($expectedHeaders as $expectedHeader) {
                if (str_starts_with($headerHex, $expectedHeader)) {
                    $headerValid = true;
                    break;
                }
            }
        } elseif (isset($spec['header_patterns'])) {
            foreach ($spec['header_patterns'] as $pattern) {
                if (strpos($headerHex, $pattern) !== false) {
                    $headerValid = true;
                    break;
                }
            }
        }

        if (! $headerValid) {
            $result['valid'] = false;
            $result['reason'] = "Invalid {$extension} file header";
            $result['integrity_checks']['format_validation'] = 'failed';
        }
    }

    /**
     * Validate footer signature
     */
    private function validateFooterSignature($fileHandle, int $fileSize, array $spec, array &$result): void
    {
        try {
            if (isset($spec['footer'])) {
                $footerLength = strlen($spec['footer']) / 2; // Convert hex to bytes
                fseek($fileHandle, -$footerLength, SEEK_END);
                $footer = fread($fileHandle, $footerLength);
                $footerHex = bin2hex($footer);

                if ($footerHex !== $spec['footer']) {
                    $result['warnings'][] = 'File footer signature mismatch';
                }
            } elseif (isset($spec['footer_pattern'])) {
                // Read last few bytes to check for pattern
                fseek($fileHandle, -100, SEEK_END);
                $endContent = fread($fileHandle, 100);

                if (strpos($endContent, $spec['footer_pattern']) === false) {
                    $result['warnings'][] = 'Expected footer pattern not found';
                }
            }
        } catch (\Exception $e) {
            $result['warnings'][] = 'Could not validate file footer';
        }
    }

    /**
     * Validate content signature for compound documents
     */
    private function validateContentSignature(string $filePath, string $signature, array &$result): void
    {
        // For ZIP-based formats (DOCX, XLSX, PPTX), we would need to extract and check
        // This is a simplified check - in production, you'd use proper ZIP libraries
        $result['warnings'][] = 'Content signature validation requires ZIP extraction (not implemented)';
    }

    /**
     * Perform content consistency checks
     */
    private function performContentConsistencyChecks(UploadedFile $file, array &$result): void
    {
        $result['integrity_checks']['content_consistency'] = 'passed';

        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            // Check MIME type consistency with extension
            $expectedMimeTypes = $this->getExpectedMimeTypes($extension);
            if (! empty($expectedMimeTypes) && ! in_array($mimeType, $expectedMimeTypes)) {
                $result['warnings'][] = "MIME type '{$mimeType}' doesn't match extension '{$extension}'";
            }

            // Perform format-specific consistency checks
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $this->checkJpegConsistency($file->getRealPath(), $result);
                    break;
                case 'png':
                    $this->checkPngConsistency($file->getRealPath(), $result);
                    break;
                case 'pdf':
                    $this->checkPdfConsistency($file->getRealPath(), $result);
                    break;
            }

        } catch (\Exception $e) {
            $result['warnings'][] = 'Content consistency check failed: '.$e->getMessage();
        }
    }

    /**
     * Detect various forms of file corruption
     */
    private function detectCorruption(string $filePath, int $fileSize, array &$result): void
    {
        $result['integrity_checks']['corruption_detection'] = 'passed';

        try {
            // Check for suspicious file size patterns
            if ($fileSize > 0 && $fileSize < 10) {
                $result['warnings'][] = 'Unusually small file size detected';
            }

            // Check for null byte sequences (potential corruption)
            $this->checkForNullBytes($filePath, $result);

            // Check for repeated patterns (potential corruption)
            $this->checkForRepeatedPatterns($filePath, $result);

            // Check file density (ratio of printable to non-printable characters)
            $this->checkFileDensity($filePath, $result);

        } catch (\Exception $e) {
            $result['warnings'][] = 'Corruption detection failed: '.$e->getMessage();
        }
    }

    /**
     * Verify file completeness
     */
    private function verifyFileCompleteness(string $filePath, string $extension, array &$result): void
    {
        $result['integrity_checks']['completeness'] = 'passed';

        try {
            // Check if file appears to be truncated
            $fileSize = filesize($filePath);
            $fileHandle = fopen($filePath, 'rb');

            // Read last few bytes to check for proper termination
            if ($fileSize > 10) {
                fseek($fileHandle, -10, SEEK_END);
                $endBytes = fread($fileHandle, 10);

                // Check for premature termination indicators
                if (strlen($endBytes) < 10) {
                    $result['warnings'][] = 'File may be truncated';
                }
            }

            fclose($fileHandle);

        } catch (\Exception $e) {
            $result['warnings'][] = 'Completeness verification failed: '.$e->getMessage();
        }
    }

    /**
     * Check JPEG file consistency
     */
    private function checkJpegConsistency(string $filePath, array &$result): void
    {
        $fileHandle = fopen($filePath, 'rb');
        $header = fread($fileHandle, 4);

        // JPEG should start with FFD8 and end with FFD9
        if (bin2hex($header) !== 'ffd8ffe0' && ! str_starts_with(bin2hex($header), 'ffd8ff')) {
            $result['warnings'][] = 'JPEG header structure appears invalid';
        }

        fclose($fileHandle);
    }

    /**
     * Check PNG file consistency
     */
    private function checkPngConsistency(string $filePath, array &$result): void
    {
        $fileHandle = fopen($filePath, 'rb');
        $header = fread($fileHandle, 8);

        // PNG signature
        if (bin2hex($header) !== '89504e470d0a1a0a') {
            $result['warnings'][] = 'PNG header signature invalid';
        }

        fclose($fileHandle);
    }

    /**
     * Check PDF file consistency
     */
    private function checkPdfConsistency(string $filePath, array &$result): void
    {
        $content = file_get_contents($filePath, false, null, 0, 1024);

        if (! str_starts_with($content, '%PDF-')) {
            $result['warnings'][] = 'PDF header signature invalid';
        }

        // Check for PDF version
        if (! preg_match('/%PDF-(\d+\.\d+)/', $content, $matches)) {
            $result['warnings'][] = 'PDF version information missing';
        }
    }

    /**
     * Check for null bytes in file
     */
    private function checkForNullBytes(string $filePath, array &$result): void
    {
        $fileHandle = fopen($filePath, 'rb');
        $sampleSize = min(1024, filesize($filePath));
        $sample = fread($fileHandle, $sampleSize);
        fclose($fileHandle);

        $nullCount = substr_count($sample, "\0");
        $nullRatio = $nullCount / $sampleSize;

        if ($nullRatio > 0.1) { // More than 10% null bytes
            $result['warnings'][] = 'High null byte density detected (possible corruption)';
        }
    }

    /**
     * Check for repeated patterns that might indicate corruption
     */
    private function checkForRepeatedPatterns(string $filePath, array &$result): void
    {
        $fileHandle = fopen($filePath, 'rb');
        $sample = fread($fileHandle, 1024);
        fclose($fileHandle);

        // Check for repeated 4-byte patterns
        for ($i = 0; $i < strlen($sample) - 8; $i += 4) {
            $pattern = substr($sample, $i, 4);
            $nextPattern = substr($sample, $i + 4, 4);

            if ($pattern === $nextPattern && strlen($pattern) === 4) {
                $result['warnings'][] = 'Repeated byte patterns detected (possible corruption)';
                break;
            }
        }
    }

    /**
     * Check file density (printable vs non-printable characters)
     */
    private function checkFileDensity(string $filePath, array &$result): void
    {
        $fileHandle = fopen($filePath, 'rb');
        $sample = fread($fileHandle, 1024);
        fclose($fileHandle);

        $printableCount = 0;
        $totalCount = strlen($sample);

        for ($i = 0; $i < $totalCount; $i++) {
            $char = ord($sample[$i]);
            if ($char >= 32 && $char <= 126) {
                $printableCount++;
            }
        }

        $printableRatio = $printableCount / $totalCount;

        // This is very format-dependent - adjust thresholds based on file type
        if ($printableRatio > 0.9) {
            $result['warnings'][] = 'Unusually high printable character density';
        } elseif ($printableRatio < 0.01) {
            $result['warnings'][] = 'Unusually low printable character density';
        }
    }

    /**
     * Get expected MIME types for file extension
     */
    private function getExpectedMimeTypes(string $extension): array
    {
        $mimeMap = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'mp4' => ['video/mp4'],
            'mp3' => ['audio/mpeg', 'audio/mp3'],
            'zip' => ['application/zip'],
        ];

        return $mimeMap[$extension] ?? [];
    }

    /**
     * Check timestamp anomalies for tampering detection
     */
    private function checkTimestampAnomalies(UploadedFile $file, array &$result): void
    {
        $filePath = $file->getRealPath();
        $stats = stat($filePath);

        $mtime = $stats['mtime'];
        $ctime = $stats['ctime'];
        $now = time();

        // Check for future timestamps
        if ($mtime > $now || $ctime > $now) {
            $result['indicators'][] = 'File has future timestamp';
            $result['confidence'] += 3;
        }

        // Check for timestamp inconsistencies
        if (abs($mtime - $ctime) > 86400) { // More than 1 day difference
            $result['indicators'][] = 'Unusual timestamp difference between modification and creation';
            $result['confidence'] += 2;
        }
    }

    /**
     * Check metadata consistency
     */
    private function checkMetadataConsistency(UploadedFile $file, array &$result): void
    {
        $declaredSize = $file->getSize();
        $actualSize = filesize($file->getRealPath());

        if ($declaredSize !== $actualSize) {
            $result['indicators'][] = 'File size mismatch between declared and actual';
            $result['confidence'] += 5;
            $result['tampered'] = true;
        }
    }

    /**
     * Check structural anomalies
     */
    private function checkStructuralAnomalies(string $filePath, string $extension, array &$result): void
    {
        // This would involve deep structural analysis specific to each file format
        // For brevity, implementing basic checks only

        if (isset(self::FILE_FORMAT_SPECS[$extension])) {
            $fileHandle = fopen($filePath, 'rb');
            $header = fread($fileHandle, 16);
            fclose($fileHandle);

            // Check if header matches expected format
            $spec = self::FILE_FORMAT_SPECS[$extension];
            if (isset($spec['header'])) {
                $expectedHeader = is_array($spec['header']) ? $spec['header'][0] : $spec['header'];
                if (! str_starts_with(bin2hex($header), $expectedHeader)) {
                    $result['indicators'][] = 'File header does not match declared format';
                    $result['confidence'] += 4;
                }
            }
        }
    }

    /**
     * Check embedded content anomalies
     */
    private function checkEmbeddedContentAnomalies(string $filePath, string $extension, array &$result): void
    {
        // Look for suspicious embedded content or steganography indicators
        $fileHandle = fopen($filePath, 'rb');
        $sample = fread($fileHandle, 4096);
        fclose($fileHandle);

        // Check for embedded executable signatures
        $executableSigs = ['4d5a', '7f454c46', 'feedface'];
        $sampleHex = bin2hex($sample);

        foreach ($executableSigs as $sig) {
            if (strpos($sampleHex, $sig) !== false) {
                $result['indicators'][] = 'Embedded executable signature detected';
                $result['confidence'] += 6;
                $result['tampered'] = true;
                break;
            }
        }
    }

    /**
     * Log integrity check results
     */
    private function logIntegrityCheck(UploadedFile $file, array $result): void
    {
        Log::channel('security')->info('File integrity check completed', [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'extension' => strtolower($file->getClientOriginalExtension()),
            'integrity_valid' => $result['valid'],
            'checks_performed' => array_keys($result['integrity_checks']),
            'warnings_count' => count($result['warnings']),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
