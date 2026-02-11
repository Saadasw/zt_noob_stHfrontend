/**
 * Patient AI Health Assistant Chat Widget JavaScript
 * Handles chat functionality for Patient Portal pages
 * 
 * Adapted from ai-chat.js (admin version).
 * Key difference: No page context collection — backend fetches patient data server-side.
 */

(function () {
    'use strict';

    // Configuration
    const API_ENDPOINT = '../api/patient_ai_chat.php';
    const MAX_HISTORY = 20;

    // State
    let isOpen = false;
    let isLoading = false;
    let chatHistory = [];

    // DOM Elements
    let toggleBtn, chatPanel, messagesContainer, inputField, sendBtn;

    /**
     * Initialize the chat widget
     */
    function init() {
        toggleBtn = document.getElementById('ai-chat-toggle');
        chatPanel = document.getElementById('ai-chat-panel');
        messagesContainer = document.getElementById('ai-chat-messages');
        inputField = document.getElementById('ai-chat-input');
        sendBtn = document.getElementById('ai-chat-send');

        if (!toggleBtn || !chatPanel) {
            console.warn('Patient AI Chat: Required elements not found');
            return;
        }

        // Event listeners
        toggleBtn.addEventListener('click', toggleChat);
        sendBtn.addEventListener('click', handleSend);
        inputField.addEventListener('keydown', handleKeyDown);
        inputField.addEventListener('input', autoResize);

        // Suggestion buttons
        document.querySelectorAll('.ai-chat-suggestion').forEach(function (btn) {
            btn.addEventListener('click', function () {
                inputField.value = this.textContent;
                handleSend();
            });
        });

        console.log('Patient AI Chat widget initialized');
    }

    /**
     * Toggle chat panel open/closed
     */
    function toggleChat() {
        isOpen = !isOpen;

        if (isOpen) {
            chatPanel.classList.add('open');
            toggleBtn.classList.add('active');
            inputField.focus();
        } else {
            chatPanel.classList.remove('open');
            toggleBtn.classList.remove('active');
        }
    }

    /**
     * Handle send button click
     */
    function handleSend() {
        var question = inputField.value.trim();

        if (!question || isLoading) {
            return;
        }

        // Clear input
        inputField.value = '';
        autoResize();

        // Add user message
        addMessage(question, 'user');

        // Hide welcome if visible
        var welcome = document.querySelector('.ai-chat-welcome');
        if (welcome) {
            welcome.style.display = 'none';
        }

        // Send to API
        sendQuestion(question);
    }

    /**
     * Handle keyboard events
     */
    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    }

    /**
     * Auto-resize textarea
     */
    function autoResize() {
        inputField.style.height = 'auto';
        inputField.style.height = Math.min(inputField.scrollHeight, 80) + 'px';
    }

    /**
     * Add a message to the chat
     */
    function addMessage(content, type) {
        var messageEl = document.createElement('div');
        messageEl.className = 'ai-chat-message ' + type;
        messageEl.textContent = content;

        messagesContainer.appendChild(messageEl);
        scrollToBottom();

        // Track history
        chatHistory.push({ content: content, type: type, timestamp: Date.now() });
        if (chatHistory.length > MAX_HISTORY) {
            chatHistory.shift();
        }
    }

    /**
     * Show typing indicator
     */
    function showTypingIndicator() {
        var indicator = document.createElement('div');
        indicator.className = 'ai-typing-indicator';
        indicator.id = 'ai-typing';
        indicator.innerHTML = '<span></span><span></span><span></span>';
        messagesContainer.appendChild(indicator);
        scrollToBottom();
    }

    /**
     * Hide typing indicator
     */
    function hideTypingIndicator() {
        var indicator = document.getElementById('ai-typing');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Scroll messages to bottom
     */
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    /**
     * Send question to the API
     */
    function sendQuestion(question) {
        if (isLoading) return;

        isLoading = true;
        setLoadingState(true);
        showTypingIndicator();

        fetch(API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question: question
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                hideTypingIndicator();

                if (data.success && data.response) {
                    addMessage(data.response, 'ai');
                } else {
                    addMessage(data.error || 'Sorry, something went wrong. Please try again.', 'error');
                }
            })
            .catch(function (error) {
                console.error('Patient AI Chat error:', error);
                hideTypingIndicator();
                addMessage('Unable to connect to the AI service. Please check your connection and try again.', 'error');
            })
            .finally(function () {
                isLoading = false;
                setLoadingState(false);
            });
    }

    /**
     * Set loading state on UI
     */
    function setLoadingState(loading) {
        sendBtn.disabled = loading;
        inputField.disabled = loading;

        if (!loading) {
            inputField.focus();
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose toggle function globally
    window.PatientAIChatWidget = {
        toggle: toggleChat,
        isOpen: function () { return isOpen; }
    };

})();
