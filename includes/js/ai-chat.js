/**
 * AI Chat Widget JavaScript
 * Handles chat functionality for Reports pages
 */

(function () {
    'use strict';

    // Configuration
    const API_ENDPOINT = '../api/ai_chat.php';
    const MAX_HISTORY = 20; // Maximum messages to keep in memory

    // State
    let isOpen = false;
    let isLoading = false;
    let chatHistory = [];

    // DOM Elements (initialized on load)
    let toggleBtn, chatPanel, messagesContainer, inputField, sendBtn;

    /**
     * Initialize the chat widget
     */
    function init() {
        // Get DOM elements
        toggleBtn = document.getElementById('ai-chat-toggle');
        chatPanel = document.getElementById('ai-chat-panel');
        messagesContainer = document.getElementById('ai-chat-messages');
        inputField = document.getElementById('ai-chat-input');
        sendBtn = document.getElementById('ai-chat-send');

        if (!toggleBtn || !chatPanel) {
            console.warn('AI Chat: Required elements not found');
            return;
        }

        // Event listeners
        toggleBtn.addEventListener('click', toggleChat);
        sendBtn.addEventListener('click', handleSend);
        inputField.addEventListener('keydown', handleKeyDown);
        inputField.addEventListener('input', autoResize);

        // Suggestion buttons
        document.querySelectorAll('.ai-chat-suggestion').forEach(btn => {
            btn.addEventListener('click', function () {
                inputField.value = this.textContent;
                handleSend();
            });
        });

        console.log('AI Chat widget initialized');
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
        const question = inputField.value.trim();

        if (!question || isLoading) {
            return;
        }

        // Clear input
        inputField.value = '';
        autoResize();

        // Add user message
        addMessage(question, 'user');

        // Hide welcome if visible
        const welcome = document.querySelector('.ai-chat-welcome');
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
        const messageEl = document.createElement('div');
        messageEl.className = `ai-chat-message ${type}`;
        messageEl.textContent = content;

        messagesContainer.appendChild(messageEl);
        scrollToBottom();

        // Track history
        chatHistory.push({ content, type, timestamp: Date.now() });
        if (chatHistory.length > MAX_HISTORY) {
            chatHistory.shift();
        }
    }

    /**
     * Show typing indicator
     */
    function showTypingIndicator() {
        const indicator = document.createElement('div');
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
        const indicator = document.getElementById('ai-typing');
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
     * Collect report context from the page
     */
    function collectReportContext() {
        const context = {};

        // Collect from data attributes
        document.querySelectorAll('[data-ai-context]').forEach(el => {
            const key = el.getAttribute('data-ai-context');
            let value = el.getAttribute('data-ai-value') || el.textContent.trim();

            // Clean up the value (remove currency symbols, commas for parsing)
            value = value.replace(/^\$/, '').trim();

            context[key] = value;
        });

        // Try to get context from hidden JSON block if present
        const contextEl = document.getElementById('ai-report-context');
        if (contextEl) {
            try {
                const jsonContext = JSON.parse(contextEl.textContent);
                Object.assign(context, jsonContext);
            } catch (e) {
                console.warn('AI Chat: Could not parse context JSON');
            }
        }

        return context;
    }

    /**
     * Get branch name if available
     */
    function getBranchName() {
        const branchEl = document.querySelector('[data-ai-branch-name]');
        return branchEl ? branchEl.getAttribute('data-ai-branch-name') : null;
    }

    /**
     * Send question to the API
     */
    async function sendQuestion(question) {
        if (isLoading) return;

        isLoading = true;
        setLoadingState(true);
        showTypingIndicator();

        try {
            const context = collectReportContext();
            const branchName = getBranchName();

            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    question: question,
                    context: context,
                    branch_name: branchName
                })
            });

            const data = await response.json();

            hideTypingIndicator();

            if (data.success && data.response) {
                addMessage(data.response, 'ai');
            } else {
                addMessage(data.error || 'Sorry, something went wrong. Please try again.', 'error');
            }

        } catch (error) {
            console.error('AI Chat error:', error);
            hideTypingIndicator();
            addMessage('Unable to connect to the AI service. Please check your connection and try again.', 'error');
        } finally {
            isLoading = false;
            setLoadingState(false);
        }
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

    // Expose toggle function globally for external use if needed
    window.AIChatWidget = {
        toggle: toggleChat,
        isOpen: () => isOpen
    };

})();
