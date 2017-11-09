function Conversation(backend, crypter, view) {
  this.backend = backend;
  this.crypter = crypter;
  this.view = view;

  this.timeout;
  this.t = 300;

  this.processing = false;

  this.token = Cookies.get('token');
}

Conversation.prototype.handleScrollOnMessageBlock = function() {
  this.view.messagesContainer.scroll(function() {
    var that = this;

    var quarterOfMessageBlock = $(that.view.messagesContainer).prop('scrollHeight') / 4;

    if ($(that.view.moreMessages).length) {
      if ($(that.view.messagesContainer).scrollTop() <= quarterOfMessageBlock) {
        if (!that.processing) {
          that.processing = true;

          var url = window.location.href;

          var datawith = $('a', that.view.moreMessages).attr('data-with');
          var offset = $('a', that.view.moreMessages).attr('data-offset');
          var count = $('a', that.view.moreMessages).attr('data-count');

          that.backend.getLastMessages(datawith, offset).then(
            function(data) {
              var actualUrl = window.location.href;

              if (url === actualUrl) {
                that.view.showDecryptionLoader();

                var messages = data['messages'];

                var decryptedPromises = [];

                for (var key in messages) {
                  var decryptedPromise = that.crypter.decrypt(messages[key].content);

                  decryptedPromises.push(decryptedPromise);
                }

                Promise.all(decryptedPromises).then(function(decrypted) {
                  actualUrl = window.location.href;

                  if (url == actualUrl) {
                    for (var key in messages) {
                      messages[key].content = decrypted[key].data;
                    }

                    that.view.removeDecryptionLoader();

                    that.view.showMoreMessagesButton(data['with'], +offset + +1, +count + data['count'], data['totalCount']);
                    that.view.showLastMessages(messages);
                    that.view.moveMessagesToBottom();

                    that.processing = false;
                  }
                });
              }
            },

            function(jqXHR, textStatus) {
              that.view.showConnectionError();

              that.backend.handleError(jqXHR, textStatus);
            }
          );
        }
      }
    }
  }.bind(this));
};

Conversation.prototype.handleClickOnMoreMessages = function() {
  $(this.view.moreMessages).click(function(e) {
    e.preventDefault();

    var that = this;

    if (!that.processing) {
      that.processing = true;

      var url = window.location.href;

      var datawith = $('a', that.view.moreMessages).attr('data-with');
      var offset = $('a', that.view.moreMessages).attr('data-offset');
      var count = $('a', that.view.moreMessages).attr('data-count');

      that.backend.getLastMessages(datawith, offset).then(
        function(data) {
          var actualUrl = window.location.href;

          if (url === actualUrl) {
            that.view.showDecryptionLoader();

            var messages = data['messages'];

            var decryptedPromises = [];

            for (var key in messages) {
              var decryptedPromise = that.crypter.decrypt(messages[key].content);

              decryptedPromises.push(decryptedPromise);
            }

            Promise.all(decryptedPromises).then(function(decrypted) {
              actualUrl = window.location.href;

              if (url == actualUrl) {
                for (var key in messages) {
                  messages[key].content = decrypted[key].data;
                }

                that.view.removeDecryptionLoader();

                that.view.showMoreMessagesButton(data['with'], +offset + +1, +count + data['count'], data['totalCount']);
                that.view.showLastMessages(messages);
                that.view.moveMessagesToBottom();

                that.processing = false;
              }
            });
          }
        },

        function(jqXHR, textStatus) {
          that.view.showConnectionError();

          that.backend.handleError(jqXHR, textStatus);
        }
      );
    }
  }.bind(this));
};

Conversation.prototype.handleEnterKeyOnMessageForm = function() {
  this.view.messageBox.keydown(
    function(e) {
      if (e.ctrlKey && e.keyCode == 13) {
        $(this.view.messageBox).val($(this.view.messageBox).val() + "\n");
      } else if (e.keyCode == 13) {
        e.preventDefault();

        $(this.view.messageForm).submit();
        $(this.view.messageBox).val('');
      }
    }.bind(this)
  );
};

