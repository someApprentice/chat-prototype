function Conversation(backend, modelView, view) {
  this.backend = backend;
  this.modelView = modelView;
  this.view = view;

  this.messagesInterval;
}

Conversation.prototype.refreshMessages = function(datawith) {
  var that = this;

  clearInterval(that.messagesInterval);

  that.messagesInterval = setInterval(function() {
    var url = window.location.href;

    that.backend.getMessages(datawith).then(function(data) {
      var actualUrl = window.location.href;

      if (url == actualUrl) {
        that.view.showMessages(data);
        that.view.moveMessagesToBottom();
        that.view.scrollDownMessages();
      }
    })
  }, 300);
};

function ConversationModelView() {

}

function ConversationView() {
  this.conversation = $('.conversation');
  this.messageblock = $('.messages');
  this.messages = $('.message');

  this.messageform = $('.message-form');
  this.messagebox = $('textarea[name="message"]');
  this.submit = $('.message-form input[type="submit"]');
  this.token = Cookies.get('token');

  this.selectDialogMessage = $('.select-dialog');
}

ConversationView.prototype.showMessageForm = function(to) {
  if ($(this.selectDialogMessage).length) {
    $(this.selectDialogMessage).remove();

    $(this.messageform).remove();

    var data = {
      to: to,
      token: this.token
    };

    var template = $('#message-form-template').html();
    var html = ejs.render(template, data);

    $(this.conversation).append(html);

    this.messageform = $('.message-form');
  } else if ($(this.messageform).attr('data-send-to') != to) {
    $(this.messageform).attr('action', 'send.php?to=' + to);
    $(this.messageform).attr('data-send-to', to);
  }
};

ConversationView.prototype.showMessages = function(messages) {
  this.messages.remove();

  var data = {messages: messages};
  var template = $('#messages-template').html();
    
  var html = ejs.render(template, data);

  $(this.messageblock).html(html);

  $(this.messageblock.find('.content')).each(function() {
    var text = $(this).text().replace(/[\r\n]/g, "<br>");

    $(this).html(text);
  });

  this.messages = $('.message');
};

ConversationView.prototype.moveMessagesToBottom = function() {
  var space = 0;

  var messageContainerHeight = $(this.messageblock).outerHeight();
  var messagesHeight = 0;

  $.each(this.messages, function(i, message) {
    messagesHeight += message.offsetHeight;
  });

  if (messagesHeight < messageContainerHeight) {
    space = messageContainerHeight - messagesHeight;

    this.messages.first().css('padding-top', space);
  } 
};

ConversationView.prototype.scrollDownMessages = function() {
  $(this.messageblock).scrollTop($(this.messageblock)[0].scrollHeight);
};