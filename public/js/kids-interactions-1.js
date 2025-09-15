/**
 * Kids Interactions - Independence Level 1 (View Only)
 *
 * Basic interactions for youngest learners with minimal complexity
 * and maximum safety. Focus on visual feedback and simple audio.
 */

class KidsInteractionsLevel1 {
    constructor() {
        this.soundEnabled = localStorage.getItem('kids_sound_enabled') !== 'false';
        this.animations = true;
        this.touchFeedback = true;
        this.readingProgress = 0;
        this.totalInteractions = 0;

        this.init();
    }

    init() {
        console.log('🌟 Kids Mode Level 1 - View Only initialized');

        this.setupTouchFeedback();
        this.setupReadAloud();
        this.setupVisualFeedback();
        this.setupProgressTracking();
        this.setupSafety();

        // Add welcoming message
        this.showWelcomeMessage();
    }

    setupTouchFeedback() {
        // Add gentle touch feedback to all interactive elements
        document.addEventListener('touchstart', (e) => {
            const target = e.target;

            if (this.isInteractiveElement(target)) {
                this.addTouchRipple(target, e.touches[0]);
                this.playTouchSound();
            }
        });

        // Add hover effects for mouse users
        document.addEventListener('mouseover', (e) => {
            const target = e.target;

            if (this.isInteractiveElement(target)) {
                this.addHoverEffect(target);
            }
        });
    }

