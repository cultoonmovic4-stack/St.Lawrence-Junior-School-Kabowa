/**
 * St. Lawrence Junior School Kabowa — AI School Assistant
 * Frontend Controller (Voice & Text Chat Receptionist)
 * Complete Native Web Speech Two-Way Voice Experience & Text Chat
 */

class StLawrenceChatbot {
    constructor() {
        window.chatbot = this; // Immediately available for inline and global access
        this.apiUrl = this.detectApiUrl();
        this.isOpen = false;
        this.isTyping = false;
        this.isVoiceMode = false;
        
        // Voice properties (Native Web Speech API)
        this.recognition = null;
        this.synthesis = window.speechSynthesis;
        this.isListening = false;
        this.isSpeaking = false;
        this.isProcessing = false;
        this.micPermissionGranted = false;
        this.selectedVoice = null;
        this.shouldStopSpeaking = false;
        this.lastTranscript = '';
        
        this.init();
    }

    detectApiUrl() {
        return window.location.pathname.includes('/frontend/') 
            ? '../backend/api/chatbot/chat.php' 
            : 'backend/api/chatbot/chat.php';
    }

    getLogoUrl() {
        return window.location.pathname.includes('/frontend/') 
            ? '../img/5.jpg' 
            : 'img/5.jpg';
    }
    
    init() {
        this.createChatWidget();
        this.attachEventListeners();
        this.initializeVoices();

        // Ask global layout helpers to re-pin floating controls if needed
        if (typeof window.pinFloatingControls === 'function') {
            window.pinFloatingControls();
        }
    }
    
