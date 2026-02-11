<!-- Patient AI Health Assistant Chat Widget -->
<!-- Include this file in patient portal pages for floating AI chat -->

<!-- CSS (reuses existing ai-chat.css) -->
<link rel="stylesheet" href="../includes/css/ai-chat.css">

<!-- Chat Toggle Button -->
<button id="ai-chat-toggle" class="ai-chat-toggle" title="AI Health Assistant">
    <svg class="chat-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M12 2C6.48 2 2 6.48 2 12c0 1.82.49 3.53 1.34 5L2 22l5-1.34C8.47 21.51 10.18 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm-1 15h2v-2h-2v2zm0-4h2V7h-2v6z" />
    </svg>
    <svg class="close-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
    </svg>
</button>

<!-- Chat Panel -->
<div id="ai-chat-panel" class="ai-chat-panel">
    <!-- Header -->
    <div class="ai-chat-header">
        <div class="ai-chat-header-icon">🤖</div>
        <div class="ai-chat-header-text">
            <h4>AI Health Assistant</h4>
            <p>Ask about doctors, symptoms &amp; your health</p>
        </div>
    </div>

    <!-- Messages Container -->
    <div id="ai-chat-messages" class="ai-chat-messages">
        <!-- Welcome Message -->
        <div class="ai-chat-welcome">
            <h5>👋 Hi! I'm your Health Assistant.</h5>
            <p>I can help you find the right doctor, understand your medical history, and answer questions about your
                health records.</p>

            <div class="ai-chat-suggestions">
                <button class="ai-chat-suggestion">Which doctor for headaches?</button>
                <button class="ai-chat-suggestion">Summarize my medical history</button>
                <button class="ai-chat-suggestion">My active prescriptions</button>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="ai-chat-input-area">
        <textarea id="ai-chat-input" class="ai-chat-input" placeholder="Ask about symptoms, doctors, or your health..."
            rows="1"></textarea>
        <button id="ai-chat-send" class="ai-chat-send" title="Send">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
            </svg>
        </button>
    </div>
</div>

<!-- JavaScript -->
<script src="../includes/js/patient-ai-chat.js"></script>