    setupReadAloud() {
        // Enhanced read-aloud for level 1
        document.querySelectorAll('.kids-read-aloud-btn, [data-action="read-aloud"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.readAloudText(btn);
            });
        });

        // Auto-read headers for very young learners
        document.querySelectorAll('.kids-heading').forEach(heading => {
            heading.addEventListener('click', () => {
                this.readAloudText(heading);
            });
        });
    }

    setupVisualFeedback() {
        // Add sparkle effects for interactions
        document.querySelectorAll('.kids-list-item, .kids-text').forEach(element => {
            element.addEventListener('click', () => {
                this.addSparkleEffect(element);
                this.incrementInteractions();
            });
        });

        // Add gentle animations to all content
        this.addEntranceAnimations();
    }

    setupProgressTracking() {
        // Simple scroll-based progress
        let ticking = false;

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.updateReadingProgress();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Track time spent
        this.startTime = Date.now();
        setInterval(() => {
            this.updateTimeDisplay();
        }, 1000);
    }

    setupSafety() {
        // Prevent accidental navigation
        window.addEventListener('beforeunload', (e) => {
            if (this.totalInteractions > 0) {
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave? Your learning progress will be saved!';
            }
        });

        // Block right-click for safety
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showSafetyMessage('Right-click is disabled for safety! 🛡️');
        });
    }

    isInteractiveElement(element) {
        return element.matches(`
            .kids-read-aloud-btn,
            .kids-list-item,
            .kids-text,
            .kids-heading,
            .kids-control-btn,
            .kids-help-btn,
            [data-interactive],
            button,
            a
        `);
    }

    addTouchRipple(element, touch) {
        const rect = element.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;

        const ripple = document.createElement('div');
        ripple.className = 'kids-touch-ripple';
        ripple.style.cssText = `
            position: absolute;
            left: ${x}px;
            top: ${y}px;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: translate(-50%, -50%);
            animation: kids-ripple-level1 0.8s ease-out;
            pointer-events: none;
            z-index: 1000;
        `;

        element.style.position = 'relative';
        element.appendChild(ripple);

        setTimeout(() => ripple.remove(), 800);
    }

    addHoverEffect(element) {
        if (!element.classList.contains('kids-hovering')) {
            element.classList.add('kids-hovering');

            setTimeout(() => {
                element.classList.remove('kids-hovering');
            }, 200);
        }
    }

    playTouchSound() {
        if (!this.soundEnabled) return;

        // Create gentle click sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.1);

        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    }

    readAloudText(element) {
        if (!('speechSynthesis' in window)) {
            this.showFeedback('🔊 Read aloud not available');
            return;
        }

        // Stop any current speech
        speechSynthesis.cancel();

        let textToRead = '';

        if (element.hasAttribute('data-text')) {
            textToRead = element.getAttribute('data-text');
        } else {
            textToRead = element.textContent || element.innerText;
        }

        // Clean up text for better speech
        textToRead = textToRead
            .replace(/[📚🔍💡⭐🎯🌟✨🎈🦋🌈]/g, '') // Remove emojis
            .replace(/\s+/g, ' ') // Normalize whitespace
            .trim();

        if (textToRead) {
            const utterance = new SpeechSynthesisUtterance(textToRead);
            utterance.rate = 0.7; // Slower for young learners
            utterance.pitch = 1.2; // Higher pitch for friendliness
            utterance.volume = 0.8;

            // Add visual feedback during speech
            element.classList.add('kids-reading-aloud');

            utterance.onend = () => {
                element.classList.remove('kids-reading-aloud');
                this.showFeedback('✅ Reading complete!');
            };

            utterance.onerror = () => {
                element.classList.remove('kids-reading-aloud');
                this.showFeedback('❌ Could not read text');
            };

            speechSynthesis.speak(utterance);
            this.showFeedback('🔊 Reading aloud...');
        }
    }

    addSparkleEffect(element) {
        const sparkleCount = 5;

        for (let i = 0; i < sparkleCount; i++) {
            setTimeout(() => {
                const sparkle = document.createElement('div');
                sparkle.className = 'kids-sparkle';
                sparkle.textContent = ['✨', '⭐', '💫', '🌟'][Math.floor(Math.random() * 4)];

                const rect = element.getBoundingClientRect();
                const x = Math.random() * rect.width;
                const y = Math.random() * rect.height;

                sparkle.style.cssText = `
                    position: absolute;
                    left: ${x}px;
                    top: ${y}px;
                    font-size: 1.5rem;
                    pointer-events: none;
                    z-index: 1000;
                    animation: kids-sparkle-level1 1.5s ease-out forwards;
                `;

                element.style.position = 'relative';
                element.appendChild(sparkle);

                setTimeout(() => sparkle.remove(), 1500);
            }, i * 100);
        }
    }

    addEntranceAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('kids-animated')) {
                    entry.target.classList.add('kids-animated');
                    entry.target.style.animation = 'kids-entrance-level1 0.8s ease-out';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.kids-text, .kids-list-item, .kids-heading').forEach(el => {
            observer.observe(el);
        });
    }

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

            // Celebrate milestones
            if (progress >= 25 && !this.milestone25) {
                this.milestone25 = true;
                this.celebrateMilestone('🎉 Quarter way there!');
            } else if (progress >= 50 && !this.milestone50) {
                this.milestone50 = true;
                this.celebrateMilestone('🌟 Halfway done!');
            } else if (progress >= 75 && !this.milestone75) {
                this.milestone75 = true;
                this.celebrateMilestone('🚀 Almost finished!');
            } else if (progress >= 100 && !this.milestone100) {
                this.milestone100 = true;
                this.celebrateMilestone('🏆 You did it!');
            }
        }
    }

    updateTimeDisplay() {
        const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;

        const timeDisplay = document.querySelector('[data-timer="session"]');
        if (timeDisplay) {
            timeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    incrementInteractions() {
        this.totalInteractions++;

        // Update interaction score
        const maxInteractions = 20; // Reasonable for level 1
        const score = Math.min(100, (this.totalInteractions / maxInteractions) * 100);

        const scoreBar = document.querySelector('.kids-progress-engagement .kids-progress-fill');
        const scorePercent = document.querySelector('.kids-progress-engagement .kids-progress-percent');

        if (scoreBar && scorePercent) {
            scoreBar.style.width = score + '%';
            scorePercent.textContent = Math.round(score) + '%';
        }

        // Encourage interaction
        if (this.totalInteractions % 5 === 0) {
            this.showEncouragement();
        }
    }

    celebrateMilestone(message) {
        this.showBigCelebration(message);
        this.playTouchSound();

        // Add confetti effect
        this.addConfetti();
    }

    showBigCelebration(message) {
        const celebration = document.createElement('div');
        celebration.className = 'kids-big-celebration';
        celebration.innerHTML = `
            <div class="kids-celebration-content">
                <div class="kids-celebration-icon">🎉</div>
                <div class="kids-celebration-message">${message}</div>
            </div>
        `;

        celebration.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(79, 70, 229, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: kids-big-celebration 3s ease-out forwards;
        `;

        document.body.appendChild(celebration);

        setTimeout(() => celebration.remove(), 3000);
    }

    addConfetti() {
        const confettiCount = 20;
        const colors = ['#FF6B9D', '#4A90FF', '#4AFF88', '#FFEB4A', '#FF8E4A'];

        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: fixed;
                    top: -10px;
                    left: ${Math.random() * 100}vw;
                    width: 10px;
                    height: 10px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    z-index: 9999;
                    animation: kids-confetti 3s ease-out forwards;
                `;

                document.body.appendChild(confetti);

                setTimeout(() => confetti.remove(), 3000);
            }, i * 50);
        }
    }

    showEncouragement() {
        const encouragements = [
            '🌟 You\'re doing great!',
            '🎉 Keep exploring!',
            '💫 Awesome learning!',
            '🚀 You\'re amazing!',
            '⭐ Fantastic job!'
        ];

        const message = encouragements[Math.floor(Math.random() * encouragements.length)];
        this.showFeedback(message);
    }

    showFeedback(message) {
        const feedback = document.createElement('div');
        feedback.className = 'kids-feedback-level1';
        feedback.textContent = message;

        feedback.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 1.25rem;
            z-index: 1000;
            box-shadow: 0 8px 16px rgba(139, 92, 246, 0.3);
            animation: kids-feedback-level1 4s ease-out forwards;
        `;

        document.body.appendChild(feedback);
        setTimeout(() => feedback.remove(), 4000);
    }

    showSafetyMessage(message) {
        const safety = document.createElement('div');
        safety.className = 'kids-safety-message';
        safety.innerHTML = `
            <div class="kids-safety-icon">🛡️</div>
            <div class="kids-safety-text">${message}</div>
        `;

        safety.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #16A34A;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: kids-safety-message 3s ease-out forwards;
        `;

        document.body.appendChild(safety);
        setTimeout(() => safety.remove(), 3000);
    }

    showWelcomeMessage() {
        setTimeout(() => {
            this.showFeedback('🌟 Welcome to your learning adventure!');
        }, 500);
    }
}

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes kids-ripple-level1 {
        0% { width: 0; height: 0; opacity: 1; }
        100% { width: 120px; height: 120px; opacity: 0; }
    }

    @keyframes kids-sparkle-level1 {
        0% { opacity: 0; transform: translateY(0) scale(0.5) rotate(0deg); }
        50% { opacity: 1; transform: translateY(-20px) scale(1.2) rotate(180deg); }
        100% { opacity: 0; transform: translateY(-40px) scale(0.8) rotate(360deg); }
    }

    @keyframes kids-entrance-level1 {
        0% { opacity: 0; transform: translateY(30px) scale(0.9); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes kids-feedback-level1 {
        0% { opacity: 0; transform: translateX(100%) scale(0.8); }
        10% { opacity: 1; transform: translateX(0) scale(1.1); }
        15% { transform: scale(1); }
        85% { opacity: 1; transform: translateX(0) scale(1); }
        100% { opacity: 0; transform: translateX(100%) scale(0.9); }
    }

    @keyframes kids-big-celebration {
        0% { opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { opacity: 0; }
    }

    @keyframes kids-confetti {
        0% { transform: translateY(0) rotate(0deg); opacity: 1; }
        100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
    }

    @keyframes kids-safety-message {
        0% { opacity: 0; transform: translateX(-50%) translateY(20px); }
        20% { opacity: 1; transform: translateX(-50%) translateY(0); }
        80% { opacity: 1; transform: translateX(-50%) translateY(0); }
        100% { opacity: 0; transform: translateX(-50%) translateY(-20px); }
    }

    .kids-hovering {
        transform: scale(1.05) !important;
        transition: transform 0.2s ease !important;
    }

    .kids-reading-aloud {
        background: linear-gradient(45deg, #4F46E5, #7C3AED) !important;
        color: white !important;
        animation: kids-reading-pulse 1s ease-in-out infinite !important;
    }

    @keyframes kids-reading-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .kids-big-celebration .kids-celebration-content {
        text-align: center;
        color: white;
    }

    .kids-big-celebration .kids-celebration-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        animation: bounce-gentle 1s ease-in-out infinite;
    }

    .kids-big-celebration .kids-celebration-message {
        font-size: 2rem;
        font-weight: 700;
    }

    .kids-safety-message .kids-safety-icon {
        font-size: 1.5rem;
    }

    .kids-safety-message .kids-safety-text {
        font-size: 1rem;
    }

    /* Mobile optimizations */
    @media (max-width: 768px) {
        .kids-feedback-level1 {
            font-size: 1rem !important;
            padding: 0.75rem 1rem !important;
        }

        .kids-big-celebration .kids-celebration-message {
            font-size: 1.5rem !important;
        }

        .kids-big-celebration .kids-celebration-icon {
            font-size: 3rem !important;
        }
    }
`;

document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.kidsConfig && window.kidsConfig.independenceLevel === 1) {
        window.kidsInteractions = new KidsInteractionsLevel1();
    }
});