<div id="materials-section" class="space-y-4">
    @if($topic->hasLearningMaterials())
        @php
            $videos = $topic->getVideos();
            $links = $topic->getLinks();
            $files = $topic->getFiles();
        @endphp

        <!-- Videos Section -->
        @if(count($videos) > 0)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Videos ({{ count($videos) }})
                </h4>
                <div class="space-y-3">
                    @foreach($videos as $index => $video)
                        <div class="flex items-start justify-between p-3 bg-white border border-red-200 rounded-md">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <h5 class="font-medium text-gray-900">{{ $video['title'] }}</h5>
                                    @if($video['type'] === 'youtube')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            YouTube
                                        </span>
                                    @elseif($video['type'] === 'vimeo')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Vimeo
                                        </span>
                                    @elseif($video['type'] === 'khan_academy')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Khan Academy
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($video['description']))
                                    <p class="text-sm text-gray-600 mb-2">{{ $video['description'] }}</p>
                                @endif
                                <a href="{{ $video['url'] }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                    {{ $video['url'] }}
                                </a>
                            </div>
                            <button hx-delete="{{ route('topics.materials.remove', [$topic->id, 'videos', $index]) }}"
                                    hx-target="#materials-section"
                                    hx-swap="outerHTML"
                                    hx-confirm="Are you sure you want to remove this video?"
                                    class="ml-4 p-1 text-red-600 hover:text-red-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Links Section -->
        @if(count($links) > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Links ({{ count($links) }})
                </h4>
                <div class="space-y-3">
                    @foreach($links as $index => $link)
                        <div class="flex items-start justify-between p-3 bg-white border border-blue-200 rounded-md">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <h5 class="font-medium text-gray-900">{{ $link['title'] }}</h5>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $link['domain'] ?? parse_url($link['url'], PHP_URL_HOST) }}
                                    </span>
                                </div>
                                @if(!empty($link['description']))
                                    <p class="text-sm text-gray-600 mb-2">{{ $link['description'] }}</p>
                                @endif
                                <a href="{{ $link['url'] }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                    {{ $link['url'] }}
                                </a>
                            </div>
                            <button hx-delete="{{ route('topics.materials.remove', [$topic->id, 'links', $index]) }}"
                                    hx-target="#materials-section"
                                    hx-swap="outerHTML"
                                    hx-confirm="Are you sure you want to remove this link?"
                                    class="ml-4 p-1 text-red-600 hover:text-red-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Files Section -->
        @if(count($files) > 0)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Files ({{ count($files) }})
                </h4>
                <div class="space-y-3">
                    @foreach($files as $index => $file)
                        <div class="flex items-start justify-between p-3 bg-white border border-green-200 rounded-md">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <h5 class="font-medium text-gray-900">{{ $file['title'] }}</h5>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 uppercase">
                                        {{ $file['type'] }}
                                    </span>
                                    @if(isset($file['size']))
                                        <span class="text-xs text-gray-500">
                                            @php
                                                $size = $file['size'];
                                                if ($size >= 1048576) {
                                                    $formattedSize = number_format($size / 1048576, 2) . ' MB';
                                                } elseif ($size >= 1024) {
                                                    $formattedSize = number_format($size / 1024, 1) . ' KB';
                                                } else {
                                                    $formattedSize = $size . ' bytes';
                                                }
                                            @endphp
                                            ({{ $formattedSize }})
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 mb-2">{{ $file['original_name'] }}</p>
                                <div class="flex space-x-2">
                                    <a href="{{ $file['url'] ?? Storage::url($file['path']) }}" target="_blank"
                                       class="text-sm text-blue-600 hover:text-blue-800 underline">
                                        View File
                                    </a>
                                    <a href="{{ $file['url'] ?? Storage::url($file['path']) }}" download
                                       class="text-sm text-green-600 hover:text-green-800 underline">
                                        Download
                                    </a>
                                </div>
                            </div>
                            <button hx-delete="{{ route('topics.materials.remove', [$topic->id, 'files', $index]) }}"
                                    hx-target="#materials-section"
                                    hx-swap="outerHTML"
                                    hx-confirm="Are you sure you want to remove this file? This will permanently delete the file."
                                    class="ml-4 p-1 text-red-600 hover:text-red-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-8">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-sm font-medium text-gray-900 mb-1">No learning materials yet</h3>
            <p class="text-sm text-gray-500">Add videos, links, or files to help students learn this topic.</p>
        </div>
    @endif
</div>