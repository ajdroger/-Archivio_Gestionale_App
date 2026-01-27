/**
 * MCAG AI Genius Widget
 * A drop-in AI assistant for the frontend.
 */
(function () {
    const styles = `
        #ai-genius-root {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
            font-family: 'Inter', sans-serif;
        }
        .ai-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .ai-toggle:hover { transform: scale(1.05); }
        .ai-toggle svg { width: 30px; height: 30px; color: white; }
        
        .ai-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 500px;
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .ai-window.open { display: flex; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .ai-header {
            background: #1e293b;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        .ai-title { font-weight: 700; color: white; display: flex; align-items: center; gap: 0.5rem; }
        .ai-status { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
        
        .ai-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .msg { max-width: 80%; padding: 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; line-height: 1.4; }
        .msg.user { align-self: flex-end; background: #4f46e5; color: white; border-bottom-right-radius: 0.25rem; }
        .msg.ai { align-self: flex-start; background: #1e293b; color: #e2e8f0; border-bottom-left-radius: 0.25rem; border: 1px solid #334155; }
        
        .ai-input-area {
            padding: 1rem;
            background: #0f172a;
            border-top: 1px solid #1e293b;
            display: flex;
            gap: 0.5rem;
        }
        .ai-input {
            flex: 1;
            background: #1e293b;
            border: 1px solid #334155;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            outline: none;
        }
        .ai-input:focus { border-color: #6366f1; }
        .ai-send {
            background: #6366f1;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
        }
        .ai-send:disabled { background: #475569; cursor: not-allowed; }
    `;

    // Inject Styles
    const styleSheet = document.createElement("style");
    styleSheet.innerText = styles;
    document.head.appendChild(styleSheet);

    // Create UI
    const root = document.createElement("div");
    root.id = "ai-genius-root";
    root.innerHTML = `
        <div class="ai-window" id="aiWindow">
            <div class="ai-header">
                <div class="ai-title">
                    <span class="ai-status"></span> MCAG Genius
                </div>
                <button id="aiClose" style="background:none;border:none;color:#94a3b8;cursor:pointer;">&times;</button>
            </div>
            <div class="ai-messages" id="aiMessages">
                <div class="msg ai">Ciao! Sono il tuo assistente virtuale. Come posso aiutarti oggi?</div>
            </div>
            <div class="ai-input-area">
                <input type="text" class="ai-input" id="aiInput" placeholder="Chiedimi qualcosa..." />
                <button class="ai-send" id="aiSend">Send</button>
            </div>
        </div>
        <div class="ai-toggle" id="aiToggle">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>
    `;
    document.body.appendChild(root);

    // Logic
    const toggle = document.getElementById('aiToggle');
    const windowEl = document.getElementById('aiWindow');
    const closeBtn = document.getElementById('aiClose');
    const input = document.getElementById('aiInput');
    const sendBtn = document.getElementById('aiSend');
    const messages = document.getElementById('aiMessages');

    function toggleChat() {
        windowEl.classList.toggle('open');
    }

    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.className = `msg ${sender}`;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        addMessage(text, 'user');
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;

        // Typing indicator
        const typingDiv = document.createElement('div');
        typingDiv.className = 'msg ai';
        typingDiv.textContent = '...';
        messages.appendChild(typingDiv);
        messages.scrollTop = messages.scrollHeight;

        try {
            const response = await fetch('/api/ai/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, context: 'user_dashboard' })
            });

            const data = await response.json();
            messages.removeChild(typingDiv);

            if (data.error) {
                addMessage('Error: ' + data.error, 'ai');
            } else {
                addMessage(data.response, 'ai');
            }
        } catch (e) {
            messages.removeChild(typingDiv);
            addMessage('Connection Error.', 'ai');
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    toggle.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

})();
