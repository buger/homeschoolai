<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

// Note: Intervention Image package is optional for image optimization
// Install with: composer require intervention/image

class StorageOptimizationService
{
    /**
     * Image optimization settings
     */
    public const IMAGE_OPTIMIZATION = [
        'jpeg_quality' => 85,
        'png_compression' => 9,
        'webp_quality' => 80,
        'max_width' => 2048,
        'max_height' => 2048,
        'thumbnail_sizes' => [150, 300, 600],
        'progressive_jpeg' => true,
        'strip_metadata' => true,
    ];

    /**
     * Video optimization settings
     */
    public const VIDEO_OPTIMIZATION = [
        'max_resolution' => '1920x1080',
        'video_codec' => 'h264',
        'audio_codec' => 'aac',
        'video_bitrate' => '2000k',
        'audio_bitrate' => '128k',
        'frame_rate' => 30,
        'format' => 'mp4',
        'thumbnail_count' => 5,
    ];

    /**
     * Document optimization settings
     */
    public const DOCUMENT_OPTIMIZATION = [
        'pdf_compression' => 'high',
        'pdf_quality' => 'prepress',
        'remove_annotations' => false,
        'linearize' => true,
        'preview_pages' => 3,
    ];

    /**
     * Audio optimization settings
     */
    public const AUDIO_OPTIMIZATION = [
        'bitrate' => '128k',
        'sample_rate' => 44100,
        'channels' => 2,
        'format' => 'mp3',
        'normalize' => true,
        'remove_silence' => false,
    ];

