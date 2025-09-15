{{-- Enhanced Rich Content Preview Partial --}}
<div class="rich-content-preview bg-white border border-gray-200 rounded-lg p-6">
    {{-- Enhanced Content Metadata --}}
    @if(!empty($metadata))
    <div class="mb-4 flex items-center justify-between text-sm text-gray-500 border-b border-gray-100 pb-3">
        <div class="flex items-center space-x-4 flex-wrap gap-y-2">
            @if(isset($metadata['word_count']) && $metadata['word_count'] > 0)
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                {{ $metadata['word_count'] }} words
            </span>
            @endif

            @if(isset($metadata['reading_time']) && $metadata['reading_time'] > 0)
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $metadata['reading_time'] }} min read
            </span>
            @endif

            @if(isset($metadata['format']))
            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                {{ ucfirst($metadata['format']) }}
            </span>
            @endif

            {{-- Enhanced metadata indicators --}}
            @if(isset($metadata['has_videos']) && $metadata['has_videos'])
            <span class="flex items-center px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                🎥 {{ $metadata['video_count'] }} video{{ $metadata['video_count'] > 1 ? 's' : '' }}
                @if(isset($metadata['estimated_video_time']) && $metadata['estimated_video_time'] > 0)
                    (~{{ $metadata['estimated_video_time'] }}min)
                @endif
            </span>
            @endif

            @if(isset($metadata['has_files']) && $metadata['has_files'])
            <span class="flex items-center px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                📎 {{ $metadata['file_count'] }} file{{ $metadata['file_count'] > 1 ? 's' : '' }}
            </span>
            @endif

            @if(isset($metadata['has_interactive_elements']) && $metadata['has_interactive_elements'])
            <span class="flex items-center px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                ⚙️ Interactive
            </span>
            @endif

            @if(isset($metadata['complexity_score']))
            @php
                $complexityColors = [
                    'basic' => 'bg-gray-100 text-gray-800',
                    'intermediate' => 'bg-yellow-100 text-yellow-800',
                    'advanced' => 'bg-orange-100 text-orange-800'
                ];
                $complexityColor = $complexityColors[$metadata['complexity_score']] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="px-2 py-1 {{ $complexityColor }} rounded-full text-xs font-medium">
                {{ ucfirst($metadata['complexity_score']) }}
            </span>
            @endif
        </div>
    </div>
    @endif

    {{-- Enhanced Rendered Content --}}
    <div class="enhanced-content prose prose-sm sm:prose lg:prose-lg xl:prose-xl max-w-none">
        {!! $html !!}
    </div>
</div>

{{-- Add custom styles for educational content --}}
<style>
    .rich-content-preview .prose {
        color: #374151;
        line-height: 1.7;
    }

    .rich-content-preview .prose h1,
    .rich-content-preview .prose h2,
    .rich-content-preview .prose h3,
    .rich-content-preview .prose h4,
    .rich-content-preview .prose h5,
    .rich-content-preview .prose h6 {
        color: #1f2937;
        font-weight: 600;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .rich-content-preview .prose h1 {
        font-size: 2rem;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .rich-content-preview .prose h2 {
        font-size: 1.5rem;
    }

    .rich-content-preview .prose h3 {
        font-size: 1.25rem;
    }

    .rich-content-preview .prose img {
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin: 1.5rem auto;
    }

    .rich-content-preview .prose blockquote {
        border-left: 4px solid #3b82f6;
        background-color: #eff6ff;
        padding: 1rem 1.5rem;
        margin: 1.5rem 0;
        border-radius: 0.375rem;
    }

    .rich-content-preview .prose code {
        background-color: #f3f4f6;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }

    .rich-content-preview .prose pre {
        background-color: #1f2937;
        color: #f9fafb;
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
    }

    .rich-content-preview .prose pre code {
        background-color: transparent;
        padding: 0;
        color: inherit;
    }

    .rich-content-preview .prose table {
        border-collapse: collapse;
        width: 100%;
        margin: 1.5rem 0;
    }

    .rich-content-preview .prose th,
    .rich-content-preview .prose td {
        border: 1px solid #d1d5db;
        padding: 0.75rem;
        text-align: left;
    }

    .rich-content-preview .prose th {
        background-color: #f9fafb;
        font-weight: 600;
    }

    .rich-content-preview .prose a {
        color: #3b82f6;
        text-decoration: underline;
    }

    .rich-content-preview .prose a:hover {
        color: #1d4ed8;
    }

    /* Callout boxes styles */
    .rich-content-preview .callout {
        padding: 1rem;
        margin: 1.5rem 0;
        border-left: 4px solid #3b82f6;
        background-color: #eff6ff;
        border-radius: 0.375rem;
    }

    .rich-content-preview .callout.warning {
        border-left-color: #f59e0b;
        background-color: #fffbeb;
    }

    .rich-content-preview .callout.error {
        border-left-color: #ef4444;
        background-color: #fef2f2;
    }

    .rich-content-preview .callout.success {
        border-left-color: #10b981;
        background-color: #f0fdf4;
    }

    .rich-content-preview .callout.note {
        border-left-color: #6b7280;
        background-color: #f9fafb;
    }

    .rich-content-preview .callout p:first-child {
        margin-top: 0;
    }

    .rich-content-preview .callout p:last-child {
        margin-bottom: 0;
    }

    /* Math formulas (future KaTeX integration) */
    .rich-content-preview .math {
        background-color: #fef3c7;
        color: #92400e;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-family: 'Courier New', monospace;
    }

    /* Print styles */
    @media print {
        .rich-content-preview .prose {
            max-width: none;
        }

        .rich-content-preview .prose img {
            max-width: 100%;
            page-break-inside: avoid;
        }

        .rich-content-preview .callout {
            border: 1px solid #d1d5db;
            page-break-inside: avoid;
        }
    }

    /* Mobile responsiveness */
    @media (max-width: 640px) {
        .rich-content-preview .prose {
            font-size: 16px;
        }

        .rich-content-preview .prose h1 {
            font-size: 1.5rem;
        }

        .rich-content-preview .prose h2 {
            font-size: 1.25rem;
        }

        .rich-content-preview .prose h3 {
            font-size: 1.125rem;
        }

        .rich-content-preview .prose table {
            font-size: 0.875rem;
        }

        .rich-content-preview .prose th,
        .rich-content-preview .prose td {
            padding: 0.5rem;
        }
    }
</style>