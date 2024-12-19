jQuery(document).ready(function ($) {
    // console.log("Loaded Chat");
    const billChatMessages = $('#chat-messages'); // Div where messages are displayed
    const billChatForm = $('#chat-form');        // Submission form
    const billChatInput = $('#chat-input');      // Message input field
    const billChaterrorMessage = $('#error-message'); // Substitua pelo ID ou classe da mensagem de erro
    let billChatLastMessageCount = 0;
    function billChatEscapeHtml(text) {
        return $('<div>').text(text).html();
    }
    // Clears the chat on the server when the page loads
    $.ajax({
        url: bill_data.ajax_url,
        method: 'POST',
        data: {
            action: 'bill_chat_reset_messages'
        },
        success: function () {
            // console.log(bill_data.reset_success);
        },
        error: function (xhr, status, error) {
            console.error(bill_data.reset_error, error, xhr.responseText);
        }
    });
    function billChatLoadMessages() {
        $.ajax({
            url: bill_data.ajax_url, // AJAX URL passed by wp_localize_script
            method: 'POST',
            data: {
                action: 'bill_chat_load_messages',
                last_count: billChatLastMessageCount // Sends the current number of messages
            },
            success: function (response, status, xhr) {
                try {
                    // Tenta converter a resposta para JSON se necessário
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    // Verifica se a resposta tem a estrutura esperada
                    if (Array.isArray(response.messages)) {
                        if (response.message_count > billChatLastMessageCount) {
                            billChatLastMessageCount = response.message_count;
                            response.messages.forEach(function (message) {
                                if (message.text && message.sender) {
                                    if (message.sender === 'user') {
                                        billChatMessages.append('<div class="user-message">' + billChatEscapeHtml(message.text) + '</div>');
                                    } else if (message.sender === 'chatgpt') {
                                        billChatMessages.append('<div class="chatgpt-message">' + message.text + '</div>');
                                    }
                                } else {
                                    console.warn(bill_data.invalid_message, message);
                                }
                            });
                            billChatMessages.scrollTop(billChatMessages[0].scrollHeight);
                            $('.spinner999').css('display', 'none');
                            setTimeout(function () {
                                $('#chat-form button').prop('disabled', false);
                            }, 2000);
                        }
                    } else {
                        console.error(bill_data.invalid_response_format, response);
                        $('.spinner999').css('display', 'none');
                        $('#chat-form button').prop('disabled', false);
                    }
                } catch (err) {
                    console.error(bill_data.response_processing_error, err, response);
                    $('.spinner999').css('display', 'none');
                    $('#chat-form button').prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                console.error(bill_data.ajax_error, error, xhr.responseText);
                $('.spinner999').css('display', 'none');
                $('#chat-form button').prop('disabled', false);
            },
        });
    }
    // Tracks the number of messages already loaded
    function billChatLoadMessages99() {
        $.ajax({
            url: bill_data.ajax_url, // AJAX URL passed by wp_localize_script
            method: 'POST',
            data: {
                action: 'bill_chat_load_messages',
                last_count: billChatLastMessageCount // Sends the current number of messages
            },
            success: function (response, status, xhr) {
                try {
                    // Verifica o Content-Type dentro do try-catch
                    if (xhr.getResponseHeader('Content-Type') && xhr.getResponseHeader('Content-Type').includes('application/json')) {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        if (Array.isArray(response.messages)) {
                            if (response.message_count > billChatLastMessageCount) {
                                billChatLastMessageCount = response.message_count;
                                response.messages.forEach(function (message) {
                                    if (message.text && message.sender) {
                                        if (message.sender === 'user') {
                                            billChatMessages.append('<div class="user-message">' + billChatEscapeHtml(message.text) + '</div>');
                                        } else if (message.sender === 'chatgpt') {
                                            billChatMessages.append('<div class="chatgpt-message">' + message.text + '</div>');
                                        }
                                    } else {
                                        console.warn(bill_data.invalid_message, message);
                                    }
                                });
                                $('.spinner999').css('display', 'none');
                                billChatMessages.scrollTop(billChatMessages[0].scrollHeight);
                                setTimeout(function () {
                                    $('#chat-form button').prop('disabled', false);
                                }, 2000);
                            }
                        } else {
                            console.error(bill_data.invalid_response_format, response);
                            $('.spinner999').css('display', 'none');
                            $('#chat-form button').prop('disabled', false);
                        }
                    } else {
                        throw new Error(bill_data.not_json);
                    }
                } catch (err) {
                    // Aqui capturamos qualquer erro, incluindo a falha no Content-Type e erro de processamento
                    console.error(bill_data.response_processing_error, err, response);
                    $('.spinner999').css('display', 'none');
                    $('#chat-form button').prop('disabled', false);
                    if (err.message === bill_data.not_json) {
                        billChaterrorMessage.text(bill_data.not_json).show();
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error(bill_data.ajax_error, error, xhr.responseText);
                $('.spinner999').css('display', 'none');
                $('#chat-form button').prop('disabled', false);
            },
        });
    }
    billChatForm.on('submit', function (e) {
        e.preventDefault();
        const message = billChatInput.val().trim();
        const billChaterrorMessage = $('#error-message');
        if (message !== '') {
            $('.spinner999').css('display', 'block');
            $('#chat-form button').prop('disabled', true);
            $.ajax({
                url: bill_data.ajax_url,
                method: 'POST',
                data: {
                    action: 'bill_chat_send_message',
                    message: message
                },
                timeout: 60000,
                success: function () {
                    //billChatInput.val('');
                    setTimeout(function () {
                        billChatInput.val('');
                    }, 2000);
                    billChatLoadMessages();
                },
                error: function (xhr, status, error) {
                    billChaterrorMessage.text(bill_data.send_error).show();
                    $('.spinner999').css('display', 'none');
                    $('#chat-form button').prop('disabled', false);
                    setTimeout(() => billChaterrorMessage.fadeOut(), 5000);
                }
            });
        } else {
            billChaterrorMessage.text(bill_data.empty_message_error).show();
            setTimeout(() => billChaterrorMessage.fadeOut(), 3000);
        }
    });
    setInterval(() => {
        if (billChatMessages.is(':visible')) {
            billChatLoadMessages();
        }
    }, 3000);
    billChatMessages.empty();
});
