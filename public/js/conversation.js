function Conversation(backend, view) {
  this.backend = backend;
  this.view = view;

  this.timeout;
  this.t = 500;

  this.token = Cookies.get('token');
}

Conversation.prototype.handleScrollOnMessageBlock = function() {
  var processing = false;

  this.view.messagescontainer.scroll(function() {
    var that = this;

    var quarterOfMessageBlock = $(that.view.messagescontainer).prop('scrollHeight') / 4;

    if ($(that.view.moremessages).length) {
      if ($(that.view.messagescontainer).scrollTop() <= quarterOfMessageBlock) {
        if (!processing) {
          processing = true;

          var url = window.location.href;

          var datawith = $('a', that.view.moremessages).attr('data-with');
          var offset = $('a', that.view.moremessages).attr('data-offset');
          var count = $('a', that.view.moremessages).attr('data-count');

          that.backend.getLastMessages(datawith, offset).then(
            function(data) {
              var actualUrl = window.location.href;

              if (url === actualUrl) {
                that.view.showMoreMessagesButton(data['with'], +offset + +1, +count + data['count'], data['totalCount']);  
                that.view.showLastMessages(data['messages']);
                that.view.moveMessagesToBottom();
              }

              processing = false;
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
  $(this.view.moremessages).click(function(e) {
    e.preventDefault();

    var that = this;

    var url = window.location.href;

    var datawith = $('a', that.view.moremessages).attr('data-with');
    var offset = $('a', that.view.moremessages).attr('data-offset');
    var count = $('a', that.view.moremessages).attr('data-count');

    that.backend.getLastMessages(datawith, offset).then(
      function(data) {
        var actualUrl = window.location.href;

        if (url === actualUrl) {
          that.view.showMoreMessagesButton(data['with'], +offset + +1, +count + data['count'], data['totalCount']);  
          that.view.showLastMessages(data['messages']);
          that.view.moveMessagesToBottom();
        }
      },

      function(jqXHR, textStatus) {
        that.view.showConnectionError();

        that.backend.handleError(jqXHR, textStatus);
      }
    );
  }.bind(this));
};

Conversation.prototype.handleEnterKeyOnMessageForm = function() {
  this.view.messagebox.keydown(
    function(e) {
      if (e.ctrlKey && e.keyCode == 13) {
        $(this.view.messagebox).val($(this.view.messagebox).val() + "\n");
      } else if (e.keyCode == 13) {
        e.preventDefault();

        $(this.view.messageform).submit();
        $(this.view.messagebox).val('');
      }
    }.bind(this)
  );
};

Conversation.prototype.handleClickOnMessageInput = function() {
  this.view.submit.click(
    function(e) {
      e.preventDefault();

      $(this.view.messageform).submit();
      $(this.view.messagebox).val('');
    }.bind(this)
  );
};

Conversation.prototype.handleSubmitOnMessageForm = function() {
  this.view.messageform.submit(
    function(e) {
      e.preventDefault();

      var that = this;

      var to = $(that.view.messageform).attr('data-send-to'),
          message =  $(that.view.messagebox).val(),
          token = $(that.view.token).val();

      if (message != '') {
        that.backend.postMessage(to, message, token).then(
          function(data) {
            // ...
          },

          function(jqXHR, textStatus) {
            var scrollPosition = $(that.view.messagescontainer).scrollTop() + $(that.view.messagescontainer).height();      
            var scrollHeight = $(that.view.messagescontainer).prop('scrollHeight');

            var data = {
              to: to,
              content: message,
              token: token
            };

            var template = $('#resend-message-template').html(); 
            var html = ejs.render(template, data);

            $(that.view.messageblock).append(html);

            that.view.scrollDownMessages(scrollPosition, scrollHeight);

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

  $(this.view.messageblock).on('click', '.resend a', function(e) {
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

Conversation.prototype.runMessages = function(datawith, offset) {
  if (offset === undefined) {
    offset = 1;
  }
  
  var that = this;

  var url = window.location.href;
  
  that.backend.getLastMessages(datawith, offset).then(
    function(data) {
      var actualUrl = window.location.href;

      if (url == actualUrl) {
        that.view.removeSelectDialog();

        if ($(that.view.moremessages).length) {
          that.view.showMoreMessagesButton(data['with'], +offset + +1, data['count'], data['totalCount']);          
        } else {
          that.view.showMoreMessagesButton(data['with'], +offset + +1, data['count'], data['totalCount']);

          that.handleClickOnMoreMessages();
          that.handleScrollOnMessageBlock();
        }

        if ($(that.view.messageform).length) {
          that.view.showMessageForm(datawith, that.token);        
        } else {
          that.view.showMessageForm(datawith, that.token);

          that.handleEnterKeyOnMessageForm();
          that.handleClickOnMessageInput();
          that.handleSubmitOnMessageForm();
        }

        that.view.showMessages(data['messages']);
        that.view.moveMessagesToBottom();

        that.handleClickOnResendMessage();

        if (offset < 2) {
          var scrollPosition = $(that.view.messagescontainer).prop('scrollHeight');

          that.view.scrollDownMessages(scrollPosition, scrollPosition);
        }

        that.refreshMessages(datawith, data.since);
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

      var scrollPosition = $(that.view.messagescontainer).scrollTop() + $(that.view.messagescontainer).height();      
      var scrollHeight = $(that.view.messagescontainer).prop('scrollHeight');

      if (url == actualUrl) {
        that.view.showNewMessages(data['messages']);
        that.view.scrollDownMessages(scrollPosition, scrollHeight);

        clearTimeout(that.timeout);

        that.timeout = setTimeout(function() {
          that.refreshMessages(datawith, data.since);
        }, that.t);
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

  this.moremessages = $('.get-more-messages', this.conversation);
  this.messagescontainer = $('.messages-container', this.conversation);
  this.messageblock = $('.messages', this.conversation);
  this.messages = $('.message', this.conversation);

  this.messageform = $('.message-form', this.conversation);
  this.messagebox = $('textarea[name="message"]', this.messageform);
  this.token = $('input[name="token"]', this.messageform);
  this.submit = $('input[type="submit"]', this.messageform);

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

    if ($(this.moremessages).length) {
      $('a', this.moremessages).attr('data-with', datawith);
      $('a', this.moremessages).attr('data-offset', offset);
      $('a', this.moremessages).attr('data-count', count);
    } else {
      $(this.messagescontainer).prepend(html);
    }
  } else {
    if ($(this.moremessages).length) {
      $(this.moremessages).remove();
    }
  }


  this.moremessages = $('.get-more-messages');
};

ConversationView.prototype.showMessageForm = function(to, token) {
  if ($(this.messageform).length) {
    $(this.messageform).attr('action', 'send.php?to=' + to);
    $(this.messageform).attr('data-send-to', to);
    $('input[name="token"]', this.messageform).val(token);
  } else {
    var data = {
      to: to,
      token: token
    };

    var template = $('#message-form-template').html();
    var html = ejs.render(template, data);

    $(this.conversation).append(html);

    this.messageform = $('.message-form');
    this.messagebox = $('textarea[name="message"]', this.messageform);
  }
};

ConversationView.prototype.showMessages = function(messages) {
  this.messages.remove();

  var data = {
    messages: messages
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageblock).html(html);

  this.messages = $('.message');
};

ConversationView.prototype.showLastMessages = function(messages) {
  var data = {
    messages: messages
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageblock).prepend(html);

  this.messages = $('.message');
};

ConversationView.prototype.showNewMessages = function(messages) {
  var data = {
    messages: messages
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageblock).append(html);

  this.messages = $('.message');
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

ConversationView.prototype.removeSelectDialog = function() {
  if ($(this.selectDialog).length) {
    $(this.selectDialog).remove();

    this.selectDialog = $('.select-dialog');
  }
};

ConversationView.prototype.moveMessagesToBottom = function() {
  var space = 0;

  var messageContainerHeight = $(this.messagescontainer).outerHeight();
  var messagesHeight = 0;

  messagesHeight = $(this.messageblock).outerHeight(); 

  if (messagesHeight < messageContainerHeight) {
    space = messageContainerHeight - messagesHeight;

    this.moremessages.css('padding-bottom', space);
  } 
};

// may b' there is some better way
ConversationView.prototype.scrollDownMessages = function(scrollPosition, scrollHeight) {  
  var newBottomScrollPosition = $(this.messagescontainer).prop('scrollHeight');

  if (Math.round(scrollPosition) == scrollHeight) {
    $(this.messagescontainer).scrollTop(newBottomScrollPosition);
  }
};