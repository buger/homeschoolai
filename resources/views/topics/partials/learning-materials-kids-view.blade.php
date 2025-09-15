@php
    $videos = $topic->getVideos();
    $links = $topic->getLinks();
    $files = $topic->getFiles();
@endphp

<div class="learning-materials-kids" data-testid="learning-materials-kids">
    <!-- Header with fun styling -->
    <div class="mb-6 text-center">
        <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center">
            <span class="mr-3 text-3xl">📚</span>
            Learning Materials
            <span class="ml-3 text-3xl">🎯</span>
        </h3>
        <p class="text-gray-600 text-lg">Explore these awesome resources to learn about {{ $topic->title }}!</p>
    </div>

    <!-- Materials Grid - Optimized for Kids -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

        <!-- Videos Section -->
        @foreach($videos as $index => $video)
            <div class="material-card video-card bg-gradient-to-br from-red-50 to-red-100 border-2 border-red-200 rounded-xl p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group"
                 data-testid="video-card-{{ $index }}">

                <!-- Large Video Thumbnail Area -->
                <div class="mb-4">
                    @if($video['type'] === 'youtube' && isset($video['thumbnail']))
                        <div class="relative rounded-lg overflow-hidden bg-gray-200" style="aspect-ratio: 16/9;">
                            <img src="{{ $video['thumbnail'] }}" alt="{{ $video['title'] }}"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-20 group-hover:bg-opacity-10 transition-all">
                                <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="relative rounded-lg bg-red-200 flex items-center justify-center" style="aspect-ratio: 16/9;">
                            <div class="text-center">
                                @if($video['type'] === 'youtube')
                                    <svg class="w-16 h-16 mx-auto text-red-600 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-red-700">YouTube Video</span>
                                @elseif($video['type'] === 'vimeo')
                                    <svg class="w-16 h-16 mx-auto text-blue-600 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797l-.013.01z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-blue-700">Vimeo Video</span>
                                @elseif($video['type'] === 'khan_academy')
                                    <svg class="w-16 h-16 mx-auto text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-green-700">Khan Academy</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Video Info -->
                <div class="text-center">
                    <h4 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">{{ $video['title'] }}</h4>
                    @if(!empty($video['description']))
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $video['description'] }}</p>
                    @endif

                    <!-- Large, Touch-Friendly Button -->
                    <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center w-full py-3 px-6 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg text-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-red-300"
                       data-testid="watch-video-{{ $index }}">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        Watch Video
                    </a>
                </div>

                <!-- Platform Badge -->
                <div class="absolute top-4 right-4">
                    @if($video['type'] === 'youtube')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white shadow-lg">
                            YouTube
                        </span>
                    @elseif($video['type'] === 'vimeo')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-600 text-white shadow-lg">
                            Vimeo
                        </span>
                    @elseif($video['type'] === 'khan_academy')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-600 text-white shadow-lg">
                            Khan Academy
                        </span>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Links Section -->
        @foreach($links as $index => $link)
            <div class="material-card link-card bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-200 rounded-xl p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group"
                 data-testid="link-card-{{ $index }}">

                <!-- Large Link Icon -->
                <div class="mb-4 text-center">
                    <div class="w-20 h-20 mx-auto bg-blue-200 rounded-full flex items-center justify-center mb-3 group-hover:bg-blue-300 transition-colors">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                </div>

                <!-- Link Info -->
                <div class="text-center">
                    <h4 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">{{ $link['title'] }}</h4>
                    @if(!empty($link['description']))
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $link['description'] }}</p>
                    @endif

                    <!-- Website Badge -->
                    <div class="mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            🌐 {{ $link['domain'] ?? parse_url($link['url'], PHP_URL_HOST) }}
                        </span>
                    </div>

                    <!-- Large, Touch-Friendly Button -->
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300"
                       data-testid="visit-link-{{ $index }}">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Visit Website
                    </a>
                </div>
            </div>
        @endforeach

        <!-- Files Section -->
        @foreach($files as $index => $file)
            <div class="material-card file-card bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 rounded-xl p-6 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group"
                 data-testid="file-card-{{ $index }}">

                <!-- Large File Icon with Type -->
                <div class="mb-4 text-center">
                    <div class="w-20 h-20 mx-auto bg-green-200 rounded-full flex items-center justify-center mb-3 group-hover:bg-green-300 transition-colors">
                        @if(in_array($file['type'], ['jpg', 'jpeg', 'png', 'gif']))
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @elseif($file['type'] === 'pdf')
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        @elseif(in_array($file['type'], ['mp4', 'mov', 'avi']))
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        @elseif(in_array($file['type'], ['mp3', 'wav', 'ogg']))
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        @elseif(in_array($file['type'], ['doc', 'docx']))
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @else
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>
                </div>

                <!-- File Info -->
                <div class="text-center">
                    <h4 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">{{ $file['title'] }}</h4>
                    <p class="text-sm text-gray-600 mb-2 line-clamp-1">{{ $file['original_name'] }}</p>

                    <!-- File Type and Size -->
                    <div class="mb-4 space-y-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-600 text-white uppercase">
                            {{ $file['type'] }}
                        </span>
                        @if(isset($file['size']))
                            @php
                                $size = $file['size'];
                                if ($size >= 1048576) {
                                    $formattedSize = number_format($size / 1048576, 1) . ' MB';
                                } elseif ($size >= 1024) {
                                    $formattedSize = number_format($size / 1024, 0) . ' KB';
                                } else {
                                    $formattedSize = $size . ' B';
                                }
                            @endphp
                            <div class="text-sm text-gray-500 font-medium">{{ $formattedSize }}</div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2">
                        <a href="{{ $file['url'] ?? Storage::url($file['path']) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center justify-center w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-green-300"
                           data-testid="view-file-{{ $index }}">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View File
                        </a>
                        <a href="{{ $file['url'] ?? Storage::url($file['path']) }}" download
                           class="inline-flex items-center justify-center w-full py-2 px-6 bg-white border-2 border-green-600 text-green-600 hover:bg-green-50 font-semibold rounded-lg text-base transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-green-300"
                           data-testid="download-file-{{ $index }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Empty State for Kids -->
    @if(count($videos) === 0 && count($links) === 0 && count($files) === 0)
        <div class="text-center py-12 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border-2 border-dashed border-gray-300">
            <div class="mb-6">
                <span class="text-6xl">📝</span>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Learning Materials Yet</h3>
            <p class="text-gray-600 text-lg mb-6">Ask your parent to add some awesome videos, links, or files to help you learn!</p>
            <div class="inline-flex items-center px-6 py-3 bg-blue-100 text-blue-800 rounded-lg font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Coming Soon!
            </div>
        </div>
    @endif
