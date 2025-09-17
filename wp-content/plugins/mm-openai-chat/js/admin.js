jQuery(document).ready(function($) {
    // Handle embedding generation
    $('#oec-gen-embed').on('click', function() {
        const button = $(this);
        const postId = button.data('post-id');
        
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: OEC.ajax_url,
            type: 'POST',
            data: {
                action: 'oec_generate_embedding',
                post_id: postId,
                nonce: OEC.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Embedding generated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to generate embedding. ';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage += xhr.responseJSON.data;
                } else {
                    errorMessage += 'Please try again.';
                }
                alert(errorMessage);
            },
            complete: function() {
                button.prop('disabled', false).text('Generate Embedding');
            }
        });
    });

    // Handle chat functionality
    const chatLog = $('#oec-chat-log');
    const chatInput = $('#oec-chat-input');
    const sendButton = $('#oec-chat-send');

    function appendMessage(message, isUser = false) {
        const messageClass = isUser ? 'user-message' : 'ai-message';
        const messageHtml = `<div class="${messageClass}">${message}</div>`;
        chatLog.append(messageHtml);
        chatLog.scrollTop(chatLog[0].scrollHeight);
    }

    function sendMessage() {
        const message = chatInput.val().trim();
        if (!message) return;

        // Append user message
        appendMessage(message, true);
        chatInput.val('');

        // Disable input while processing
        chatInput.prop('disabled', true);
        sendButton.prop('disabled', true);

        // Send to server
        $.ajax({
            url: OEC.ajax_url,
            type: 'POST',
            data: {
                action: 'oec_chat',
                message: message,
                nonce: OEC.nonce
            },
            success: function(response) {
                if (response.success) {
                    appendMessage(response.data.reply);
                } else {
                    appendMessage('Error: ' + (response.data || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to get response. ';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage += xhr.responseJSON.data;
                } else {
                    errorMessage += 'Please try again.';
                }
                appendMessage(errorMessage);
            },
            complete: function() {
                chatInput.prop('disabled', false);
                sendButton.prop('disabled', false);
                chatInput.focus();
            }
        });
    }

    // Send message on button click
    sendButton.on('click', sendMessage);

    // Send message on Enter key (but allow Shift+Enter for new line)
    chatInput.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}); 