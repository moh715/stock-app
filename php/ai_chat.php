<?php
?>
<div id="ai-chat-widget">
    <div id="ai-chat-box">
        <div class="ai-chat-header">💬 Stock AI Assistant</div>
        <div class="ai-chat-messages" id="ai-messages"></div>
        <div class="ai-chat-input">
            <input
                type="text"
                id="ai-input"
                placeholder="Ask about any stock…"
                maxlength="500"
            />
            <button onclick="aiSend()">Send</button>
        </div>
    </div>
    <button id="ai-chat-toggle" onclick="toggleChat()" title="AI Chat">💬</button>
</div>