Conversation.prototype.handleClickOnMessageInput = function() {
  this.view.submit.click(
    function(e) {
      e.preventDefault();

      $(this.view.messageForm).submit();
      $(this.view.messageBox).val('');
    }.bind(this)
  );
};

Conversation.prototype.handleSubmitOnMessageForm = function() {
  this.view.messageForm.submit(
    function(e) {
      e.preventDefault();

      var that = this;

      var to = $(that.view.messageForm).attr('data-send-to'),
          message =  $(that.view.messageBox).val(),
          token = $(that.view.token).val();

      if (message != '') {
        that.backend.getPublicKeys(to).then(
          function(publicKeys) {
            that.crypter.encrypt(publicKeys, message).then(function(encrypted) {
              that.backend.postMessage(to, encrypted.data, token).then(
                function(data) {
                  // ...
                },

                function(jqXHR, textStatus) {
                  var scrollPosition = $(that.view.messagesContainer).scrollTop() + $(that.view.messagesContainer).height();      
                  var scrollHeight = $(that.view.messagesContainer).prop('scrollHeight');

                  var data = {
                    to: to,
                    content: message,
                    token: token
                  };

                  var template = $('#resend-message-template').html(); 
                  var html = ejs.render(template, data);

                  $(that.view.messageBlock).append(html);

                  that.view.scrollDownMessages(scrollPosition, scrollHeight);

                  that.backend.handleError(jqXHR, textStatus);
                }
              );
            });
          },

          function(jqXHR, textStatus) {
            that.view.showConnectionError();

            that.backend.handleError(jqXHR, textStatus);
          }
        );
      }

      return false;
    }.bind(this)
  );
};

Conversation.prototype.handleClickOnResendMessage = function() {
  var that = this;

  $(this.view.messageBlock).on('click', '.resend a', function(e) {
    e.preventDefault();

    var resendLink = $(this);

    var to =  resendLink.attr('data-send-to'),
        message = resendLink.parent().next('.content').text(),
        token = resendLink.attr('data-token');

    if (message != '') {
      that.backend.postMessage(to, message, token).then(
        function(data) {
          resendLink.parents('.resend').remove();
        },

        function(jqXHR, textStatus) {
          that.backend.handleError(jqXHR, textStatus);
        }
      );
    }
  });
};

Conversation.prototype.handleEnterKeyOnPassphraseForm = function() {
  this.view.passphraseBox.keydown(
    function(e) {
      if (e.keyCode == 13) {
        e.preventDefault();

        $(this.view.passphraseForm).submit();
      }
    }.bind(this)
  );
};

Conversation.prototype.handleSubmitOnPassphraseForm = function() {
  $(this.view.passphraseForm).submit(
    function(e) {
      e.preventDefault();

      var that = this;

      var passphrase = $(that.view.passphraseBox).val(),
          datawith = $(that.view.passphraseForm).attr('data-with'),
          offset = $(that.view.passphraseForm).attr('data-offset');

      that.crypter.passphrase = passphrase;

      that.runMessages(datawith, offset);

    }.bind(this)
  );
};

