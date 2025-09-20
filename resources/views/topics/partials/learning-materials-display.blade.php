@php
    $videos = $topic->getVideos();
    $links = $topic->getLinks();
    $files = $topic->getFiles();
    $isKidsView = request()->get('view') === 'kids' || auth()->user()->preferred_view === 'kids';
@endphp

@if($isKidsView)
    @include('topics.partials.learning-materials-kids-view', ['topic' => $topic])
@else
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
    <!-- Videos -->
    @foreach($videos as $video)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        @if($video['type'] === 'youtube')
                            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate">{{ $video['title'] }}</h4>
                    @if(!empty($video['description']))
                        <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $video['description'] }}</p>
                    @endif
                    <div class="mt-2">
                        <a href="{{ $video['url'] }}" target="_blank"
                           class="inline-flex items-center text-xs font-medium text-red-600 hover:text-red-700">
                            Watch Video
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Links -->
    @foreach($links as $link)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate">{{ $link['title'] }}</h4>
                    @if(!empty($link['description']))
                        <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $link['description'] }}</p>
                    @endif
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-500 truncate">{{ $link['domain'] ?? parse_url($link['url'], PHP_URL_HOST) }}</span>
                        <a href="{{ $link['url'] }}" target="_blank"
                           class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-700">
                            Visit
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Files -->
    @foreach($files as $file)
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        @if(in_array($file['type'], ['jpg', 'jpeg', 'png', 'gif']))
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @elseif($file['type'] === 'pdf')
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        @elseif(in_array($file['type'], ['mp4', 'mov', 'avi']))
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        @elseif(in_array($file['type'], ['mp3', 'wav', 'ogg']))
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate">{{ $file['title'] }}</h4>
                    <p class="text-xs text-gray-500 mt-1 truncate">{{ $file['original_name'] }}</p>
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
                        <p class="text-xs text-gray-400 mt-1">{{ $formattedSize }}</p>
                    @endif
                    <div class="flex space-x-3 mt-2">
                        <a href="{{ $file['url'] ?? Storage::url($file['path']) }}" target="_blank"
                           class="inline-flex items-center text-xs font-medium text-green-600 hover:text-green-700">
                            View
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                        <a href="{{ $file['url'] ?? Storage::url($file['path']) }}" download
                           class="inline-flex items-center text-xs font-medium text-gray-600 hover:text-gray-700">
                            Download
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if(count($videos) === 0 && count($links) === 0 && count($files) === 0)
    <div class="text-center py-8">
        <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-sm font-medium text-gray-900 mb-1">No learning materials</h3>
        <p class="text-sm text-gray-500">Click "Edit Topic" to add videos, links, or files to help with learning.</p>
    </div>
@endif
@endif