/**
 * Kids Interactions - Independence Level 2 (Basic Tasks)
 *
 * Interactive features for elementary learners who can reorder tasks
 * and perform basic self-directed learning activities.
 */

class KidsInteractionsLevel2 {
    constructor() {
        this.soundEnabled = localStorage.getItem('kids_sound_enabled') !== 'false';
        this.theme = localStorage.getItem('kids_theme') || 'bright';
        this.animations = true;
        this.touchFeedback = true;

        // Learning data
        this.readingProgress = 0;
        this.totalInteractions = 0;
        this.tasksCompleted = 0;
        this.highlightedText = [];
        this.startTime = Date.now();

        // State management
        this.highlightMode = false;
        this.currentHighlightColor = 'yellow';

        this.init();
    }

    init() {
        console.log('📚 Kids Mode Level 2 - Basic Tasks initialized');

        this.setupTouchFeedback();
        this.setupInteractiveElements();
        this.setupTaskManagement();
        this.setupHighlighting();
        this.setupProgressTracking();
        this.setupPersistence();
        this.setupSafety();

        this.loadSavedProgress();
        this.showWelcomeMessage();
    }

    setupTouchFeedback() {
        // Enhanced touch feedback with haptic simulation
        document.addEventListener('touchstart', (e) => {
            const target = e.target;

            if (this.isInteractiveElement(target)) {
                this.addTouchRipple(target, e.touches[0]);
                this.simulateHaptic();
                this.playInteractionSound();
            }
        });

        // Improved hover effects
        document.addEventListener('mouseover', (e) => {
            const target = e.target;

            if (this.isInteractiveElement(target)) {
                this.addHoverEffect(target);
            }
        });
    }

    setupInteractiveElements() {
        // Enhanced read-aloud with controls
        this.setupReadAloud();

        // Interactive checkboxes with persistence
        this.setupCheckboxes();

        // Control buttons
        this.setupControlButtons();

        // Action buttons
        this.setupActionButtons();
    }