Conversation.prototype.runMessages = function(datawith, offset) {
  if (offset === undefined) {
    offset = 1;
  }
  
  var that = this;

  var url = window.location.href;

  that.view.removeSelectDialog();

  that.backend.getPrivateKey().then(
    function(privateKey) {
      var success = that.crypter.addPrivateKey(privateKey['privateKey']);

      if (success) {
        that.view.removePassphraseForm();

        that.backend.getLastMessages(datawith, offset).then(
          function(data) {
            var actualUrl = window.location.href;

            that.view.showDecryptionLoader();

            if (url == actualUrl) {
              if ($(that.view.moreMessages).length) {
                that.view.showMoreMessagesButton(data['with'], +offset + +1, data['count'], data['totalCount']);          
              } else {
                that.view.showMoreMessagesButton(data['with'], +offset + +1, data['count'], data['totalCount']);

                that.handleClickOnMoreMessages();

                if (!$.hasData(that.view.messagesContainer[0])) {
                  that.handleScrollOnMessageBlock();
                }
              }

              if ($(that.view.messageForm).length) {
                that.view.showMessageForm(datawith, that.token);        
              } else {
                that.view.showMessageForm(datawith, that.token);

                that.handleEnterKeyOnMessageForm();
                that.handleClickOnMessageInput();
                that.handleSubmitOnMessageForm();
              }

              var messages = data['messages'];

              var decryptedPromises = [];

              for (var key in messages) {
                var decryptedPromise = that.crypter.decrypt(messages[key].content);

                decryptedPromises.push(decryptedPromise);
              }

              Promise.all(decryptedPromises).then(function(decrypted) {
                actualUrl = window.location.href;

                if (url == actualUrl) {
                  for (var key in messages) {
                    messages[key].content = decrypted[key].data;
                  }

                  that.view.removeDecryptionLoader();

                  that.view.showMessages(messages);
                  that.view.moveMessagesToBottom();

                  that.handleClickOnResendMessage();

                  if (offset < 2) {
                    var scrollPosition = $(that.view.messagesContainer).prop('scrollHeight');

                    that.view.scrollDownMessages(scrollPosition, scrollPosition);
                  }

                  that.refreshMessages(datawith, data.since);
                }
              });
            }
          },

          function(jqXHR, textStatus) {
            that.view.showConnectionError();

            that.backend.handleError(jqXHR, textStatus);
          }
        );
      } else {
        that.view.showPassphraseForm(datawith, offset);
        that.handleEnterKeyOnPassphraseForm();
        that.handleSubmitOnPassphraseForm();
      }
    },

    function(jqXHR, textStatus) {
      that.view.showConnectionError();

      that.backend.handleError(jqXHR, textStatus);
    }
  );
}

Conversation.prototype.refreshMessages = function(datawith, since) {

  var that = this;

  var url = window.location.href;

  that.backend.getNewMessages(datawith, since).then(
    function(data) {
      var actualUrl = window.location.href;

      var scrollPosition = $(that.view.messagesContainer).scrollTop() + $(that.view.messagesContainer).height();      
      var scrollHeight = $(that.view.messagesContainer).prop('scrollHeight');

      if (url == actualUrl) {
        var messages = data['messages'];

        var decryptedPromises = [];

        for (var key in messages) {
          var decryptedPromise = that.crypter.decrypt(messages[key].content);

          decryptedPromises.push(decryptedPromise);
        }

        Promise.all(decryptedPromises).then(function(decrypted) {
          actualUrl = window.location.href;

          if (url == actualUrl) {
            for (var key in messages) {
              messages[key].content = decrypted[key].data;
            }

            that.view.showNewMessages(messages);
            that.view.scrollDownMessages(scrollPosition, scrollHeight);

            clearTimeout(that.timeout);

            that.timeout = setTimeout(function() {
              that.refreshMessages(datawith, data.since);
            }, that.t);
          }
        });
      }
    },

    function(jqXHR, textStatus) {
      that.view.showConnectionError();

      that.backend.handleError(jqXHR, textStatus);

      clearTimeout(that.timeout);

      that.timeout = setTimeout(function() {
        that.refreshMessages(datawith, since);
      }, that.t);
    }
  );
};


function ConversationView() {
  this.conversation = $('.conversation');

  this.moreMessages = $('.get-more-messages', this.conversation);
  this.messagesContainer = $('.messages-container', this.conversation);
  this.messageBlock = $('.messages', this.conversation);
  this.messages = $('.message', this.conversation);

  this.messageForm = $('.message-form', this.conversation);
  this.messageBox = $('textarea[name="message"]', this.messageForm);
  this.token = $('input[name="token"]', this.messageForm);
  this.submit = $('input[type="submit"]', this.messageForm);

  this.passphraseForm = $('.passphrase-form', this.conversation);
  this.passphraseBox = $('input[name="passphrase"]', this.passphraseForm);

  this.decryptionLoader = $('.decrypting');

  this.selectDialog = $('.select-dialog', this.conversation);
}

