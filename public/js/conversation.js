function Conversation(backend, modelView, view) {
  this.backend = backend;
  this.modelView = modelView;
  this.view = view;
}

Conversation.prototype.handleScrollOnMessageBlock = function() {
  this.view.messagescontainer.scroll(function() {
    var quarterOfMessageBlock = $(this.view.messageblock).prop('scrollHeight') / 4;

    if ($(this.view.moremessages).length) {
      if ($(this.view.messageblock).scrollTop() < quarterOfMessageBlock) {
        var datawith = $('a', this.view.moremessages).attr('data-with');
        var offset = $('a', this.view.moremessages).attr('data-offset');

        this.refreshMessages(datawith, offset);
      }
    }
  }.bind(this));
}

Conversation.prototype.handleClickOnMoreMessages = function() {
  $(this.view.moremessages).click(function(e) {
    e.preventDefault();

    var datawith = $('a', this.view.moremessages).attr('data-with');
    var offset = $('a', this.view.moremessages).attr('data-offset');

    this.runMessages(datawith, offset);
  }.bind(this));
};

Conversation.prototype.handleEnterKeyOnMessageForm = function() {
  this.view.messagebox.off('keydown');

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
  this.view.submit.off('click');

  this.view.submit.click(
    function(e) {
      e.preventDefault();

      $(this.view.messageform).submit();
      $(this.view.messagebox).val('');
    }.bind(this)
  );
};

Conversation.prototype.handleSubmitOnMessageForm = function() {
  this.view.messageform.off('submit');

  this.view.messageform.submit(
    function(e) {
      e.preventDefault();

      var that = this;

      var to = $(that.view.messageform).attr('data-send-to'),
          message =  $(that.view.messagebox).val(),
          token = that.view.token;

      if (message != '') {
        that.backend.postMessage(to, message, token).then(
          function(data) {
            // ...
          },

          function(jqXHR, textStatus) {
            var template = $('#connection-error-template').html(); 
            var html = ejs.render(template);

            $('header').after(html);

            that.backend.handleError(jqXHR, textStatus);
          }
        );
      }

      return false;
    }.bind(this)
  );
};

Conversation.prototype.runMessages = function(datawith, offset = 1) {
  var that = this;

  var url = window.location.href;
  
  that.modelView.messages = that.backend.getMessages(datawith, offset).then(
    function(data) {
      var actualUrl = window.location.href;

      if (url == actualUrl) {
        that.view.removeSelectDialog();

        that.view.showMoreMessagesButton(data['with'], +offset + +1, data['count'], data['totalCount']);
        that.handleClickOnMoreMessages();
        that.handleScrollOnMessageBlock();

        that.view.showMessageForm(datawith);
        that.handleEnterKeyOnMessageForm();
        that.handleClickOnMessageInput();
        that.handleSubmitOnMessageForm();

        that.view.showMessages(data);

        if (offset < 2) {
          var scrollPosition = $(that.view.messagescontainer).prop('scrollHeight');

          that.view.scrollDownMessages(scrollPosition, scrollPosition); 
        }

        return data;
      }
    },

    function(jqXHR, textStatus) {
      clearInterval(that.modelView.messagesInterval);

      var template = $('#connection-error-template').html(); 
      var html = ejs.render(template);

      $('header').after(html);

      that.backend.handleError(jqXHR, textStatus);
    }
  );

  that.refreshMessages(datawith, offset);
}

Conversation.prototype.refreshMessages = function(datawith, offset = 1) {
  var that = this;

  clearInterval(that.modelView.messagesInterval);

  that.modelView.messagesInterval = setInterval(function() {
    var url = window.location.href;

    that.modelView.messages = that.backend.getMessages(datawith, offset).then(
      function(data) {
        var actualUrl = window.location.href;

        var scrollPosition = $(that.view.messagescontainer).scrollTop() + $(that.view.messagescontainer).height();      
        var scrollHeight = $(that.view.messagescontainer).prop('scrollHeight');

        if (url == actualUrl) {
          that.view.showMoreMessagesButton(data['with'], +offset + +1, data['count'], data['totalCount']);
          that.handleClickOnMoreMessages();

          that.view.showMessages(data);
          that.view.scrollDownMessages(scrollPosition, scrollHeight);

          return data;
        }
      },

      function(jqXHR, textStatus) {
        clearInterval(that.modelView.messagesInterval);

        var template = $('#connection-error-template').html(); 
        var html = ejs.render(template);

        $('header').after(html);

        that.backend.handleError(jqXHR, textStatus);
      }
    );
  }, 300);
};

function ConversationModelView() {
  this.messages = {};

  this.messagesInterval;
}

function ConversationView() {
  this.conversation = $('.conversation');

  this.moremessages = $('.get-more-messages', this.conversation);
  this.messagescontainer = $('.messages-container', this.conversation);
  this.messageblock = $('.messages', this.conversation);
  this.messages = $('.message', this.conversation);

  this.messageform = $('.message-form', this.conversation);
  this.messagebox = $('textarea[name="message"]', this.messageform);
  this.submit = $('input[type="submit"]', this.messageform);

  this.selectDialog = $('.select-dialog', this.conversation);

  this.token = Cookies.get('token');
}

ConversationView.prototype.showMoreMessagesButton = function(datawith, offset, count, totalCount) {
  var data = {
    datawith: datawith,
    offset: offset,
    count: count,
    totalCount: totalCount
  };

  var template = $('#get-more-messages-template').html();
  var html = ejs.render(template, data);

  if ($(this.moremessages).length) {
    $(this.moremessages).replaceWith(html);

    this.moremessages = $('.get-more-messages');
  } else {
    $(this.messagescontainer).prepend(html);

    this.moremessages = $('.get-more-messages');
  }
};

ConversationView.prototype.showMessageForm = function(to) {
  if ($(this.messageform).length) {

    $(this.messageform).attr('action', 'send.php?to=' + to);
    $(this.messageform).attr('data-send-to', to);
    $('input[name="token"]', this.messageform).val(this.token);
  } else {
    var data = {
      to: to,
      token: this.token
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
    datawith: messages['with'],
    offset: messages['offset'],
    count: messages['count'],
    totalCount: messages['totalCount'],
    messages: messages['messages']
  };

  var template = $('#messages-template').html();    
  var html = ejs.render(template, data);

  $(this.messageblock).html(html);

  $(this.messageblock.find('.content')).each(function() {
    var text = $(this).text().replace(/[\r\n]/g, "<br>");

    $(this).html(text);
  });

  this.messages = $('.message');
};

ConversationView.prototype.removeSelectDialog = function() {
  if ($(this.selectDialog).length) {
    $(this.selectDialog).remove();

    this.selectDialog = $('.select-dialog');
  }
}

// may b' there is some better way
ConversationView.prototype.scrollDownMessages = function(scrollPosition, scrollHeight) {  
  var newBottomScrollPosition = $(this.messagescontainer).prop('scrollHeight');

  if (Math.round(scrollPosition) == scrollHeight) {
    $(this.messagescontainer).scrollTop(newBottomScrollPosition);
  }
};