    setupTaskManagement() {
        // Task completion tracking
        document.querySelectorAll('.kids-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handleTaskToggle(e.target);
            });
        });

        // Task reordering (drag and drop simulation)
        this.setupTaskReordering();
    }

    setupHighlighting() {
        // Highlight mode toggle
        document.querySelectorAll('[data-action="highlight-mode"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleHighlightMode();
            });
        });

        // Highlight color picker
        this.addHighlightColorPicker();

        // Text selection and highlighting
        document.addEventListener('mouseup', () => {
            if (this.highlightMode) {
                this.handleTextSelection();
            }
        });
    }

    setupProgressTracking() {
        // Enhanced scroll-based progress
        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.updateReadingProgress();
                    this.checkMilestones();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Time tracking with pause/resume
        this.setupTimeTracking();

        // Interaction scoring
        this.setupInteractionScoring();
    }

    setupPersistence() {
        // Auto-save progress every 30 seconds
        setInterval(() => {
            this.saveProgress();
        }, 30000);

        // Save on page unload
        window.addEventListener('beforeunload', () => {
            this.saveProgress();
        });
    }

    setupSafety() {
        // Enhanced safety features
        this.setupSafeNavigation();
        this.setupContentFiltering();
        this.setupTimeWarnings();
    }

    setupReadAloud() {
        document.querySelectorAll('[data-action="read-aloud"], .kids-read-aloud-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.readAloudText(btn);
            });
        });

        // Paragraph-level read aloud
        document.querySelectorAll('.kids-text').forEach(text => {
            const readBtn = document.createElement('button');
            readBtn.className = 'kids-paragraph-read-btn';
            readBtn.innerHTML = '🔊';
            readBtn.title = 'Read this paragraph';

            readBtn.addEventListener('click', () => {
                this.readAloudText(text);
            });

            text.style.position = 'relative';
            text.appendChild(readBtn);
        });
    }

    setupCheckboxes() {
        document.querySelectorAll('.kids-checkbox').forEach(checkbox => {
            // Load saved state
            const checkboxId = this.getCheckboxId(checkbox);
            const savedState = localStorage.getItem(`kids_checkbox_${checkboxId}`);

            if (savedState === 'true') {
                checkbox.checked = true;
                this.updateTaskUI(checkbox, true);
            }

            checkbox.addEventListener('change', (e) => {
                this.handleCheckboxChange(e.target);
            });
        });
    }

    setupControlButtons() {
        document.querySelectorAll('.kids-control-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.dataset.action;
                this.handleControlAction(action, btn);
            });
        });
    }

    setupActionButtons() {
        document.querySelectorAll('.kids-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.dataset.action;
                this.handleActionButton(action, btn);
            });
        });
    }

    setupTaskReordering() {
        // Simple task reordering for level 2
        const taskList = document.querySelector('.kids-list');
        if (!taskList) return;

        let draggedElement = null;

        document.querySelectorAll('.kids-list-item').forEach(item => {
            item.draggable = true;
            item.style.cursor = 'grab';

            item.addEventListener('dragstart', (e) => {
                draggedElement = item;
                item.style.opacity = '0.5';
                this.showFeedback('📝 Reordering task...');
            });

            item.addEventListener('dragend', (e) => {
                item.style.opacity = '';
                draggedElement = null;
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
            });

            item.addEventListener('drop', (e) => {
                e.preventDefault();

                if (draggedElement && draggedElement !== item) {
                    const draggedIndex = Array.from(taskList.children).indexOf(draggedElement);
                    const targetIndex = Array.from(taskList.children).indexOf(item);

                    if (draggedIndex < targetIndex) {
                        taskList.insertBefore(draggedElement, item.nextSibling);
                    } else {
                        taskList.insertBefore(draggedElement, item);
                    }

                    this.saveTaskOrder();
                    this.showFeedback('✅ Tasks reordered!');
                    this.incrementInteractions();
                }
            });
        });
    }

    setupTimeTracking() {
        this.isPaused = false;
        this.totalPausedTime = 0;
        this.lastPauseTime = null;

        // Time display update
        setInterval(() => {
            this.updateTimeDisplay();
        }, 1000);

        // Pause/resume controls
        document.querySelectorAll('[data-action="toggle-timer"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleTimer(btn);
            });
        });
    }

    setupInteractionScoring() {
        // Track various interaction types
        this.interactionTypes = {
            reading: 0,
            tasks: 0,
            highlights: 0,
            reorders: 0
        };
    }

    // Core interaction methods
    isInteractiveElement(element) {
        return element.matches(`
            .kids-read-aloud-btn,
            .kids-paragraph-read-btn,
            .kids-list-item,
            .kids-text,
            .kids-heading,
            .kids-control-btn,
            .kids-action-btn,
            .kids-help-btn,
            .kids-checkbox,
            [data-interactive],
            [data-highlightable],
            button,
            a
        `);
    }

    addTouchRipple(element, touch) {
        const rect = element.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;

        const ripple = document.createElement('div');
        ripple.className = 'kids-touch-ripple-level2';
        ripple.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.6) 0%, rgba(16, 185, 129, 0) 70%);
            transform: translate(-50%, -50%);
            animation: kids-ripple-level2 0.6s ease-out;
            pointer-events: none;
            z-index: 1000;
        `;

        element.style.position = 'relative';
        element.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    }

    simulateHaptic() {
        // Simulate haptic feedback with visual pulse
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    }

    playInteractionSound() {
        if (!this.soundEnabled) return;

        // Create pleasant interaction sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 0.1);

        gainNode.gain.setValueAtTime(0.15, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.15);
    }

    handleControlAction(action, button) {
        switch(action) {
            case 'read-aloud':
                this.readAloudText(button.closest('.kids-text') || document.querySelector('.kids-content-display'));
                break;
            case 'highlight-mode':
                this.toggleHighlightMode();
                break;
            case 'take-notes':
                this.openNotesMode();
                break;
            case 'bookmark':
                this.addBookmark();
                break;
        }

        this.incrementInteractions();
    }

    handleActionButton(action, button) {
        switch(action) {
            case 'mark-complete':
                this.markTopicComplete();
                break;
            case 'save-progress':
                this.saveProgress();
                this.showFeedback('💾 Progress saved!');
                break;
        }
    }

    handleCheckboxChange(checkbox) {
        const isChecked = checkbox.checked;
        const checkboxId = this.getCheckboxId(checkbox);

        // Save state
        localStorage.setItem(`kids_checkbox_${checkboxId}`, isChecked.toString());

        // Update UI
        this.updateTaskUI(checkbox, isChecked);

        // Track completion
        if (isChecked) {
            this.tasksCompleted++;
            this.interactionTypes.tasks++;
            this.showTaskCompletion(checkbox);
        } else {
            this.tasksCompleted = Math.max(0, this.tasksCompleted - 1);
        }

        this.updateTaskProgress();
        this.incrementInteractions();
    }

    updateTaskUI(checkbox, isCompleted) {
        const container = checkbox.closest('.kids-list-item') || checkbox.closest('.kids-checkbox-container');

        if (container) {
            if (isCompleted) {
                container.classList.add('kids-task-completed');
                container.style.background = 'linear-gradient(135deg, #ECFDF5, #D1FAE5)';
            } else {
                container.classList.remove('kids-task-completed');
                container.style.background = '';
            }
        }
    }

    showTaskCompletion(checkbox) {
        const celebration = document.createElement('div');
        celebration.className = 'kids-task-celebration';
        celebration.innerHTML = '🎉';

        celebration.style.cssText = `
            position: absolute;
            top: -20px;
            right: -10px;
            font-size: 2rem;
            z-index: 1000;
            animation: kids-task-celebration 1.5s ease-out forwards;
            pointer-events: none;
        `;

        checkbox.style.position = 'relative';
        checkbox.appendChild(celebration);

        setTimeout(() => celebration.remove(), 1500);

        this.playSuccessSound();
        this.showFeedback('✅ Great job completing that task!');
    }

    toggleHighlightMode() {
        this.highlightMode = !this.highlightMode;

        const highlightBtn = document.querySelector('[data-action="highlight-mode"]');
        if (highlightBtn) {
            if (this.highlightMode) {
                highlightBtn.style.background = '#F59E0B';
                highlightBtn.textContent = '🖍️ Highlighting ON';
                this.showFeedback('🖍️ Highlight mode active! Select text to highlight.');
            } else {
                highlightBtn.style.background = '';
                highlightBtn.textContent = '🖍️ Highlight';
                this.showFeedback('📖 Highlight mode off');
            }
        }

        document.body.classList.toggle('kids-highlight-mode', this.highlightMode);
    }

    addHighlightColorPicker() {
        const colors = [
            { name: 'yellow', color: '#FEF08A' },
            { name: 'green', color: '#BBF7D0' },
            { name: 'blue', color: '#BFDBFE' },
            { name: 'pink', color: '#FBCFE8' },
            { name: 'orange', color: '#FED7AA' }
        ];

        const colorPicker = document.createElement('div');
        colorPicker.className = 'kids-highlight-colors';
        colorPicker.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 2px solid #E5E7EB;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        `;

        colors.forEach(color => {
            const colorBtn = document.createElement('button');
            colorBtn.className = 'kids-color-btn';
            colorBtn.style.cssText = `
                width: 30px;
                height: 30px;
                background: ${color.color};
                border: 2px solid #E5E7EB;
                border-radius: 50%;
                margin: 0.25rem;
                cursor: pointer;
                transition: transform 0.2s;
            `;

            colorBtn.addEventListener('click', () => {
                this.currentHighlightColor = color.name;
                this.showFeedback(`🎨 Using ${color.name} highlighter`);
            });

            colorBtn.addEventListener('mouseover', () => {
                colorBtn.style.transform = 'scale(1.2)';
            });

            colorBtn.addEventListener('mouseout', () => {
                colorBtn.style.transform = 'scale(1)';
            });

            colorPicker.appendChild(colorBtn);
        });

        document.body.appendChild(colorPicker);

        // Show/hide color picker
        document.querySelector('[data-action="highlight-mode"]')?.addEventListener('click', () => {
            setTimeout(() => {
                colorPicker.style.display = this.highlightMode ? 'block' : 'none';
            }, 100);
        });
    }

    handleTextSelection() {
        const selection = window.getSelection();
        if (selection.rangeCount === 0 || selection.toString().trim() === '') return;

        const range = selection.getRangeAt(0);
        const selectedText = selection.toString().trim();

        if (selectedText.length < 3) return; // Minimum selection

        // Create highlight span
        const highlight = document.createElement('span');
        highlight.className = `kids-highlight kids-highlight-${this.currentHighlightColor}`;
        highlight.style.cssText = `
            background: ${this.getHighlightColor(this.currentHighlightColor)};
            padding: 2px 4px;
            border-radius: 3px;
            position: relative;
        `;

        try {
            range.surroundContents(highlight);

            // Track highlight
            this.highlightedText.push({
                text: selectedText,
                color: this.currentHighlightColor,
                timestamp: Date.now()
            });

            this.interactionTypes.highlights++;
            this.incrementInteractions();
            this.showFeedback(`🖍️ Text highlighted in ${this.currentHighlightColor}!`);

            // Add remove button
            this.addHighlightRemoveButton(highlight);

        } catch (e) {
            // Fallback for complex selections
            console.log('Could not highlight complex selection');
        }

        selection.removeAllRanges();
    }

    addHighlightRemoveButton(highlight) {
        const removeBtn = document.createElement('button');
        removeBtn.innerHTML = '❌';
        removeBtn.className = 'kids-highlight-remove';
        removeBtn.style.cssText = `
            position: absolute;
            top: -8px;
            right: -8px;
            width: 16px;
            height: 16px;
            border: none;
            background: #EF4444;
            color: white;
            border-radius: 50%;
            font-size: 8px;
            cursor: pointer;
            display: none;
            z-index: 1001;
        `;

        removeBtn.addEventListener('click', () => {
            const parent = highlight.parentNode;
            parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
            parent.removeChild(highlight);
            this.showFeedback('🗑️ Highlight removed');
        });

        highlight.appendChild(removeBtn);

        highlight.addEventListener('mouseenter', () => {
            removeBtn.style.display = 'block';
        });

        highlight.addEventListener('mouseleave', () => {
            removeBtn.style.display = 'none';
        });
    }

    getHighlightColor(colorName) {
        const colors = {
            yellow: '#FEF08A',
            green: '#BBF7D0',
            blue: '#BFDBFE',
            pink: '#FBCFE8',
            orange: '#FED7AA'
        };
        return colors[colorName] || colors.yellow;
    }

    // Progress and persistence methods
    updateReadingProgress() {
        const scrollTop = window.pageYOffset;
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = Math.min(100, Math.max(0, (scrollTop / scrollHeight) * 100));

        this.readingProgress = progress;

        const progressBar = document.querySelector('.kids-progress-fill');
        const progressPercent = document.querySelector('.kids-progress-percent');

        if (progressBar && progressPercent) {
            progressBar.style.width = progress + '%';
            progressPercent.textContent = Math.round(progress) + '%';
        }
    }

    updateTimeDisplay() {
        if (this.isPaused) return;

        const elapsed = Math.floor((Date.now() - this.startTime - this.totalPausedTime) / 1000);
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;

        const timeDisplay = document.querySelector('[data-timer="session"]');
        if (timeDisplay) {
            timeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        // Time warnings
        if (minutes >= 30 && !this.thirtyMinuteWarning) {
            this.thirtyMinuteWarning = true;
            this.showTimeWarning('⏰ You\'ve been learning for 30 minutes! Great focus!');
        }
    }

    toggleTimer(button) {
        this.isPaused = !this.isPaused;

        if (this.isPaused) {
            this.lastPauseTime = Date.now();
            button.textContent = '▶️';
            this.showFeedback('⏸️ Timer paused');
        } else {
            if (this.lastPauseTime) {
                this.totalPausedTime += Date.now() - this.lastPauseTime;
            }
            button.textContent = '⏸️';
            this.showFeedback('▶️ Timer resumed');
        }
    }

    saveProgress() {
        const progressData = {
            readingProgress: this.readingProgress,
            totalInteractions: this.totalInteractions,
            tasksCompleted: this.tasksCompleted,
            highlightedText: this.highlightedText,
            interactionTypes: this.interactionTypes,
            timeSpent: Math.floor((Date.now() - this.startTime - this.totalPausedTime) / 1000),
            timestamp: Date.now()
        };

        const topicId = document.querySelector('[data-topic-id]')?.dataset.topicId;
        const childId = document.querySelector('[data-child-id]')?.dataset.childId;

        if (topicId && childId) {
            localStorage.setItem(`kids_progress_${topicId}_${childId}`, JSON.stringify(progressData));
        }
    }

    loadSavedProgress() {
        const topicId = document.querySelector('[data-topic-id]')?.dataset.topicId;
        const childId = document.querySelector('[data-child-id]')?.dataset.childId;

        if (topicId && childId) {
            const saved = localStorage.getItem(`kids_progress_${topicId}_${childId}`);
            if (saved) {
                const progressData = JSON.parse(saved);

                this.totalInteractions = progressData.totalInteractions || 0;
                this.tasksCompleted = progressData.tasksCompleted || 0;
                this.highlightedText = progressData.highlightedText || [];
                this.interactionTypes = progressData.interactionTypes || { reading: 0, tasks: 0, highlights: 0, reorders: 0 };

                this.showFeedback('📚 Previous progress loaded!');
            }
        }
    }

    // Utility methods
    getCheckboxId(checkbox) {
        return checkbox.closest('.kids-list-item')?.textContent?.trim().substring(0, 20) || Math.random().toString(36);
    }

    incrementInteractions() {
        this.totalInteractions++;
        this.updateInteractionScore();
    }

    updateInteractionScore() {
        const maxInteractions = 50; // Higher for level 2
        const score = Math.min(100, (this.totalInteractions / maxInteractions) * 100);

        const scoreBar = document.querySelector('.kids-progress-engagement .kids-progress-fill');
        const scorePercent = document.querySelector('.kids-progress-engagement .kids-progress-percent');

        if (scoreBar && scorePercent) {
            scoreBar.style.width = score + '%';
            scorePercent.textContent = Math.round(score) + '%';
        }
    }

    showFeedback(message) {
        const feedback = document.createElement('div');
        feedback.className = 'kids-feedback-level2';
        feedback.textContent = message;

        feedback.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 1rem;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            animation: kids-feedback-level2 3.5s ease-out forwards;
        `;

        document.body.appendChild(feedback);
        setTimeout(() => feedback.remove(), 3500);
    }

    showTimeWarning(message) {
        const warning = document.createElement('div');
        warning.className = 'kids-time-warning';
        warning.innerHTML = `
            <div class="kids-warning-icon">⏰</div>
            <div class="kids-warning-text">${message}</div>
        `;

        warning.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #F59E0B;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: kids-time-warning 4s ease-out forwards;
        `;

        document.body.appendChild(warning);
        setTimeout(() => warning.remove(), 4000);
    }

    showWelcomeMessage() {
        setTimeout(() => {
            this.showFeedback('📚 Ready to learn and complete tasks!');
        }, 500);
    }

    playSuccessSound() {
        if (!this.soundEnabled) return;

        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        // Success sound - ascending notes
        oscillator.frequency.setValueAtTime(523, audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659, audioContext.currentTime + 0.1); // E5
        oscillator.frequency.setValueAtTime(784, audioContext.currentTime + 0.2); // G5

        gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    }

    readAloudText(element) {
        if (!('speechSynthesis' in window)) {
            this.showFeedback('🔊 Read aloud not available');
            return;
        }

        speechSynthesis.cancel();

        let textToRead = element.textContent || element.innerText;
        textToRead = textToRead.replace(/[📚🔍💡⭐🎯🌟✨]/g, '').replace(/\s+/g, ' ').trim();

        if (textToRead) {
            const utterance = new SpeechSynthesisUtterance(textToRead);
            utterance.rate = 0.8;
            utterance.pitch = 1.1;
            utterance.volume = 0.9;

            element.classList.add('kids-reading-aloud');
            this.interactionTypes.reading++;

            utterance.onend = () => {
                element.classList.remove('kids-reading-aloud');
                this.showFeedback('✅ Reading complete!');
            };

            speechSynthesis.speak(utterance);
            this.showFeedback('🔊 Reading aloud...');
            this.incrementInteractions();
        }
    }
}

