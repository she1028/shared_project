<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
/* Chatbot button */
#chatBotLink {
    background-color: #8c580a;
    cursor: pointer;
}

.chatbot-text {
    opacity: 0;
    max-width: 0;
    overflow: hidden;
    white-space: nowrap;
    transform: translateX(-5px);
    transition: opacity 0.5s ease, transform 0.5s ease, max-width 0.5s ease;
}

#chatBotLink:hover .chatbot-text {
    opacity: 1;
    max-width: 200px;
    transform: translateX(0);
}

/* Chat Modal - Floating above the button */
#chatModal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1100;
    left: 10px;  /* Position near the left edge */
    bottom: 70px; /* Position above the button */
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease-in-out;
}

#chatModalContent {
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 15px;
    position: relative;
}

#chatModalClose {
    position: absolute;
    top: 5px;
    right: 10px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

#chat {
    flex-grow: 1;
    overflow-y: auto;
    padding-bottom: 10px;
    font-family: Arial, sans-serif;
}

#chat p {
    margin: 5px 0;
    padding: 8px 12px;
    border-radius: 8px;
    line-height: 1.4;
}

#chat p.user {
    background: #d4efff;
    align-self: flex-end;
    width: fit-content;
}

#chat p.bot {
    background: #f1f1f1;
    align-self: flex-start;
    width: fit-content;
}

/* Input container */
#input-container {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

#input {
    flex: 1;
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #000000;
    font-size: 16px;
}

</style>

<!-- Chatbot Button -->
<div>
    <a id="chatBotLink"
       class="position-fixed bottom-0 start-0 my-4 mx-4 d-flex align-items-center gap-2 text-decoration-none rounded-pill shadow px-3 py-2"
       style="z-index: 1055; border: none;">
        <img src="images/bot.png" alt="Chat Bot" width="50" class="chatbot">
        <span class="chatbot-text fw-medium text-white">Chat with us!</span>
    </a>
</div>

<!-- Chat Modal -->
<div id="chatModal" class="shadow-lg mb-5 mx-3" style="background-color: #ffffff; width: 300px; height: 400px;">
    <div id="chatModalContent">
        <div id="chatModalHeader" class=" fw-bold my-1" style="font-size:16px;">YMZM Assistant</div>
        <hr class="my-1">
        <span id="chatModalClose">&times;</span>
        <div id="chat"></div>
        <div id="input-container">
            <input id="input" type="text" placeholder="Ask...">
            <button type="button" class="btn btn-outline-dark" id="send">
    <i class="bi bi-send-fill"></i>
</button>
        </div>
    </div>
</div>

<script>
// Open modal and show "Chat with us" text when clicked
document.getElementById("chatBotLink").addEventListener("click", function(event) {
    event.preventDefault();
    document.getElementById("chatModal").style.display = "block"; // Show the modal when the button is clicked
    document.querySelector('.chatbot-text').style.display = "none"; // Hide the "Chat with us" text
});

// Close modal and show "Chat with us" text again
document.getElementById("chatModalClose").addEventListener("click", function() {
    document.getElementById("chatModal").style.display = "none"; // Close the modal
    document.querySelector('.chatbot-text').style.display = "block"; // Show the "Chat with us" text again
});

// Close modal when clicking outside of it
window.addEventListener("click", function(event) {
    if (event.target == document.getElementById("chatModal")) {
        document.getElementById("chatModal").style.display = "none"; // Close the modal when clicking outside
        document.querySelector('.chatbot-text').style.display = "block"; // Show the "Chat with us" text again
    }
});

// Chat send function
function send_to_chat() {
    const chat = document.getElementById("chat");
    const input = document.getElementById("input");
    const text = input.value.trim();
    if (!text) return;

    // Add user message to chat
    chat.innerHTML += `
    <div class = "d-flex justify-content-end mb-2">
        <p class='user rounded-5'><b>You:</b> ` + text + `</p>
    </div>`;
    
    input.value = "";  // Clear the input field
    chat.scrollTop = chat.scrollHeight;

    const reply_id = "reply_" + Date.now();
    chat.innerHTML += "<p id='" + reply_id + "' class='bot justify-content-start mb-2 rounded-5' style='word-wrap:break-all;'><b>YMZM:</b> ...</p>";
    chat.scrollTop = chat.scrollHeight;

    // Send user input to chat.php using AJAX (fetch API)
    fetch("includes/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "prompt=" + encodeURIComponent(text)  // Sending the user input as prompt
    })
    .then(res => res.text())  // Parse response from PHP backend
    .then(reply => {
        document.getElementById(reply_id).innerHTML = "<b>YMZM:</b> " + reply;
        chat.scrollTop = chat.scrollHeight;
    })
    .catch(() => {
        document.getElementById(reply_id).innerHTML = "<b>YMZM:</b> error sending request";
    });
}

// Bind send button to send_to_chat function
document.getElementById("send").addEventListener("click", send_to_chat);

// Also send on Enter key press
document.getElementById("input").addEventListener("keypress", function(e){
    if (e.key === "Enter") send_to_chat();
});

</script>
