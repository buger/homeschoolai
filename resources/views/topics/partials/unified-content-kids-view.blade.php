@php
    use App\Models\Child;
    use App\Services\KidsContentRenderer;

    // Get current child from session or request
    $child = null;
    $isKidsMode = Session::get('kids_mode_active', false);
    $childId = Session::get('kids_mode_child_id');

    if ($isKidsMode && $childId) {
        $child = Child::find($childId);
    }

    // If no child found, try to get from user's children (for parent preview)
    if (!$child && auth()->user()) {
        $child = auth()->user()->children()->first();
    }

    // Fallback child for demo purposes
    if (!$child) {
        $child = new Child(['grade' => '3rd', 'independence_level' => 2, 'name' => 'Student']);
    }

    // Get unified content - either from new unified field or convert legacy
    $unifiedContent = $topic->getUnifiedContent();

    if (empty($unifiedContent)) {
        $unifiedContent = "# Learning about " . $topic->title . "\n\nThis is an exciting topic to explore! Let's dive in and discover new things together.";
    }

    // Render content using KidsContentRenderer
    $kidsRenderer = app(KidsContentRenderer::class);
    $renderedContent = $kidsRenderer->renderForKids($unifiedContent, $child, $topic);
@endphp

<div class="kids-unified-content-view"
     data-topic-id="{{ $topic->id }}"
     data-child-id="{{ $child->id ?? 'demo' }}"
     data-testid="kids-unified-content">

    <!-- Fun Header with Topic Info -->
    <div class="kids-topic-header">
        <div class="kids-breadcrumb">
            <span class="kids-breadcrumb-item">{{ $subject->name }}</span>
            <span class="kids-breadcrumb-arrow">→</span>
            <span class="kids-breadcrumb-item">{{ $unit->title }}</span>
            <span class="kids-breadcrumb-arrow">→</span>
            <span class="kids-breadcrumb-current">{{ $topic->title }}</span>
        </div>

        <div class="kids-topic-meta">
            @if($topic->estimated_minutes)
                <div class="kids-meta-item">
                    <span class="kids-meta-icon">⏰</span>
                    <span class="kids-meta-text">{{ $topic->estimated_minutes }} min</span>
                </div>
            @endif

            @if($topic->required)
                <div class="kids-meta-item kids-required">
                    <span class="kids-meta-icon">⭐</span>
                    <span class="kids-meta-text">Required</span>
                </div>
            @endif

            <div class="kids-meta-item">
                <span class="kids-meta-icon">📊</span>
                <span class="kids-meta-text">Level {{ $child->independence_level ?? 2 }}</span>
            </div>
        </div>
    </div>

    <!-- Gamification Dashboard for Kids -->
    <div class="kids-gamification-dashboard">
        <div class="kids-score-display">
            <div class="kids-score-item">
                <span class="kids-score-icon">🎯</span>
                <span class="kids-score-label">Points Available</span>
                <span class="kids-score-value">{{ $renderedContent['gamification']['points_available'] ?? 50 }}</span>
            </div>

            <div class="kids-score-item">
                <span class="kids-score-icon">⚡</span>
                <span class="kids-score-label">Difficulty</span>
                <span class="kids-score-value">{{ ucfirst($renderedContent['gamification']['difficulty_level'] ?? 'medium') }}</span>
            </div>

            <div class="kids-score-item">
                <span class="kids-score-icon">🔥</span>
                <span class="kids-score-label">Engagement</span>
                <span class="kids-score-value">{{ $renderedContent['engagement_score'] ?? 75 }}%</span>
            </div>
        </div>

        <!-- Encouragement Message -->
        <div class="kids-encouragement-message">
            <span class="kids-encouragement-icon">🌟</span>
            <span class="kids-encouragement-text">
                {{ $renderedContent['gamification']['encouragement_messages']['start'] ?? 'Let\'s learn something amazing!' }}
            </span>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="kids-main-content-area">
        <!-- Reading Controls (for older kids) -->
        @if($child->independence_level >= 2)
            <div class="kids-reading-controls">
                <button class="kids-control-btn" data-action="read-aloud" data-testid="kids-read-aloud">
                    🔊 Read Aloud
                </button>
                <button class="kids-control-btn" data-action="highlight-mode" data-testid="kids-highlight-mode">
                    🖍️ Highlight
                </button>
                @if($child->independence_level >= 3)
                    <button class="kids-control-btn" data-action="take-notes" data-testid="kids-take-notes">
                        📝 Take Notes
                    </button>
                    <button class="kids-control-btn" data-action="bookmark" data-testid="kids-bookmark">
                        🔖 Bookmark
                    </button>
                @endif
            </div>
        @endif

        <!-- Enhanced Content Display -->
        <div class="kids-content-display">
            {!! $renderedContent['html'] !!}
        </div>

        <!-- Interactive Learning Elements -->
        @if(!empty($renderedContent['interactive_elements']))
            <div class="kids-interactive-summary">
                <h4 class="kids-interactive-title">🎮 Interactive Features Available</h4>
                <div class="kids-interactive-list">
                    @foreach($renderedContent['interactive_elements'] as $element)
                        <span class="kids-interactive-tag">
                            @switch($element)
                                @case('checkboxes')
                                    ✅ Task Lists
                                    @break
                                @case('highlighting')
                                    🖍️ Text Highlighting
                                    @break
                                @case('bookmarks')
                                    🔖 Bookmarks
                                    @break
                                @case('notes')
                                    📝 Note Taking
                                    @break
                                @default
                                    🎯 {{ ucfirst($element) }}
                            @endswitch
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Learning Progress Section -->
    <div class="kids-learning-progress-section">
        <div class="kids-progress-header">
            <h4 class="kids-progress-title">📈 Your Learning Journey</h4>
            <div class="kids-progress-actions">
                @if($child->independence_level >= 2)
                    <button class="kids-action-btn kids-complete-btn" data-action="mark-complete" data-testid="kids-mark-complete">
                        ✅ Mark Complete
                    </button>
                @endif
                @if($child->independence_level >= 3)
                    <button class="kids-action-btn kids-save-btn" data-action="save-progress" data-testid="kids-save-progress">
                        💾 Save Progress
                    </button>
                @endif
            </div>
        </div>

        <!-- Visual Progress Indicators -->
        <div class="kids-progress-indicators">
            <div class="kids-progress-reading">
                <div class="kids-progress-label">
                    <span class="kids-progress-icon">📖</span>
                    <span class="kids-progress-text">Reading Progress</span>
                </div>
                <div class="kids-progress-bar-wrapper">
                    <div class="kids-progress-bar" data-progress="0">
                        <div class="kids-progress-fill"></div>
                    </div>
                    <span class="kids-progress-percent">0%</span>
                </div>
            </div>

            <div class="kids-progress-engagement">
                <div class="kids-progress-label">
                    <span class="kids-progress-icon">🎯</span>
                    <span class="kids-progress-text">Interaction Score</span>
                </div>
                <div class="kids-progress-bar-wrapper">
                    <div class="kids-progress-bar" data-progress="{{ $renderedContent['engagement_score'] ?? 0 }}">
                        <div class="kids-progress-fill" style="width: {{ $renderedContent['engagement_score'] ?? 0 }}%"></div>
                    </div>
                    <span class="kids-progress-percent">{{ $renderedContent['engagement_score'] ?? 0 }}%</span>
                </div>
            </div>

            @if($child->independence_level >= 3)
                <div class="kids-progress-time">
                    <div class="kids-progress-label">
                        <span class="kids-progress-icon">⏱️</span>
                        <span class="kids-progress-text">Time Spent</span>
                    </div>
                    <div class="kids-time-tracker">
                        <span class="kids-time-display" data-timer="session">0:00</span>
                        <button class="kids-time-control" data-action="toggle-timer">⏸️</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Achievement Showcase -->
    @if(!empty($renderedContent['gamification']['achievements_possible']))
        <div class="kids-achievement-showcase">
            <h4 class="kids-achievement-title">🏆 Achievements You Can Earn</h4>
            <div class="kids-achievement-grid">
                @foreach($renderedContent['gamification']['achievements_possible'] as $achievement => $description)
                    <div class="kids-achievement-card" data-achievement="{{ $achievement }}">
                        <div class="kids-achievement-icon">
                            @switch($achievement)
                                @case('first_reader')
                                    📚
                                    @break
                                @case('speed_reader')
                                    ⚡
                                    @break
                                @case('careful_reader')
                                    🔍
                                    @break
                                @case('interactive_learner')
                                    🎮
                                    @break
                                @default
                                    🏅
                            @endswitch
                        </div>
                        <div class="kids-achievement-info">
                            <div class="kids-achievement-name">{{ ucwords(str_replace('_', ' ', $achievement)) }}</div>
                            <div class="kids-achievement-desc">{{ $description }}</div>
                        </div>
                        <div class="kids-achievement-status">
                            <span class="kids-achievement-badge kids-not-earned">Not Earned</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Safety and Help Section -->
    <div class="kids-safety-section">
        <div class="kids-safety-header">
            <span class="kids-safety-icon">🛡️</span>
            <span class="kids-safety-title">Safe Learning Zone</span>
        </div>

        <div class="kids-safety-info">
            <div class="kids-safety-item">
                <span class="kids-safety-indicator green">✅</span>
                <span class="kids-safety-text">Content approved for {{ $child->grade ?? '3rd' }} grade</span>
            </div>

            <div class="kids-safety-item">
                <span class="kids-safety-indicator green">✅</span>
                <span class="kids-safety-text">Safe learning environment</span>
            </div>

            @if($child->independence_level <= 2)
                <div class="kids-safety-item">
                    <span class="kids-safety-indicator blue">👥</span>
                    <span class="kids-safety-text">Ask a grown-up if you need help!</span>
                </div>
            @endif
        </div>

        <div class="kids-help-actions">
            @if($child->independence_level >= 2)
                <button class="kids-help-btn" data-action="get-help" data-testid="kids-get-help">
                    🙋 Need Help?
                </button>
            @endif

            <button class="kids-help-btn" data-action="report-problem" data-testid="kids-report-problem">
                🚨 Report Problem
            </button>

            @if($isKidsMode)
                <a href="{{ route('kids-mode.exit') }}" class="kids-help-btn kids-exit-btn" data-testid="kids-exit-mode">
                    🚪 Exit Kids Mode
                </a>
            @endif
        </div>
    </div>

    <!-- Hidden Elements for JavaScript Interaction -->
    <div class="kids-hidden-data" style="display: none;">
        <input type="hidden" name="topic_id" value="{{ $topic->id }}">
        <input type="hidden" name="child_id" value="{{ $child->id ?? 'demo' }}">
        <input type="hidden" name="age_group" value="{{ $child->grade ?? '3rd' }}">
        <input type="hidden" name="independence_level" value="{{ $child->independence_level ?? 2 }}">
        <input type="hidden" name="estimated_minutes" value="{{ $topic->estimated_minutes ?? 15 }}">
        <textarea name="content_metadata" style="display: none;">{{ json_encode($renderedContent['metadata'] ?? []) }}</textarea>
        <textarea name="gamification_data" style="display: none;">{{ json_encode($renderedContent['gamification'] ?? []) }}</textarea>
    </div>
