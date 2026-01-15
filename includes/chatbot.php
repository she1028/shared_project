<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>YMZM Assistant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Chatbot button */
        #chatBotLink { background-color: #3E2723; cursor: pointer; box-shadow: 0 10px 30px rgba(0,0,0,0.18); border: 1px solid rgba(0,0,0,0.05); z-index: 1200; }
        .chatbot-text { opacity: 0; max-width: 0; overflow: hidden; white-space: nowrap; transform: translateX(-5px); transition: opacity 0.35s ease, transform 0.35s ease, max-width 0.35s ease; }
        #chatBotLink:hover .chatbot-text { opacity: 1; max-width: 200px; transform: translateX(0); }

        /* Chat Modal */
        #chatModal { display: none; position: fixed; z-index: 1100; left: 96px; bottom: 110px; border-radius: 14px; box-shadow: 0 16px 45px rgba(0,0,0,0.22); overflow: hidden; transition: all 0.3s ease-in-out; background: #f8f7f5; width: 340px; max-width: calc(100vw - 28px); height: 460px; }
        #chatModalContent { height:100%; display:flex; flex-direction:column; padding:16px 16px 12px; position:relative; gap:12px; }
        #chatModalHeader { display:flex; align-items:center; gap:8px; font-size:16px; }
        #chatModalClose { position:absolute; top:10px; right:14px; font-size:22px; cursor:pointer; color:#777; }
        #chat { flex-grow:1; overflow-y:auto; overflow-x:hidden; padding:6px 4px 4px 0; font-family: "Segoe UI", Arial, sans-serif; display:flex; flex-direction:column; gap:10px; }
        #chat p { margin:0; padding:10px 12px; border-radius:12px; line-height:1.45; max-width: 85%; word-break: break-word; }
        #chat p.user { background:#dceeff; align-self:flex-end; border:1px solid #b7d8ff; box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
        #chat p.bot { background:#ffffff; align-self:flex-start; border:1px solid #ececec; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        #chat::-webkit-scrollbar { width: 8px; }
        #chat::-webkit-scrollbar-thumb { background: #c9c9c9; border-radius: 10px; }

        /* Input container */
        #input-container { display:flex; gap:10px; align-items:center; }
        #input { flex:1; padding:10px 14px; border-radius:10px; border:1px solid #d6d6d6; font-size:15px; background:#fff; box-shadow: inset 0 1px 3px rgba(0,0,0,0.06); }
        #send { display:flex; align-items:center; justify-content:center; width:46px; height:46px; border-radius:12px; }

        /* Responsive tweaks */
        @media (max-width: 540px) {
            #chatModal { left: 12px; right: 12px; width: auto; bottom: 96px; height: 65vh; max-width: none; }
            #chatBotLink { bottom: 14px; left: 12px; }
            #chatModalContent { padding: 14px 14px 10px; gap:10px; }
            #chat p { max-width: 90%; }
        }
    </style>
</head>
<body>

<!-- Chatbot Button -->
<div>
    <a id="chatBotLink" class="position-fixed bottom-0 start-0 my-4 mx-4 d-flex align-items-center gap-2 text-decoration-none rounded-pill shadow px-3 py-2" style="z-index:1055; border:none;">
        <img src="images/bot.png" alt="Chat Bot" width="50" class="chatbot">
        <span class="chatbot-text fw-medium text-white">Chat with us!</span>
    </a>
</div>

<!-- Chat Modal -->
<div id="chatModal">
    <div id="chatModalContent">
        <div id="chatModalHeader" class="fw-bold">YMZM Assistant</div>
        <span id="chatModalClose">&times;</span>
        <div id="chat"></div>
        <div id="input-container">
            <input id="input" type="text" placeholder="Ask...">
            <button type="button" class="btn btn-outline-dark" id="send"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>
</div>

<script>
const send = document.getElementById("send");
const chatBotLink = document.getElementById("chatBotLink");
const chatModal = document.getElementById("chatModal");

chatBotLink.addEventListener("click", function(event) {
    event.preventDefault();
    const isOpen = chatModal.style.display === "block";
    if (isOpen) {
        chatModal.style.display = "none";
        document.querySelector('.chatbot-text').style.display = "block";
    } else {
        chatModal.style.display = "block";
        document.querySelector('.chatbot-text').style.display = "none";
    }
});

document.getElementById("chatModalClose").addEventListener("click", function() {
    chatModal.style.display = "none";
    document.querySelector('.chatbot-text').style.display = "block";
});

window.addEventListener("click", function(event) {
    if (event.target == chatModal) {
        chatModal.style.display = "none";
        document.querySelector('.chatbot-text').style.display = "block";
    }
});

function send_to_chat() {
    const chat = document.getElementById("chat");
    const input = document.getElementById("input");
    const text = input.value.trim();
    if (!text) return;

    // Prevent rapid double sends while a request is in flight.
    if (send.disabled) return;
    send.disabled = true;

    chat.innerHTML += `<div class="d-flex justify-content-end mb-2"><p class='user rounded-5'><b>You:</b> ${text}</p></div>`;
    input.value = "";
    chat.scrollTop = chat.scrollHeight;

    const reply_id = "reply_" + Date.now();
    chat.innerHTML += `<p id='${reply_id}' class='bot justify-content-start mb-2 rounded-5' style='word-wrap:break-word;'>Thinking...</p>`;
    chat.scrollTop = chat.scrollHeight;

    // Call chat.php (lives under /includes)
    fetch("includes/chat.php", { 
        method: "POST", 
        headers: { "Content-Type": "application/x-www-form-urlencoded" }, 
        body: "prompt=" + encodeURIComponent(text) 
    })
    .then(res => res.text())
    .then(reply => { 
        document.getElementById(reply_id).innerHTML = "<b>YMZM:</b> " + reply; 
        chat.scrollTop = chat.scrollHeight; 
    })
    .catch(() => { 
        document.getElementById(reply_id).innerHTML = "<b>YMZM:</b> error sending request"; 
    })
    .finally(() => {
        send.disabled = false;
        input.focus();
    });
}

document.getElementById("send").addEventListener("click", send_to_chat);
document.getElementById("input").addEventListener("keypress", function(e){ if(e.key==="Enter") send_to_chat(); });
</script>

</body>
</html>