</div>

<!-- Custom CSS for enhanced kids experience -->
<style>
.learning-materials-kids {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
}

.material-card {
    position: relative;
    min-height: 280px;
    background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.material-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Improved touch targets for mobile */
@media (max-width: 768px) {
    .material-card {
        min-height: 300px;
        padding: 1.5rem !important;
    }

    .material-card button,
    .material-card a {
        padding: 1rem 1.5rem !important;
        font-size: 1.125rem !important;
    }

    .material-card h4 {
        font-size: 1.25rem !important;
        line-height: 1.4 !important;
    }
}

/* Focus styles for accessibility */
.material-card a:focus,
.material-card button:focus {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3) !important;
}

/* Loading states */
.material-card img {
    transition: opacity 0.3s ease;
}

.material-card img[src=""] {
    opacity: 0;
}

/* Line clamp utilities for text truncation */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .material-card {
        border-width: 3px !important;
    }

    .material-card button,
    .material-card a {
        border-width: 2px !important;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .material-card,
    .material-card *,
    .material-card button,
    .material-card a {
        transition: none !important;
        transform: none !important;
    }

    .material-card:hover {
        transform: none !important;
    }
}

/* Print styles */
@media print {
    .material-card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 2px solid #000 !important;
    }
}
</style>

<!-- JavaScript for enhanced interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states for external links
    const links = document.querySelectorAll('[data-testid^="visit-link-"], [data-testid^="watch-video-"]');
    links.forEach(link => {
        link.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;

            // Show loading state briefly
            button.innerHTML = button.innerHTML.replace(/Watch Video|Visit Website/, 'Opening...');
            button.style.pointerEvents = 'none';

            // Reset after a short delay
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.pointerEvents = 'auto';
            }, 1500);
        });
    });

    // Add file download feedback
    const downloadLinks = document.querySelectorAll('[data-testid^="download-file-"]');
    downloadLinks.forEach(link => {
        link.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;

            button.innerHTML = button.innerHTML.replace('Download', 'Downloading...');
            button.style.pointerEvents = 'none';

            setTimeout(() => {
                button.innerHTML = originalText.replace('Downloading...', 'Downloaded!');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.pointerEvents = 'auto';
                }, 2000);
            }, 1000);
        });
    });

    // Keyboard navigation improvements
    const cards = document.querySelectorAll('.material-card');
    cards.forEach(card => {
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const primaryLink = card.querySelector('a[data-testid^="watch-video-"], a[data-testid^="visit-link-"], a[data-testid^="view-file-"]');
                if (primaryLink) {
                    primaryLink.click();
                }
            }
        });

        // Make cards focusable
        card.setAttribute('tabindex', '0');
    });

    // Touch feedback for mobile
    if ('ontouchstart' in window) {
        cards.forEach(card => {
            card.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });

            card.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    }
});
</script>