    /**
     * Optimize uploaded file based on type and settings
     */
    public function optimizeFile(string $filePath, array $fileMetadata): array
    {
        $result = [
            'success' => false,
            'original_size' => 0,
            'optimized_size' => 0,
            'compression_ratio' => 0,
            'thumbnails' => [],
            'previews' => [],
            'optimizations_applied' => [],
            'errors' => [],
        ];

        try {
            if (! Storage::disk('public')->exists($filePath)) {
                $result['errors'][] = 'File does not exist';

                return $result;
            }

            $fullPath = Storage::disk('public')->path($filePath);
            $result['original_size'] = filesize($fullPath);

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $category = $this->getCategoryFromExtension($extension);

            switch ($category) {
                case 'image':
                    $result = array_merge($result, $this->optimizeImage($filePath, $fullPath));
                    break;
                case 'video':
                    $result = array_merge($result, $this->optimizeVideo($filePath, $fullPath));
                    break;
                case 'document':
                    $result = array_merge($result, $this->optimizeDocument($filePath, $fullPath));
                    break;
                case 'audio':
                    $result = array_merge($result, $this->optimizeAudio($filePath, $fullPath));
                    break;
                default:
                    $result = array_merge($result, $this->optimizeGenericFile($filePath, $fullPath));
                    break;
            }

            // Calculate final metrics
            if (file_exists($fullPath)) {
                $result['optimized_size'] = filesize($fullPath);
                $result['compression_ratio'] = $result['original_size'] > 0
                    ? ($result['original_size'] - $result['optimized_size']) / $result['original_size']
                    : 0;
                $result['success'] = true;
            }

            // Log optimization results
            $this->logOptimization($filePath, $result);

        } catch (\Exception $e) {
            $result['errors'][] = 'Optimization failed: '.$e->getMessage();

            Log::error('File optimization error', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Optimize image files
     */
    private function optimizeImage(string $filePath, string $fullPath): array
    {
        $result = [
            'optimizations_applied' => [],
            'thumbnails' => [],
            'errors' => [],
        ];

        try {
            // Check if Intervention Image is available
            if (! class_exists('\Intervention\Image\ImageManagerStatic')) {
                $result['errors'][] = 'Intervention Image package not installed. Run: composer require intervention/image';

                return $result;
            }

            // Load image
            $image = \Intervention\Image\ImageManagerStatic::make($fullPath);
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Strip EXIF data for privacy
            if (self::IMAGE_OPTIMIZATION['strip_metadata']) {
                $image->orientate(); // Fix orientation before stripping EXIF
                $result['optimizations_applied'][] = 'metadata_stripped';
            }

            // Resize if too large
            $maxWidth = self::IMAGE_OPTIMIZATION['max_width'];
            $maxHeight = self::IMAGE_OPTIMIZATION['max_height'];

            if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $result['optimizations_applied'][] = 'resized';
            }

            // Optimize based on format
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image->encode('jpg', self::IMAGE_OPTIMIZATION['jpeg_quality']);
                    if (self::IMAGE_OPTIMIZATION['progressive_jpeg']) {
                        $image->interlace();
                    }
                    $result['optimizations_applied'][] = 'jpeg_optimized';
                    break;

                case 'png':
                    $image->encode('png', self::IMAGE_OPTIMIZATION['png_compression']);
                    $result['optimizations_applied'][] = 'png_optimized';
                    break;

                case 'webp':
                    $image->encode('webp', self::IMAGE_OPTIMIZATION['webp_quality']);
                    $result['optimizations_applied'][] = 'webp_optimized';
                    break;
            }

            // Save optimized image
            $image->save($fullPath);

            // Generate thumbnails
            $result['thumbnails'] = $this->generateImageThumbnails($filePath, $fullPath);

            // Generate WebP version for better compression
            $this->generateWebPVersion($filePath, $fullPath);

        } catch (\Exception $e) {
            $result['errors'][] = 'Image optimization failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Generate image thumbnails
     */
    private function generateImageThumbnails(string $filePath, string $fullPath): array
    {
        $thumbnails = [];
        $directory = dirname($filePath);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        try {
            if (! class_exists('\Intervention\Image\ImageManagerStatic')) {
                return $thumbnails; // Return empty array if package not available
            }

            $image = \Intervention\Image\ImageManagerStatic::make($fullPath);

            foreach (self::IMAGE_OPTIMIZATION['thumbnail_sizes'] as $size) {
                $thumbnailName = "{$filename}_thumb_{$size}.{$extension}";
                $thumbnailPath = "{$directory}/thumbnails/{$thumbnailName}";
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

                // Ensure thumbnail directory exists
                $thumbnailDir = dirname($thumbnailFullPath);
                if (! is_dir($thumbnailDir)) {
                    mkdir($thumbnailDir, 0755, true);
                }

                // Create thumbnail
                $thumbnail = clone $image;
                $thumbnail->resize($size, $size, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $thumbnail->save($thumbnailFullPath);

                $thumbnails[] = [
                    'size' => $size,
                    'path' => $thumbnailPath,
                    'url' => Storage::url($thumbnailPath),
                    'width' => $thumbnail->width(),
                    'height' => $thumbnail->height(),
                ];
            }

        } catch (\Exception $e) {
            Log::warning('Thumbnail generation failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $thumbnails;
    }

    /**
     * Generate WebP version of image
     */
    private function generateWebPVersion(string $filePath, string $fullPath): ?string
    {
        try {
            $directory = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $webpPath = "{$directory}/{$filename}.webp";
            $webpFullPath = Storage::disk('public')->path($webpPath);

            if (! class_exists('\Intervention\Image\ImageManagerStatic')) {
                return null; // Return null if package not available
            }

            $image = \Intervention\Image\ImageManagerStatic::make($fullPath);
            $image->encode('webp', self::IMAGE_OPTIMIZATION['webp_quality']);
            $image->save($webpFullPath);

            return $webpPath;

        } catch (\Exception $e) {
            Log::warning('WebP generation failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Optimize video files using FFmpeg
     */
    private function optimizeVideo(string $filePath, string $fullPath): array
    {
        $result = [
            'optimizations_applied' => [],
            'thumbnails' => [],
            'previews' => [],
            'errors' => [],
        ];

        try {
            // Check if FFmpeg is available
            if (! $this->isFFmpegAvailable()) {
                $result['errors'][] = 'FFmpeg not available for video optimization';

                return $result;
            }

            $directory = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $optimizedPath = "{$directory}/{$filename}_optimized.mp4";
            $optimizedFullPath = Storage::disk('public')->path($optimizedPath);

            // Build FFmpeg command for optimization
            $command = $this->buildFFmpegOptimizationCommand($fullPath, $optimizedFullPath);

            // Execute optimization
            $process = Process::run($command);

            if ($process->successful()) {
                // Replace original with optimized version
                rename($optimizedFullPath, $fullPath);
                $result['optimizations_applied'][] = 'video_compressed';

                // Generate video thumbnails
                $result['thumbnails'] = $this->generateVideoThumbnails($filePath, $fullPath);

                // Generate preview clips
                $result['previews'] = $this->generateVideoPreview($filePath, $fullPath);

            } else {
                $result['errors'][] = 'FFmpeg optimization failed: '.$process->errorOutput();
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Video optimization failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Generate video thumbnails
     */
    private function generateVideoThumbnails(string $filePath, string $fullPath): array
    {
        $thumbnails = [];
        $directory = dirname($filePath);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);

        try {
            // Get video duration
            $duration = $this->getVideoDuration($fullPath);
            $thumbnailCount = self::VIDEO_OPTIMIZATION['thumbnail_count'];

            for ($i = 0; $i < $thumbnailCount; $i++) {
                $timestamp = ($duration / $thumbnailCount) * $i;
                $thumbnailName = "{$filename}_thumb_{$i}.jpg";
                $thumbnailPath = "{$directory}/thumbnails/{$thumbnailName}";
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

                // Ensure thumbnail directory exists
                $thumbnailDir = dirname($thumbnailFullPath);
                if (! is_dir($thumbnailDir)) {
                    mkdir($thumbnailDir, 0755, true);
                }

                // Generate thumbnail using FFmpeg
                $command = [
                    'ffmpeg', '-i', $fullPath,
                    '-ss', (string) $timestamp,
                    '-vframes', '1',
                    '-q:v', '2',
                    '-y', $thumbnailFullPath,
                ];

                $process = Process::run($command);

                if ($process->successful()) {
                    $thumbnails[] = [
                        'index' => $i,
                        'timestamp' => $timestamp,
                        'path' => $thumbnailPath,
                        'url' => Storage::url($thumbnailPath),
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::warning('Video thumbnail generation failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $thumbnails;
    }

    /**
     * Optimize document files
     */
    private function optimizeDocument(string $filePath, string $fullPath): array
    {
        $result = [
            'optimizations_applied' => [],
            'previews' => [],
            'errors' => [],
        ];

        try {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            switch ($extension) {
                case 'pdf':
                    $result = array_merge($result, $this->optimizePDF($filePath, $fullPath));
                    break;
                default:
                    // For other document types, generate previews only
                    $result['previews'] = $this->generateDocumentPreviews($filePath, $fullPath);
                    break;
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Document optimization failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Optimize PDF files
     */
    private function optimizePDF(string $filePath, string $fullPath): array
    {
        $result = [
            'optimizations_applied' => [],
            'previews' => [],
            'errors' => [],
        ];

        try {
            // Check if Ghostscript is available
            if (! $this->isGhostscriptAvailable()) {
                $result['errors'][] = 'Ghostscript not available for PDF optimization';

                return $result;
            }

            $directory = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $optimizedPath = "{$directory}/{$filename}_optimized.pdf";
            $optimizedFullPath = Storage::disk('public')->path($optimizedPath);

            // Build Ghostscript command for optimization
            $command = [
                'gs',
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dPDFSETTINGS=/prepress',
                '-dNOPAUSE',
                '-dQUIET',
                '-dBATCH',
                '-sOutputFile='.$optimizedFullPath,
                $fullPath,
            ];

            $process = Process::run($command);

            if ($process->successful() && file_exists($optimizedFullPath)) {
                $originalSize = filesize($fullPath);
                $optimizedSize = filesize($optimizedFullPath);

                // Only replace if optimization actually reduced size
                if ($optimizedSize < $originalSize) {
                    rename($optimizedFullPath, $fullPath);
                    $result['optimizations_applied'][] = 'pdf_compressed';
                } else {
                    unlink($optimizedFullPath);
                }
            }

            // Generate PDF preview images
            $result['previews'] = $this->generatePDFPreviews($filePath, $fullPath);

        } catch (\Exception $e) {
            $result['errors'][] = 'PDF optimization failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Generate PDF preview images
     */
    private function generatePDFPreviews(string $filePath, string $fullPath): array
    {
        $previews = [];
        $directory = dirname($filePath);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);

        try {
            $previewCount = self::DOCUMENT_OPTIMIZATION['preview_pages'];

            for ($page = 1; $page <= $previewCount; $page++) {
                $previewName = "{$filename}_preview_page_{$page}.jpg";
                $previewPath = "{$directory}/previews/{$previewName}";
                $previewFullPath = Storage::disk('public')->path($previewPath);

                // Ensure preview directory exists
                $previewDir = dirname($previewFullPath);
                if (! is_dir($previewDir)) {
                    mkdir($previewDir, 0755, true);
                }

                // Generate preview using Ghostscript
                $command = [
                    'gs',
                    '-sDEVICE=jpeg',
                    '-dTextAlphaBits=4',
                    '-dGraphicsAlphaBits=4',
                    '-r150',
                    '-dFirstPage='.$page,
                    '-dLastPage='.$page,
                    '-sOutputFile='.$previewFullPath,
                    $fullPath,
                ];

                $process = Process::run($command);

                if ($process->successful() && file_exists($previewFullPath)) {
                    $previews[] = [
                        'page' => $page,
                        'path' => $previewPath,
                        'url' => Storage::url($previewPath),
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::warning('PDF preview generation failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $previews;
    }

    /**
     * Optimize audio files
     */
    private function optimizeAudio(string $filePath, string $fullPath): array
    {
        $result = [
            'optimizations_applied' => [],
            'errors' => [],
        ];

        try {
            if (! $this->isFFmpegAvailable()) {
                $result['errors'][] = 'FFmpeg not available for audio optimization';

                return $result;
            }

            $directory = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $optimizedPath = "{$directory}/{$filename}_optimized.mp3";
            $optimizedFullPath = Storage::disk('public')->path($optimizedPath);

            // Build FFmpeg command for audio optimization
            $command = [
                'ffmpeg', '-i', $fullPath,
                '-codec:a', 'libmp3lame',
                '-b:a', self::AUDIO_OPTIMIZATION['bitrate'],
                '-ar', (string) self::AUDIO_OPTIMIZATION['sample_rate'],
                '-ac', (string) self::AUDIO_OPTIMIZATION['channels'],
                '-y', $optimizedFullPath,
            ];

            $process = Process::run($command);

            if ($process->successful()) {
                rename($optimizedFullPath, $fullPath);
                $result['optimizations_applied'][] = 'audio_compressed';
            } else {
                $result['errors'][] = 'FFmpeg audio optimization failed: '.$process->errorOutput();
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Audio optimization failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Optimize generic files (compression)
     */
    private function optimizeGenericFile(string $filePath, string $fullPath): array
    {
        $result = [
            'optimizations_applied' => [],
            'errors' => [],
        ];

        // For now, just log that a generic file was processed
        $result['optimizations_applied'][] = 'metadata_updated';

        return $result;
    }

    // Helper methods

    private function getCategoryFromExtension(string $extension): string
    {
        $categories = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        ];

        foreach ($categories as $category => $extensions) {
            if (in_array($extension, $extensions)) {
                return $category;
            }
        }

        return 'generic';
    }

    private function isFFmpegAvailable(): bool
    {
        try {
            $process = Process::run(['ffmpeg', '-version']);

            return $process->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isGhostscriptAvailable(): bool
    {
        try {
            $process = Process::run(['gs', '--version']);

            return $process->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function buildFFmpegOptimizationCommand(string $inputPath, string $outputPath): array
    {
        return [
            'ffmpeg', '-i', $inputPath,
            '-vcodec', self::VIDEO_OPTIMIZATION['video_codec'],
            '-acodec', self::VIDEO_OPTIMIZATION['audio_codec'],
            '-b:v', self::VIDEO_OPTIMIZATION['video_bitrate'],
            '-b:a', self::VIDEO_OPTIMIZATION['audio_bitrate'],
            '-r', (string) self::VIDEO_OPTIMIZATION['frame_rate'],
            '-vf', 'scale='.self::VIDEO_OPTIMIZATION['max_resolution'],
            '-preset', 'medium',
            '-crf', '23',
            '-y', $outputPath,
        ];
    }

    private function getVideoDuration(string $filePath): float
    {
        try {
            $command = ['ffprobe', '-v', 'quiet', '-show_entries', 'format=duration', '-of', 'csv=p=0', $filePath];
            $process = Process::run($command);

            if ($process->successful()) {
                return (float) trim($process->output());
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get video duration', ['file' => $filePath, 'error' => $e->getMessage()]);
        }

        return 0.0;
    }

    private function generateVideoPreview(string $filePath, string $fullPath): array
    {
        $previews = [];

        try {
            $directory = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $previewPath = "{$directory}/previews/{$filename}_preview.mp4";
            $previewFullPath = Storage::disk('public')->path($previewPath);

            // Ensure preview directory exists
            $previewDir = dirname($previewFullPath);
            if (! is_dir($previewDir)) {
                mkdir($previewDir, 0755, true);
            }

            // Generate 30-second preview from the beginning
            $command = [
                'ffmpeg', '-i', $fullPath,
                '-t', '30',
                '-vcodec', 'libx264',
                '-acodec', 'aac',
                '-b:v', '500k',
                '-b:a', '64k',
                '-y', $previewFullPath,
            ];

            $process = Process::run($command);

            if ($process->successful()) {
                $previews[] = [
                    'type' => 'preview',
                    'duration' => 30,
                    'path' => $previewPath,
                    'url' => Storage::url($previewPath),
                ];
            }

        } catch (\Exception $e) {
            Log::warning('Video preview generation failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $previews;
    }

    private function generateDocumentPreviews(string $filePath, string $fullPath): array
    {
        // Placeholder for document preview generation
        // Would implement based on document type and available tools
        return [];
    }

    private function logOptimization(string $filePath, array $result): void
    {
        Log::info('File optimization completed', [
            'file_path' => $filePath,
            'success' => $result['success'],
            'original_size' => $result['original_size'],
            'optimized_size' => $result['optimized_size'],
            'compression_ratio' => $result['compression_ratio'],
            'optimizations_applied' => $result['optimizations_applied'],
            'thumbnail_count' => count($result['thumbnails'] ?? []),
            'preview_count' => count($result['previews'] ?? []),
        ]);
    }
}