</div>

<!-- Kids-Specific Styles -->
<style>
/* Kids Unified Content View Styles */
.kids-unified-content-view {
    max-width: 100%;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    background: linear-gradient(135deg, #FEF7FF 0%, #F0F9FF 100%);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Topic Header */
.kids-topic-header {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 2px solid #E0E7FF;
}

.kids-breadcrumb {
    font-size: 0.875rem;
    color: #6B7280;
    margin-bottom: 0.5rem;
}

.kids-breadcrumb-current {
    color: #4F46E5;
    font-weight: 600;
}

.kids-breadcrumb-arrow {
    margin: 0 0.5rem;
    color: #9CA3AF;
}

.kids-topic-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.kids-meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #F3F4F6;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}

.kids-meta-item.kids-required {
    background: #FEF3C7;
    color: #D97706;
}

/* Gamification Dashboard */
.kids-gamification-dashboard {
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    color: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.kids-score-display {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.kids-score-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    padding: 0.75rem;
}

.kids-score-icon {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 0.25rem;
}

.kids-score-label {
    font-size: 0.75rem;
    opacity: 0.9;
    display: block;
}

.kids-score-value {
    font-size: 1.125rem;
    font-weight: 700;
    display: block;
}

.kids-encouragement-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    padding: 1rem;
    font-size: 1.125rem;
    font-weight: 500;
}

.kids-encouragement-icon {
    font-size: 2rem;
}

/* Reading Controls */
.kids-reading-controls {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.kids-control-btn {
    background: #10B981;
    color: white;
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    min-height: 44px;
}

.kids-control-btn:hover {
    background: #059669;
    transform: translateY(-1px);
}

/* Interactive Summary */
.kids-interactive-summary {
    background: #F0FDF4;
    border: 2px solid #16A34A;
    border-radius: 0.75rem;
    padding: 1rem;
    margin: 1.5rem 0;
}

.kids-interactive-title {
    color: #16A34A;
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.kids-interactive-list {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.kids-interactive-tag {
    background: #16A34A;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Progress Section */
.kids-learning-progress-section {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin: 1.5rem 0;
    border: 2px solid #FDE68A;
}

.kids-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.kids-progress-title {
    color: #D97706;
    font-size: 1.25rem;
    font-weight: 700;
}

.kids-progress-actions {
    display: flex;
    gap: 0.75rem;
}

.kids-action-btn {
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 44px;
}

.kids-complete-btn {
    background: #16A34A;
    color: white;
}

.kids-save-btn {
    background: #3B82F6;
    color: white;
}

.kids-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Progress Indicators */
.kids-progress-indicators {
    display: grid;
    gap: 1rem;
}

.kids-progress-reading,
.kids-progress-engagement,
.kids-progress-time {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.kids-progress-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #374151;
    min-width: 140px;
}

.kids-progress-icon {
    font-size: 1.25rem;
}

.kids-progress-bar-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.kids-progress-bar {
    flex: 1;
    height: 12px;
    background: #E5E7EB;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.kids-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10B981, #059669);
    border-radius: 6px;
    transition: width 0.5s ease;
    position: relative;
}

.kids-progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: progress-shimmer 2s infinite;
}

.kids-progress-percent {
    font-weight: 600;
    color: #16A34A;
    min-width: 2.5rem;
    text-align: right;
}

.kids-time-tracker {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #F3F4F6;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
}

.kids-time-display {
    font-family: 'Monaco', 'Menlo', monospace;
    font-weight: 600;
    color: #374151;
}

.kids-time-control {
    background: none;
    border: none;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

/* Achievement Showcase */
.kids-achievement-showcase {
    background: #FEF7FF;
    border: 2px solid #C084FC;
    border-radius: 1rem;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.kids-achievement-title {
    color: #7C3AED;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.kids-achievement-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.kids-achievement-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid #E5E7EB;
    transition: all 0.2s;
}

.kids-achievement-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.kids-achievement-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.kids-achievement-info {
    flex: 1;
}

.kids-achievement-name {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.kids-achievement-desc {
    font-size: 0.875rem;
    color: #6B7280;
}

.kids-achievement-status {
    flex-shrink: 0;
}

.kids-achievement-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.kids-achievement-badge.kids-not-earned {
    background: #F3F4F6;
    color: #6B7280;
}

.kids-achievement-badge.kids-earned {
    background: #16A34A;
    color: white;
}

/* Safety Section */
.kids-safety-section {
    background: #F0FDF4;
    border: 2px solid #16A34A;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.kids-safety-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.kids-safety-icon {
    font-size: 2rem;
}

.kids-safety-title {
    color: #16A34A;
    font-size: 1.25rem;
    font-weight: 700;
}

.kids-safety-info {
    margin-bottom: 1rem;
}

.kids-safety-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.kids-safety-indicator {
    font-size: 1.125rem;
}

.kids-safety-indicator.green {
    color: #16A34A;
}

.kids-safety-indicator.blue {
    color: #3B82F6;
}

.kids-safety-text {
    color: #374151;
    font-weight: 500;
}

.kids-help-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.kids-help-btn {
    background: #16A34A;
    color: white;
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    min-height: 44px;
}

.kids-help-btn:hover {
    background: #059669;
    transform: translateY(-1px);
}

.kids-help-btn.kids-exit-btn {
    background: #DC2626;
}

.kids-help-btn.kids-exit-btn:hover {
    background: #B91C1C;
}

/* Animations */
@keyframes progress-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .kids-unified-content-view {
        padding: 1rem;
    }

    .kids-score-display {
        grid-template-columns: 1fr;
    }

    .kids-reading-controls {
        justify-content: center;
    }

    .kids-progress-reading,
    .kids-progress-engagement,
    .kids-progress-time {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }

    .kids-progress-label {
        min-width: auto;
        justify-content: center;
    }

    .kids-achievement-grid {
        grid-template-columns: 1fr;
    }

    .kids-help-actions {
        justify-content: center;
    }
}

/* Accessibility */
.kids-control-btn:focus,
.kids-action-btn:focus,
.kids-help-btn:focus {
    outline: 3px solid #F59E0B;
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .kids-gamification-dashboard,
    .kids-reading-controls,
    .kids-help-actions {
        display: none;
    }

    .kids-unified-content-view {
        background: white;
        box-shadow: none;
        border: 1px solid #000;
    }
}
</style>

<!-- JavaScript for Kids Interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Kids Content Interactions
    const kidsContent = document.querySelector('.kids-unified-content-view');
    if (!kidsContent) return;

    const topicId = kidsContent.dataset.topicId;
    const childId = kidsContent.dataset.childId;

    // Progress tracking
    let readingProgress = 0;
    let interactionScore = parseInt(kidsContent.querySelector('[data-progress]')?.dataset.progress || '0');
    let sessionStartTime = Date.now();
    let isTimerRunning = true;

    // Update timer
    function updateTimer() {
        if (!isTimerRunning) return;

        const elapsed = Math.floor((Date.now() - sessionStartTime) / 1000);
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;
        const timeDisplay = document.querySelector('[data-timer="session"]');

        if (timeDisplay) {
            timeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    // Start timer
    setInterval(updateTimer, 1000);

    // Timer controls
    document.querySelector('[data-action="toggle-timer"]')?.addEventListener('click', function() {
        isTimerRunning = !isTimerRunning;
        this.textContent = isTimerRunning ? '⏸️' : '▶️';
    });

    // Reading progress tracking
    function updateReadingProgress() {
        const contentArea = document.querySelector('.kids-content-display');
        if (!contentArea) return;

        const scrollTop = window.pageYOffset;
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = Math.min(100, Math.max(0, (scrollTop / scrollHeight) * 100));

        const progressBar = document.querySelector('[data-progress="0"] .kids-progress-fill');
        const progressPercent = document.querySelector('.kids-progress-percent');

        if (progressBar && progressPercent) {
            progressBar.style.width = progress + '%';
            progressPercent.textContent = Math.round(progress) + '%';
        }

        readingProgress = progress;
    }

    // Track scroll for reading progress
    window.addEventListener('scroll', updateReadingProgress);

    // Control button handlers
    document.querySelectorAll('.kids-control-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;

            switch(action) {
                case 'read-aloud':
                    handleReadAloud();
                    break;
                case 'highlight-mode':
                    toggleHighlightMode();
                    break;
                case 'take-notes':
                    openNotesTool();
                    break;
                case 'bookmark':
                    addBookmark();
                    break;
            }

            // Update interaction score
            interactionScore = Math.min(100, interactionScore + 5);
            updateInteractionScore();
        });
    });

    // Action button handlers
    document.querySelectorAll('.kids-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;

            switch(action) {
                case 'mark-complete':
                    markTopicComplete();
                    break;
                case 'save-progress':
                    saveProgress();
                    break;
            }
        });
    });

    // Help button handlers
    document.querySelectorAll('.kids-help-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;

            switch(action) {
                case 'get-help':
                    showHelpDialog();
                    break;
                case 'report-problem':
                    showReportDialog();
                    break;
            }
        });
    });

    // Interactive element handlers
    document.querySelectorAll('.kids-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            interactionScore = Math.min(100, interactionScore + 2);
            updateInteractionScore();

            // Add completion celebration
            if (this.checked) {
                showCelebration(this);
            }
        });
    });

    // Functions
    function handleReadAloud() {
        if ('speechSynthesis' in window) {
            const textContent = document.querySelector('.kids-content-display').textContent;
            const utterance = new SpeechSynthesisUtterance(textContent);
            utterance.rate = 0.8;
            utterance.pitch = 1.1;
            speechSynthesis.speak(utterance);

            showFeedback('🔊 Reading aloud!');
        } else {
            showFeedback('❌ Read aloud not available');
        }
    }

    function toggleHighlightMode() {
        document.body.classList.toggle('kids-highlight-mode');
        showFeedback('🖍️ Highlight mode toggled!');
    }

    function openNotesTool() {
        // Implementation for notes tool
        showFeedback('📝 Notes tool opening...');
    }

    function addBookmark() {
        // Implementation for bookmarks
        showFeedback('🔖 Bookmark added!');
    }

    function markTopicComplete() {
        // Send completion to server
        fetch(`/topics/${topicId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                child_id: childId,
                reading_progress: readingProgress,
                interaction_score: interactionScore,
                time_spent: Math.floor((Date.now() - sessionStartTime) / 1000)
            })
        }).then(response => {
            if (response.ok) {
                showCelebration(document.querySelector('.kids-complete-btn'), 'Topic completed! 🎉');
                updateAchievements();
            }
        });
    }

    function saveProgress() {
        // Save current progress
        const progressData = {
            topic_id: topicId,
            child_id: childId,
            reading_progress: readingProgress,
            interaction_score: interactionScore,
            time_spent: Math.floor((Date.now() - sessionStartTime) / 1000)
        };

        localStorage.setItem(`kids_progress_${topicId}_${childId}`, JSON.stringify(progressData));
        showFeedback('💾 Progress saved!');
    }

    function updateInteractionScore() {
        const scoreBar = document.querySelector('.kids-progress-engagement .kids-progress-fill');
        const scorePercent = document.querySelector('.kids-progress-engagement .kids-progress-percent');

        if (scoreBar && scorePercent) {
            scoreBar.style.width = interactionScore + '%';
            scorePercent.textContent = interactionScore + '%';
        }
    }

    function showCelebration(element, message = '🎉 Great job!') {
        const celebration = document.createElement('div');
        celebration.className = 'kids-celebration';
        celebration.textContent = message;
        celebration.style.cssText = `
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #16A34A;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-weight: 600;
            z-index: 1000;
            animation: kids-celebration 2s ease-out forwards;
        `;

        element.style.position = 'relative';
        element.appendChild(celebration);

        setTimeout(() => celebration.remove(), 2000);
    }

    function showFeedback(message) {
        const feedback = document.createElement('div');
        feedback.className = 'kids-feedback';
        feedback.textContent = message;
        feedback.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3B82F6;
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            z-index: 1000;
            animation: kids-feedback 3s ease-out forwards;
        `;

        document.body.appendChild(feedback);
        setTimeout(() => feedback.remove(), 3000);
    }

    function showHelpDialog() {
        alert('Help is on the way! Ask a grown-up or teacher for assistance. 👨‍🏫👩‍🏫');
    }

    function showReportDialog() {
        alert('Thanks for reporting! We\'ll look into this right away. 🕵️‍♀️');
    }

    function updateAchievements() {
        // Check and update achievement badges
        document.querySelectorAll('.kids-achievement-card').forEach(card => {
            const achievement = card.dataset.achievement;
            // Logic to check if achievement is earned
            // This would typically involve server communication
        });
    }

    // Add CSS animations
    if (!document.querySelector('#kids-animations')) {
        const style = document.createElement('style');
        style.id = 'kids-animations';
        style.textContent = `
            @keyframes kids-celebration {
                0% { opacity: 0; transform: translateX(-50%) translateY(10px) scale(0.8); }
                50% { opacity: 1; transform: translateX(-50%) translateY(-10px) scale(1.1); }
                100% { opacity: 0; transform: translateX(-50%) translateY(-30px) scale(1); }
            }

            @keyframes kids-feedback {
                0% { opacity: 0; transform: translateX(100%); }
                10% { opacity: 1; transform: translateX(0); }
                90% { opacity: 1; transform: translateX(0); }
                100% { opacity: 0; transform: translateX(100%); }
            }

            .kids-highlight-mode .kids-highlightable:hover {
                background: rgba(245, 158, 11, 0.3) !important;
                cursor: crosshair !important;
            }
        `;
        document.head.appendChild(style);
    }
});
</script>