ConversationView.prototype.showMoreMessagesButton = function(datawith, offset, count, totalCount) {
  if (count < totalCount) {
    var data = {
      datawith: datawith,
      offset: offset,
      count: count,
      totalCount: totalCount
    };

    var template = $('#get-more-messages-template').html();
    var html = ejs.render(template, data);

    if ($(this.moreMessages).length) {
      $('a', this.moreMessages).attr('data-with', datawith);
      $('a', this.moreMessages).attr('data-offset', offset);
      $('a', this.moreMessages).attr('data-count', count);
    } else {
      $(this.messagesContainer).prepend(html);
    }
  } else {
    if ($(this.moreMessages).length) {
      $(this.moreMessages).remove();
    }
  }

  this.moreMessages = $('.get-more-messages');
};

ConversationView.prototype.showMessageForm = function(to, token) {
  if ($(this.messageForm).length) {
    $(this.messageForm).attr('action', 'send.php?to=' + to);
    $(this.messageForm).attr('data-send-to', to);
    $('input[name="token"]', this.messageForm).val(token);
  } else {
    var data = {
      to: to,
      token: token
    };

    var template = $('#message-form-template').html();
    var html = ejs.render(template, data);

    $(this.conversation).append(html);

    this.messageForm = $('.message-form');
    this.messageBox = $('textarea[name="message"]', this.messageForm);
    this.token = $('input[name="token"]', this.messageForm);
    this.submit = $('input[type="submit"]', this.messageForm);
  }
};

ConversationView.prototype.showMessages = function(messages) {
  this.messages.remove();

  var data = {
    messages: messages
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageBlock).html(html);

  this.messages = $('.message');
};

ConversationView.prototype.showLastMessages = function(messages) {
  var data = {
    messages: messages
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageBlock).prepend(html);

  this.messages = $('.message');
};

ConversationView.prototype.showNewMessages = function(messages) {
  var data = {
    messages: messages
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageBlock).append(html);

  this.messages = $('.message');
};

ConversationView.prototype.showDecryptionLoader = function() {
  var data = {
    c: 'decrypting',
    m: "Decrypting..."
  };

  var template = $('#loader-template').html();
  var html = ejs.render(template, data);

  $(this.messageBlock).prepend(html);

  this.decryptionLoader = $('.decrypting');
};

ConversationView.prototype.showConnectionError = function() {
  if ($('.connection-error').length == 0) {
    var template = $('#connection-error-template').html(); 
    var html = ejs.render(template);

    $('header').after(html);

    var removeErrorTimeot = setTimeout(function() {
      $('.connection-error').remove();
    }, 5000);
  }
};

ConversationView.prototype.showPassphraseForm = function(datawith, offset) {
  var data = {
    datawith: datawith,
    offset: offset
  };

  var template = $('#passphrase-form-template').html();
  var html = ejs.render(template, data);

  $(this.messageBlock).html(html);

  this.passphraseForm = $('.passphrase-form', this.conversation);
  this.passphraseBox = $('input[name="passphrase"]', this.passphraseForm);
};

ConversationView.prototype.removePassphraseForm = function() {
  if ($(this.passphraseForm).length) {
    $(this.passphraseForm).remove();

    this.passphraseForm = $('.passphraseForm', this.conversation);
  }
};

ConversationView.prototype.removeDecryptionLoader = function() {
  if ($(this.decryptionLoader).length) {
    $(this.decryptionLoader).remove();

    this.decryptionLoader = $('.decrypting', this.conversation);
  }
};

ConversationView.prototype.removeSelectDialog = function() {
  if ($(this.selectDialog).length) {
    $(this.selectDialog).remove();

    this.selectDialog = $('.select-dialog', this.conversation);
  }
};

ConversationView.prototype.moveMessagesToBottom = function() {
  var space = 0;

  var messageContainerHeight = $(this.messagesContainer).outerHeight();
  var messagesHeight = 0;

  messagesHeight = $(this.messageBlock).outerHeight(); 

  if (messagesHeight < messageContainerHeight) {
    space = messageContainerHeight - messagesHeight;

    this.messageBlock.css('padding-top', space);
  }
};

// may b' there is some better way
ConversationView.prototype.scrollDownMessages = function(scrollPosition, scrollHeight) {
  var newBottomScrollPosition = $(this.messagesContainer).prop('scrollHeight');

  if (Math.round(scrollPosition) == scrollHeight) {
    $(this.messagesContainer).scrollTop(newBottomScrollPosition);
  }
};