    createChatWidget() {
        const logoUrl = this.getLogoUrl();
        const fallbackLogo = `if(this.src.indexOf('../img/5.jpg')!==-1){this.src='img/5.jpg';}else{this.src='../img/5.jpg';}`;

        const chatHTML = `
            <!-- Floating Chat Launcher -->
            <button type="button" class="chat-button" id="chatButton" onclick="window.chatbot.toggleChat()" aria-label="Chat with St. Lawrence Junior School" title="Chat with St. Lawrence">
                <div class="chat-tooltip" id="chatTooltip">Chat with St. Lawrence</div>
                <img src="${logoUrl}" onerror="${fallbackLogo}" alt="St. Lawrence Junior School Logo">
                <div class="chat-unread-dot" id="chatUnreadDot"></div>
            </button>
            
            <!-- Chatbot Window -->
            <div class="chat-widget" id="chatWidget" role="dialog" aria-modal="true" aria-labelledby="chatHeaderTitle">
                <!-- Header -->
                <div class="chat-header">
                    <div class="chat-header-left">
                        <div class="chat-header-logo">
                            <img src="${logoUrl}" onerror="${fallbackLogo}" alt="St. Lawrence Logo">
                        </div>
                        <div class="chat-header-info">
                            <h3 class="chat-header-title" id="chatHeaderTitle">St. Lawrence Junior School</h3>
                            <span class="chat-header-status" id="chatHeaderStatus">School Assistant • Online</span>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button type="button" class="chat-header-btn" id="headerVoiceToggle" onclick="window.chatbot.toggleVoiceMode()" title="Voice Assistant Mode" aria-label="Toggle Voice Mode">
                            <i class="fas fa-microphone" id="headerVoiceIcon"></i>
                        </button>
                        <button type="button" class="chat-header-btn" id="chatMinimize" onclick="window.chatbot.closeChat()" title="Minimize chat" aria-label="Minimize chat">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="chat-header-btn" id="chatClose" onclick="window.chatbot.closeChat()" title="Close chat" aria-label="Close chat">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Status Bar (General Updates) -->
                <div class="chat-status-bar" id="chatStatusBar" aria-live="polite">
                    <div class="status-left" id="statusLeft">Ready</div>
                    <button type="button" class="status-stop-btn" id="statusStopBtn" onclick="window.chatbot.stopSpeaking()" style="display: none;">Stop</button>
                </div>
                
                <!-- Chat Body (Messages & Conversation Log) -->
                <div class="chat-body" id="chatBody">
                    <!-- Welcome Card -->
                    <div class="chat-welcome-card" id="chatWelcomeCard">
                        <h4>Hello! 👋</h4>
                        <p>Welcome to St. Lawrence Junior School Kabowa.</p>
                        <p>I'm here to help with school fees, programmes, admissions, location and other school information.</p>
                        <strong>What would you like to know?</strong>
                        <div class="welcome-actions-grid">
                            <button type="button" class="welcome-action-btn" data-query="What are the school fees?">School Fees</button>
                            <button type="button" class="welcome-action-btn" data-query="What programmes do you offer?">Our Programmes</button>
                            <button type="button" class="welcome-action-btn" data-query="How do I apply for admission?">Admissions</button>
                            <button type="button" class="welcome-action-btn" data-query="Where is the school located and how can I contact you?">Location & Contact</button>
                        </div>
                    </div>
                </div>

                <!-- Dedicated Voice Chat / Call Interface -->
                <div class="voice-interface" id="voiceInterface" style="display: none;">
                    <div class="voice-card">
                        <div class="voice-avatar-wrap">
                            <div class="voice-avatar">
                                <img src="${logoUrl}" onerror="${fallbackLogo}" alt="St. Lawrence Assistant">
                            </div>
                        </div>
                        
                        <h4 class="voice-school-name">St. Lawrence Junior School</h4>
                        <p class="voice-role-title">School Assistant • Voice Mode</p>
                        
                        <!-- Status Badge -->
                        <div class="voice-status-badge" id="voiceStatusBadge">
                            <span class="voice-status-dot" id="voiceStatusDot"></span>
                            <span class="voice-status-text" id="voiceStatusText">Tap the microphone to speak</span>
                        </div>
                        
                        <!-- Live Transcript Box -->
                        <div class="voice-transcript-box" id="voiceTranscriptBox">
                            <p class="voice-transcript-lead" id="voiceTranscriptLead">Tap the microphone below to ask any question about school fees, admissions, programmes, or location.</p>
                        </div>
                        
                        <!-- Main Interactive Microphone Button -->
                        <div class="voice-mic-container">
                            <button type="button" class="voice-main-mic" id="voiceMainMic" onclick="window.chatbot.toggleListening()" aria-label="Tap to speak">
                                <i class="fas fa-microphone" id="voiceMainMicIcon"></i>
                            </button>
                            <span class="voice-mic-hint" id="voiceMicHint" onclick="window.chatbot.toggleListening()" style="cursor: pointer;">Tap to Speak</span>
                        </div>

                        <!-- Voice Actions -->
                        <div class="voice-actions-bar">
                            <button type="button" class="voice-action-btn voice-stop-btn" id="voiceStopBtn" onclick="window.chatbot.stopSpeaking()" style="display: none;">
                                <i class="fas fa-stop"></i> Stop Speaking
                            </button>
                            <button type="button" class="voice-action-btn voice-switch-chat-btn" id="voiceSwitchChatBtn" onclick="window.chatbot.closeVoiceMode()">
                                <i class="fas fa-comments"></i> Return to Text Chat
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Footer / Input Bar -->
                <div class="chat-footer" id="chatFooter">
                    <input 
                        type="text" 
                        class="chat-input" 
                        id="chatInput" 
                        placeholder="Type your message..."
                        autocomplete="off"
                        aria-label="Type your message to St. Lawrence Assistant"
                    >
                    <button type="button" class="chat-mic-btn" id="micBtn" onclick="window.chatbot.openVoiceMode()" title="Speak to Assistant" aria-label="Voice input mode">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button type="button" class="chat-send-btn" id="chatSend" onclick="window.chatbot.sendMessage()" title="Send message" aria-label="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', chatHTML);
    }
    
    attachEventListeners() {
        // Direct event listener attachment for standard and dynamic events
        const chatInput = document.getElementById('chatInput');
        const chatBody = document.getElementById('chatBody');
        
        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Welcome actions delegation
        if (chatBody) {
            chatBody.addEventListener('click', (e) => {
                const welcomeBtn = e.target.closest('.welcome-action-btn');
                if (welcomeBtn) {
                    const query = welcomeBtn.getAttribute('data-query');
                    if (query) {
                        this.handleQuickAction(query);
                    }
                }
            });
        }
    }
    
    toggleChat() {
        this.isOpen ? this.closeChat() : this.openChat();
    }

    openChat() {
        const chatWidget = document.getElementById('chatWidget');
        const chatButton = document.getElementById('chatButton');
        const chatUnreadDot = document.getElementById('chatUnreadDot');
        const chatInput = document.getElementById('chatInput');
        
        this.isOpen = true;
        if (chatWidget) chatWidget.classList.add('active');
        if (chatButton) chatButton.classList.add('active');
        if (chatUnreadDot) chatUnreadDot.style.display = 'none';
        
        if (!this.isVoiceMode && chatInput) {
            setTimeout(() => chatInput.focus(), 150);
        }
    }
    
    closeChat() {
        const chatWidget = document.getElementById('chatWidget');
        const chatButton = document.getElementById('chatButton');
        
        this.isOpen = false;
        if (chatWidget) chatWidget.classList.remove('active');
        if (chatButton) chatButton.classList.remove('active');
        
        // Stop any active speech or listening
        this.stopSpeaking();
        this.stopListening();
    }
    
    // =========================================================================
    // DEDICATED VOICE MODE CONTROLLER
    // =========================================================================

    toggleVoiceMode() {
        this.isVoiceMode ? this.closeVoiceMode() : this.openVoiceMode();
    }

    openVoiceMode() {
        this.isVoiceMode = true;
        const voiceInterface = document.getElementById('voiceInterface');
        const chatBody = document.getElementById('chatBody');
        const chatFooter = document.getElementById('chatFooter');
        const headerVoiceIcon = document.getElementById('headerVoiceIcon');
        const headerStatus = document.getElementById('chatHeaderStatus');

        if (chatBody) chatBody.style.display = 'none';
        if (chatFooter) chatFooter.style.display = 'none';
        if (voiceInterface) {
            voiceInterface.classList.add('active');
            voiceInterface.style.display = 'flex';
        }

        if (headerVoiceIcon) {
            headerVoiceIcon.className = 'fas fa-comments';
        }
        if (headerStatus) {
            headerStatus.textContent = 'Voice Mode • Active';
        }

        // Prompt microphone permission and start listening directly
        this.startListening();
    }

    closeVoiceMode() {
        this.isVoiceMode = false;
        this.stopSpeaking();
        this.stopListening();

        const voiceInterface = document.getElementById('voiceInterface');
        const chatBody = document.getElementById('chatBody');
        const chatFooter = document.getElementById('chatFooter');
        const headerVoiceIcon = document.getElementById('headerVoiceIcon');
        const headerStatus = document.getElementById('chatHeaderStatus');

        if (voiceInterface) {
            voiceInterface.classList.remove('active');
            voiceInterface.style.display = 'none';
        }
        if (chatBody) chatBody.style.display = 'flex';
        if (chatFooter) chatFooter.style.display = 'flex';

        if (headerVoiceIcon) {
            headerVoiceIcon.className = 'fas fa-microphone';
        }
        if (headerStatus) {
            headerStatus.textContent = 'School Assistant • Online';
        }

        this.scrollToBottom();
        const chatInput = document.getElementById('chatInput');
        if (chatInput) {
            setTimeout(() => chatInput.focus(), 100);
        }
    }

    setVoiceStatus(state, message) {
        const badge = document.getElementById('voiceStatusBadge');
        const text = document.getElementById('voiceStatusText');
        const micBtn = document.getElementById('voiceMainMic');
        const micHint = document.getElementById('voiceMicHint');

        if (!badge || !text) return;

        badge.className = 'voice-status-badge';
        if (micBtn) micBtn.classList.remove('listening');

        switch (state) {
            case 'listening':
                badge.classList.add('listening');
                text.textContent = message || 'Listening...';
                if (micBtn) micBtn.classList.add('listening');
                if (micHint) micHint.textContent = 'Listening... Speak now';
                break;
            case 'processing':
                badge.classList.add('processing');
                text.textContent = message || 'Thinking...';
                if (micHint) micHint.textContent = 'Finding answer...';
                break;
            case 'speaking':
                badge.classList.add('speaking');
                text.textContent = message || 'Speaking...';
                if (micHint) micHint.textContent = 'Speaking answer...';
                break;
            case 'ready':
            default:
                text.textContent = message || 'Tap the microphone to speak';
                if (micHint) micHint.textContent = 'Tap to Speak';
                break;
        }
    }

    toggleListening() {
        if (this.isSpeaking) {
            this.stopSpeaking();
            this.startListening();
            return;
        }

        if (this.isListening) {
            this.stopListening();
            this.setVoiceStatus('ready', 'Tap the microphone to speak');
        } else {
            this.startListening();
        }
    }

    startListening() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const transcriptLead = document.getElementById('voiceTranscriptLead');

        if (!SpeechRecognition) {
            this.isListening = false;
            this.setVoiceStatus('ready', 'Voice not supported');
            if (transcriptLead) {
                transcriptLead.innerHTML = '<span style="color: #dc3545; font-weight: 600;">Voice recognition is not supported in this browser.</span><br><small style="color: #64748b;">Please use Google Chrome, Microsoft Edge, or Safari, or type your question below.</small>';
            }
            return;
        }

        // Stop any active speech output before starting to listen
        this.stopSpeaking();

        // Provide immediate visual feedback on tap
        this.isListening = true;
        this.setVoiceStatus('listening', 'Listening...');
        if (transcriptLead) {
            transcriptLead.textContent = 'Listening... Speak your question now.';
        }

        this.beginSpeechRecognition();
    }

    beginSpeechRecognition() {
        const transcriptLead = document.getElementById('voiceTranscriptLead');
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) return;

        // Clean up previous instance cleanly without firing its old callbacks
        if (this.recognition) {
            try {
                this.recognition.onstart = null;
                this.recognition.onresult = null;
                this.recognition.onerror = null;
                this.recognition.onend = null;
                this.recognition.abort();
            } catch (e) {}
            this.recognition = null;
        }

        try {
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = false; // Turnaround per question
            this.recognition.interimResults = true;
            this.recognition.lang = 'en-US';
            this.recognition.maxAlternatives = 1;

            let finalTranscriptReceived = false;

            this.recognition.onstart = () => {
                this.isListening = true;
                this.setVoiceStatus('listening', 'Listening...');
                if (transcriptLead) {
                    transcriptLead.textContent = 'Listening... Speak your question now.';
                }
            };

            this.recognition.onresult = (event) => {
                let interim = '';
                let final = '';

                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        final += event.results[i][0].transcript;
                    } else {
                        interim += event.results[i][0].transcript;
                    }
                }

                if (interim && transcriptLead) {
                    transcriptLead.textContent = `You: "${interim}..."`;
                }

                if (final && final.trim()) {
                    finalTranscriptReceived = true;
                    this.isListening = false;
                    try {
                        this.recognition.stop();
                    } catch (e) {}
                    this.processVoiceMessage(final.trim());
                }
            };

            this.recognition.onerror = (event) => {
                console.warn('Speech recognition notice:', event.error);
                this.isListening = false;

                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    this.setVoiceStatus('ready', 'Microphone access needed');
                    if (transcriptLead) {
                        transcriptLead.innerHTML = '<span style="color: #dc3545; font-weight: 600;">Microphone access was not granted.</span><br><small style="color: #64748b;">Please click the camera/lock icon in your browser address bar to allow microphone access, then tap to speak again.</small>';
                    }
                } else if (event.error === 'no-speech') {
                    this.setVoiceStatus('ready', 'Tap the microphone to speak');
                    if (transcriptLead && !finalTranscriptReceived) {
                        transcriptLead.textContent = "No speech detected. Tap the microphone and try speaking again.";
                    }
                } else if (event.error === 'network') {
                    this.setVoiceStatus('ready', 'Tap the microphone to speak');
                    if (transcriptLead && !finalTranscriptReceived) {
                        transcriptLead.textContent = "Speech recognition network paused. Please tap the microphone or type your question.";
                    }
                } else if (event.error !== 'aborted') {
                    this.setVoiceStatus('ready', 'Tap the microphone to speak');
                    if (transcriptLead && !finalTranscriptReceived) {
                        transcriptLead.textContent = "I couldn't hear that clearly. Please tap the microphone to try again.";
                    }
                }
            };

            this.recognition.onend = () => {
                this.isListening = false;
                if (!this.isProcessing && !this.isSpeaking && !finalTranscriptReceived) {
                    this.setVoiceStatus('ready', 'Tap the microphone to speak');
                }
            };

            this.recognition.start();
        } catch (e) {
            console.warn('Speech recognition start error:', e);
            this.isListening = false;
            this.setVoiceStatus('ready', 'Tap the microphone to speak');
            if (transcriptLead) {
                transcriptLead.textContent = 'Could not activate microphone. Tap the microphone to try again or type your question.';
            }
        }
    }

    stopListening() {
        this.isListening = false;
        if (this.recognition) {
            try {
                this.recognition.abort();
            } catch (e) {}
            this.recognition = null;
        }
        const micBtn = document.getElementById('voiceMainMic');
        if (micBtn) micBtn.classList.remove('listening');
    }

    async processVoiceMessage(transcript) {
        if (!transcript || this.isProcessing) return;

        this.isProcessing = true;
        this.setVoiceStatus('processing', 'Thinking...');

        const transcriptLead = document.getElementById('voiceTranscriptLead');
        if (transcriptLead) {
            transcriptLead.innerHTML = `<strong>You:</strong> "${this.escapeHtml(transcript)}"<br><span style="color: #64748b; font-size: 11px;">Thinking...</span>`;
        }

        // Add user message to conversation log
        this.addUserMessage(transcript);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'chat',
                    message: transcript
                })
            });

            const data = await response.json();
            this.isProcessing = false;

            if (data.success) {
                // Add to conversation log
                this.addBotMessage(data.response);
                if (data.suggestions && Array.isArray(data.suggestions) && data.suggestions.length > 0) {
                    this.addQuickActions(data.suggestions);
                }

                // Update transcript box with bot reply
                if (transcriptLead) {
                    const plainSnippet = this.cleanTextForSpeech(data.response);
                    transcriptLead.innerHTML = `<strong>You:</strong> "${this.escapeHtml(transcript)}"<br><br><strong>Assistant:</strong> ${this.escapeHtml(plainSnippet.substring(0, 140))}${plainSnippet.length > 140 ? '...' : ''}`;
                }

                // Speak the response aloud
                const speechText = this.cleanTextForSpeech(data.response);
                this.speak(speechText, () => {
                    // When speech completes, return to ready for two-way conversation
                    this.setVoiceStatus('ready', 'Tap the microphone to speak');
                    const stopBtn = document.getElementById('voiceStopBtn');
                    if (stopBtn) stopBtn.style.display = 'none';
                });
            } else {
                const errMsg = "I'm sorry, I encountered an error. Please try again or call our school administration at +256 701 420 506.";
                this.addBotMessage(errMsg);
                if (transcriptLead) {
                    transcriptLead.textContent = errMsg;
                }
                this.speak(errMsg, () => {
                    this.setVoiceStatus('ready', 'Tap the microphone to speak');
                });
            }
        } catch (err) {
            console.error('Error processing voice message:', err);
            this.isProcessing = false;
            const errConn = "I'm sorry, I'm having trouble connecting. Please try again or call +256 701 420 506.";
            this.addBotMessage(errConn);
            if (transcriptLead) {
                transcriptLead.textContent = errConn;
            }
            this.speak(errConn, () => {
                this.setVoiceStatus('ready', 'Tap the microphone to speak');
            });
        }
    }

    // =========================================================================
    // SPEECH SYNTHESIS (VOICE RESPONSE)
    // =========================================================================

    initializeVoices() {
        if ('speechSynthesis' in window) {
            const loadVoices = () => {
                const voices = window.speechSynthesis.getVoices();
                this.selectedVoice = voices.find(voice => 
                    voice.lang.startsWith('en') && (voice.name.includes('Female') || voice.name.includes('Natural') || voice.name.includes('Google') || voice.name.includes('Samantha'))
                ) || voices.find(voice => voice.lang.startsWith('en')) || voices[0];
            };

            loadVoices();
            if (window.speechSynthesis.onvoiceschanged !== undefined) {
                window.speechSynthesis.onvoiceschanged = loadVoices;
            }
        }
    }

    speak(text, onDone = null) {
        if (!('speechSynthesis' in window) || !text) {
            if (onDone) onDone();
            return;
        }

        this.stopSpeaking();
        this.shouldStopSpeaking = false;

        const utterance = new SpeechSynthesisUtterance(text);
        if (this.selectedVoice) {
            utterance.voice = this.selectedVoice;
        }
        utterance.rate = 0.95; // Natural clear speech rate suitable for parents
        utterance.pitch = 1.0;

        utterance.onstart = () => {
            this.isSpeaking = true;
            this.setVoiceStatus('speaking', 'Speaking...');
            const stopBtn = document.getElementById('voiceStopBtn');
            if (stopBtn) stopBtn.style.display = 'inline-flex';
        };

        utterance.onend = () => {
            this.isSpeaking = false;
            const stopBtn = document.getElementById('voiceStopBtn');
            if (stopBtn) stopBtn.style.display = 'none';
            if (onDone) onDone();
        };

        utterance.onerror = (e) => {
            console.warn('Speech synthesis notice:', e);
            this.isSpeaking = false;
            const stopBtn = document.getElementById('voiceStopBtn');
            if (stopBtn) stopBtn.style.display = 'none';
            if (onDone) onDone();
        };

        window.speechSynthesis.speak(utterance);
    }

    stopSpeaking() {
        this.shouldStopSpeaking = true;
        this.isSpeaking = false;
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.cancel();
            } catch (e) {}
        }
        const stopBtn = document.getElementById('voiceStopBtn');
        if (stopBtn) stopBtn.style.display = 'none';
        if (this.isVoiceMode && !this.isListening && !this.isProcessing) {
            this.setVoiceStatus('ready', 'Tap the microphone to speak');
        }
    }

    cleanTextForSpeech(text) {
        if (!text) return '';
        // Remove markdown formatting
        text = text.replace(/\*\*(.*?)\*\*/g, '$1');
        text = text.replace(/\*(.*?)\*/g, '$1');
        text = text.replace(/\[(.*?)\]\(.*?\)/g, '$1');
        text = text.replace(/https?:\/\/[^\s]+/g, '');
        
        // Remove emojis safely
        try {
            text = text.replace(/[\p{Extended_Pictographic}\p{Emoji_Presentation}]/gu, '');
        } catch (e) {
            text = text.replace(/[📍📧📞📮🏫⏰📝🎒📚🎓💰🏠👔⚽🎨🔬🏃🍽️🚌🔒👨‍👩‍👧‍👦📅🏆♿✅❌👋☀️📖🚗🌅🍳]/g, '');
        }
        
        // Pronunciation adjustments
        text = text.replace(/[•✓✔\-\*]/g, ' ');
        text = text.replace(/\n+/g, '. ');
        text = text.replace(/\.{2,}/g, '.');
        text = text.replace(/\s+/g, ' ');
        text = text.replace(/\bUGX\b/g, 'Uganda Shillings');
        text = text.replace(/(\d),(\d)/g, '$1$2');
        
        return text.trim();
    }

    // =========================================================================
    // TEXT CHAT & MESSAGE RENDERING
    // =========================================================================

    addBotMessage(message, showAvatar = true) {
        const chatBody = document.getElementById('chatBody');
        if (!chatBody) return;

        const timestamp = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const logoUrl = this.getLogoUrl();
        const fallbackLogo = `if(this.src.indexOf('../img/5.jpg')!==-1){this.src='img/5.jpg';}else{this.src='../img/5.jpg';}`;
        
        const messageHTML = `
            <div class="chat-message bot">
                ${showAvatar ? `
                <div class="message-avatar">
                    <img src="${logoUrl}" onerror="${fallbackLogo}" alt="School Assistant">
                </div>
                ` : '<div style="width: 28px;"></div>'}
                <div class="message-wrapper">
                    <div class="message-content">${this.formatMessage(message)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            </div>
        `;
        
        chatBody.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }
    
    addUserMessage(message) {
        const chatBody = document.getElementById('chatBody');
        if (!chatBody) return;

        const timestamp = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        const messageHTML = `
            <div class="chat-message user">
                <div class="message-wrapper">
                    <div class="message-content">${this.escapeHtml(message)}</div>
                    <div class="message-time">${timestamp}</div>
                </div>
            </div>
        `;
        
        chatBody.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }
    
    addQuickActions(actions) {
        const chatBody = document.getElementById('chatBody');
        if (!chatBody || !actions || actions.length === 0) return;
        
        let actionsHTML = '<div class="quick-actions">';
        actions.forEach(action => {
            actionsHTML += `
                <button type="button" class="quick-action-btn" onclick="window.chatbot.handleQuickAction('${this.escapeHtml(action)}')">
                    ${this.escapeHtml(action)}
                </button>
            `;
        });
        actionsHTML += '</div>';
        
        chatBody.insertAdjacentHTML('beforeend', actionsHTML);
        this.scrollToBottom();
    }
    
    showTypingIndicator() {
        const chatBody = document.getElementById('chatBody');
        if (!chatBody || this.isTyping) return;

        const logoUrl = this.getLogoUrl();
        const fallbackLogo = `if(this.src.indexOf('../img/5.jpg')!==-1){this.src='img/5.jpg';}else{this.src='../img/5.jpg';}`;
        
        const typingHTML = `
            <div class="chat-message bot typing-message" id="typingIndicator">
                <div class="message-avatar">
                    <img src="${logoUrl}" onerror="${fallbackLogo}" alt="Assistant">
                </div>
                <div class="message-wrapper">
                    <div class="typing-bubble">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            </div>
        `;
        
        chatBody.insertAdjacentHTML('beforeend', typingHTML);
        this.scrollToBottom();
        this.isTyping = true;
    }
    
    hideTypingIndicator() {
        const typingMessage = document.getElementById('typingIndicator');
        if (typingMessage) {
            typingMessage.remove();
        }
        this.isTyping = false;
    }
    
    async sendMessage() {
        const chatInput = document.getElementById('chatInput');
        const message = chatInput ? chatInput.value.trim() : '';
        
        if (!message || this.isTyping) return;
        
        this.addUserMessage(message);
        chatInput.value = '';
        
        this.showTypingIndicator();
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'chat',
                    message: message
                })
            });
            
            const data = await response.json();
            
            setTimeout(() => {
                this.hideTypingIndicator();
                
                if (data.success) {
                    this.addBotMessage(data.response);
                    if (data.suggestions && Array.isArray(data.suggestions) && data.suggestions.length > 0) {
                        this.addQuickActions(data.suggestions);
                    }
                } else {
                    this.addBotMessage("I'm sorry, I encountered an error. Please try again or call our school administration at +256 701 420 506.");
                }
            }, 500);
            
        } catch (error) {
            console.error('Error sending message:', error);
            this.hideTypingIndicator();
            this.addBotMessage("I'm sorry, I'm having trouble connecting to the school server. Please try again or call +256 701 420 506.");
        }
    }
    
    async handleQuickAction(question) {
        this.addUserMessage(question);
        this.showTypingIndicator();
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'quickAction',
                    question: question
                })
            });
            
            const data = await response.json();
            
            setTimeout(() => {
                this.hideTypingIndicator();
                
                if (data.success) {
                    this.addBotMessage(data.response);
                    if (data.suggestions && Array.isArray(data.suggestions) && data.suggestions.length > 0) {
                        this.addQuickActions(data.suggestions);
                    }
                } else {
                    this.addBotMessage("I'm sorry, I encountered an error. Please try again.");
                }
            }, 500);
            
        } catch (error) {
            console.error('Error handling quick action:', error);
            this.hideTypingIndicator();
            this.addBotMessage("I'm sorry, I'm having trouble connecting. Please try again.");
        }
    }
    
    formatMessage(message) {
        if (!message) return '';
        const escaped = this.escapeHtml(message);
        let formatted = escaped.replace(/\n/g, '<br>');
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        return formatted;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    scrollToBottom() {
        const chatBody = document.getElementById('chatBody');
        if (!chatBody) return;
        setTimeout(() => {
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 80);
    }
}

// Global initialization
let chatbot;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        chatbot = new StLawrenceChatbot();
        window.chatbot = chatbot;
    });
} else {
    chatbot = new StLawrenceChatbot();
    window.chatbot = chatbot;
}
