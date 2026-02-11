<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'AI Health Assistant - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'ai-assistant';

// Get patient name for greeting
$patientName = $_SESSION['user_name'] ?? 'there';
$firstName = explode(' ', $patientName)[0];

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<link rel="stylesheet" href="../includes/css/ai-chat.css">

<style>
    /* Full-page chat layout */
    .ai-page-chat {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 180px);
        min-height: 400px;
    }

    .ai-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .ai-feature-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
    }

    .ai-feature-card:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }

    .ai-feature-icon {
        font-size: 28px;
        margin-bottom: 8px;
    }

    .ai-feature-title {
        font-weight: 600;
        font-size: 14px;
        color: #374151;
        margin-bottom: 4px;
    }

    .ai-feature-desc {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.4;
    }

    .ai-page-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px 8px 0 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ai-page-input-area {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        background: white;
        border: 1px solid #e5e7eb;
        border-top: none;
        border-radius: 0 0 8px 8px;
    }

    .ai-page-input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        resize: none;
        min-height: 40px;
        max-height: 80px;
        line-height: 1.4;
    }

    .ai-page-input:focus {
        outline: none;
        border-color: #2563eb;
    }

    .ai-page-send {
        width: 40px;
        height: 40px;
        background: #2563eb;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        flex-shrink: 0;
    }

    .ai-page-send:hover:not(:disabled) {
        background: #1d4ed8;
    }

    .ai-page-send:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }

    .ai-page-send svg {
        width: 18px;
        height: 18px;
        fill: white;
    }

    .ai-disclaimer {
        font-size: 11px;
        color: #9ca3af;
        text-align: center;
        margin-top: 8px;
    }
</style>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">🤖 AI Health Assistant</h1>
                <p class="page-subtitle">Get doctor recommendations and health insights powered by AI</p>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="ai-features" id="ai-features">
            <div class="ai-feature-card" onclick="askQuestion('Which doctor should I see for my symptoms?')">
                <div class="ai-feature-icon">🏥</div>
                <div class="ai-feature-title">Doctor Recommendations</div>
                <div class="ai-feature-desc">Describe symptoms, get matched to the right specialist</div>
            </div>
            <div class="ai-feature-card" onclick="askQuestion('Can you summarize my medical history?')">
                <div class="ai-feature-icon">📋</div>
                <div class="ai-feature-title">Health Summary</div>
                <div class="ai-feature-desc">Plain-English overview of your medical records</div>
            </div>
            <div class="ai-feature-card" onclick="askQuestion('What are my current prescriptions and medications?')">
                <div class="ai-feature-icon">💊</div>
                <div class="ai-feature-title">Prescription Info</div>
                <div class="ai-feature-desc">Ask about your current medications</div>
            </div>
            <div class="ai-feature-card" onclick="askQuestion('Explain my recent lab test results')">
                <div class="ai-feature-icon">🔬</div>
                <div class="ai-feature-title">Lab Results</div>
                <div class="ai-feature-desc">Understand your recent test results</div>
            </div>
        </div>

        <!-- Full-Page Chat -->
        <div class="ai-page-chat">
            <div class="ai-page-messages" id="ai-page-messages">
                <div class="ai-chat-message system" id="ai-welcome">
                    👋 Hello
                    <?php echo h($firstName); ?>! I'm your AI Health Assistant. Ask me anything about finding
                    the right doctor, understanding your medical history, or your prescriptions. Click a card above or
                    type below!
                </div>
            </div>
            <div class="ai-page-input-area">
                <textarea id="ai-page-input" class="ai-page-input"
                    placeholder="Describe your symptoms or ask a question..." rows="1"></textarea>
                <button id="ai-page-send" class="ai-page-send" title="Send">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                </button>
            </div>
        </div>
        <p class="ai-disclaimer">⚠️ This is AI-powered guidance, not a medical diagnosis. Always consult a doctor for
            medical advice.</p>

    </main>
</div>

<script>
    (function () {
        'use strict';

        var API_ENDPOINT = '../api/patient_ai_chat.php';
        var isLoading = false;

        var messagesContainer = document.getElementById('ai-page-messages');
        var inputField = document.getElementById('ai-page-input');
        var sendBtn = document.getElementById('ai-page-send');
        var featuresSection = document.getElementById('ai-features');

        // Event listeners
        sendBtn.addEventListener('click', handleSend);
        inputField.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });
        inputField.addEventListener('input', function () {
            inputField.style.height = 'auto';
            inputField.style.height = Math.min(inputField.scrollHeight, 80) + 'px';
        });

        function handleSend() {
            var question = inputField.value.trim();
            if (!question || isLoading) return;

            inputField.value = '';
            inputField.style.height = 'auto';

            // Hide welcome and features on first message
            var welcome = document.getElementById('ai-welcome');
            if (welcome) welcome.style.display = 'none';
            if (featuresSection) {
                featuresSection.style.display = 'none';
                featuresSection = null;
            }

            addMessage(question, 'user');
            sendQuestion(question);
        }

        function addMessage(content, type) {
            var el = document.createElement('div');
            el.className = 'ai-chat-message ' + type;
            el.textContent = content;
            messagesContainer.appendChild(el);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function showTyping() {
            var indicator = document.createElement('div');
            indicator.className = 'ai-typing-indicator';
            indicator.id = 'ai-page-typing';
            indicator.innerHTML = '<span></span><span></span><span></span>';
            messagesContainer.appendChild(indicator);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideTyping() {
            var el = document.getElementById('ai-page-typing');
            if (el) el.remove();
        }

        function sendQuestion(question) {
            if (isLoading) return;
            isLoading = true;
            sendBtn.disabled = true;
            inputField.disabled = true;
            showTyping();

            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: question })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    hideTyping();
                    if (data.success && data.response) {
                        addMessage(data.response, 'ai');
                    } else {
                        addMessage(data.error || 'Sorry, something went wrong.', 'error');
                    }
                })
                .catch(function (err) {
                    console.error('AI Chat error:', err);
                    hideTyping();
                    addMessage('Unable to connect to the AI service. Please try again.', 'error');
                })
                .finally(function () {
                    isLoading = false;
                    sendBtn.disabled = false;
                    inputField.disabled = false;
                    inputField.focus();
                });
        }

        // Expose for feature card clicks
        window.askQuestion = function (q) {
            inputField.value = q;
            handleSend();
        };
    })();
</script>

<?php include '../includes/footer.php'; ?>