// CSS Animations for Level 2
const style = document.createElement('style');
style.textContent = `
    @keyframes kids-ripple-level2 {
        0% { width: 0; height: 0; opacity: 1; }
        100% { width: 100px; height: 100px; opacity: 0; }
    }

    @keyframes kids-feedback-level2 {
        0% { opacity: 0; transform: translateX(100%); }
        10% { opacity: 1; transform: translateX(0); }
        85% { opacity: 1; transform: translateX(0); }
        100% { opacity: 0; transform: translateX(100%); }
    }

    @keyframes kids-task-celebration {
        0% { opacity: 0; transform: scale(0.5) rotate(0deg); }
        50% { opacity: 1; transform: scale(1.3) rotate(180deg); }
        100% { opacity: 0; transform: scale(0.8) rotate(360deg); }
    }

    @keyframes kids-time-warning {
        0% { opacity: 0; transform: translateX(-50%) translateY(20px); }
        15% { opacity: 1; transform: translateX(-50%) translateY(0); }
        85% { opacity: 1; transform: translateX(-50%) translateY(0); }
        100% { opacity: 0; transform: translateX(-50%) translateY(-20px); }
    }

    .kids-highlight-mode .kids-text {
        cursor: text !important;
    }

    .kids-highlight-mode .kids-text::selection {
        background: rgba(245, 158, 11, 0.3) !important;
    }

    .kids-task-completed {
        transition: all 0.3s ease !important;
    }

    .kids-reading-aloud {
        background: linear-gradient(45deg, #10B981, #059669) !important;
        color: white !important;
        animation: kids-reading-pulse-level2 1s ease-in-out infinite !important;
    }

    @keyframes kids-reading-pulse-level2 {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.01); }
    }

    .kids-paragraph-read-btn {
        position: absolute !important;
        top: 0.5rem !important;
        right: 0.5rem !important;
        background: #10B981 !important;
        color: white !important;
        border: none !important;
        border-radius: 50% !important;
        width: 32px !important;
        height: 32px !important;
        font-size: 0.875rem !important;
        cursor: pointer !important;
        opacity: 0.8 !important;
        transition: all 0.2s ease !important;
        z-index: 10 !important;
    }

    .kids-paragraph-read-btn:hover {
        opacity: 1 !important;
        transform: scale(1.1) !important;
    }

    .kids-highlight {
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }

    .kids-highlight:hover {
        filter: brightness(0.9) !important;
    }

    /* Mobile optimizations */
    @media (max-width: 768px) {
        .kids-feedback-level2 {
            font-size: 0.875rem !important;
            padding: 0.75rem 1rem !important;
        }

        .kids-highlight-colors {
            right: 10px !important;
            top: 60px !important;
        }

        .kids-paragraph-read-btn {
            width: 40px !important;
            height: 40px !important;
            font-size: 1rem !important;
        }
    }
`;

document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.kidsConfig && window.kidsConfig.independenceLevel === 2) {
        window.kidsInteractions = new KidsInteractionsLevel2();
    }
});