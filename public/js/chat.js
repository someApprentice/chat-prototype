function Controller(contacts, conversation) {
  this.contacts = contacts;
  this.conversation = conversation;

  this.contacts.view.searchbox.keydown(this.handleEnterKeyOnSearchForm.bind(this));
  this.contacts.view.searchform.submit(this.handleSubmitOnSearchForm.bind(this));

  this.contacts.view.contactList.click(this.contacts.view, this.contacts.view.turnCheckedClass);
  this.contacts.view.contactLinks.click(this.handleClickOnContact);
  this.contacts.view.contactLinks.mousedown(this, this.handleConversation);

  this.conversation.view.messagebox.keydown(this.handleEnterKeyOnMessageForm.bind(this));
  this.conversation.view.submit.click(this.handleClickOnMessageInput.bind(this));
  this.conversation.view.messageform.submit(this.handleSubmitOnMessageForm.bind(this));
}

Controller.prototype.handleEnterKeyOnSearchForm = function(e) {
  if (e.keyCode == 13) {
    $(this.contacts.view.searchform).submit();
  }
};

Controller.prototype.handleSubmitOnSearchForm = function(e) {
  e.preventDefault();

  var that = this;

  var q = $(that.contacts.view.searchbox).val();

  clearInterval(that.contacts.contactsInterval);

  if (q != '') {
    that.contacts.backend.searchContacts(q).then(function(data) {
      that.contacts.view.showContacts(data);
    });
  } else {
    that.refreshContacts();
  }

  return false;
};

Controller.prototype.handleClickOnContact = function(e) {
  e.preventDefault();

  var datawith = $(this).attr('data-with');

  window.history.pushState({}, '', '/conversation.php?with=' + datawith);
};

Controller.prototype.refreshContacts = function() {
  var that = this;

  clearInterval(that.contacts.contactsInterval);

  that.contacts.contactsInterval = setInterval(function() {
    that.contacts.backend.getContacts().then(function(data) {
      that.contacts.view.showContacts(data);

      that.contacts.view.contactLinks.click(function(e) {
        e.preventDefault();
      });

      that.contacts.view.contactList.mousedown(that.contacts.view, that.contacts.view.turnCheckedClass);
      that.contacts.view.contactLinks.mousedown(that.handleClickOnContact);
      that.contacts.view.contactLinks.mousedown(that, that.handleConversation);
    });
  }, 500);
};

Controller.prototype.handleEnterKeyOnMessageForm = function(e) {
  if (e.ctrlKey && e.keyCode == 13) {
    $(this.conversation.view.messagebox).val($(this.conversation.view.messagebox).val() + "\n");
  } else if (e.keyCode == 13) {
    e.preventDefault();

    $(this.conversation.view.messageform).submit();
    $(this.conversation.view.messagebox).val('');
  }
};

Controller.prototype.handleClickOnMessageInput = function(e) {
  e.preventDefault();

  $(this.conversation.view.messageform).submit();
  $(this.conversation.view.messagebox).val('');
};

Controller.prototype.handleSubmitOnMessageForm = function(e) {
  e.preventDefault();

  var to = $(this.conversation.view.messageform).attr('data-send-to'),
      message =  $(this.conversation.view.messagebox).val(),
      token = this.conversation.view.token;

  this.conversation.backend.postMessage(to, message, token).then(function(data) {
    // ...
  });

  return false;
};

Controller.prototype.handleConversation = function(e) {
  var controller = e.data;

  var datawith = $(this).attr('data-with');

  var url = window.location.href;

  controller.conversation.backend.getMessages(datawith).then(function(data) {
    var actualUrl = window.location.href;

    if (url == actualUrl) {
      controller.conversation.view.showMessageForm(datawith);
      controller.conversation.view.showMessages(data);
      controller.conversation.view.moveMessagesToBottom();
      controller.conversation.view.scrollDownMessages();  
    }
  });

  controller.conversation.refreshMessages(datawith);
}


function Backend() {

}

Backend.prototype.getContacts = function() {
  var promise = $.get(
    'api/v1/getcontacts.php'
  );

  return promise;
};

Backend.prototype.searchContacts = function(query) {
  var promise = $.get(
    'api/v1/search.php',
    {q: query}
  );

  return promise;
}

Backend.prototype.getMessages = function(datawith) {
  var promise = $.get(
    'api/v1/getmessages.php',
    {with: datawith}
  );

  return promise;
};

Backend.prototype.postMessage = function(to, message, token) {
  var promise = $.post(
    'api/v1/send.php?to=' + to,
    {
      message: message,
      token: token
    }
  );

  return promise;
};


function Contacts(backend, modelView, view) {
  this.backend = backend;
  this.modelView  = modelView;
  this.view = view;

  this.contactsInterval;
}

function ContactsModelView() {

}

function ContactsView() {
  this.searchform = $('.search-form');
  this.searchbox = $('input[name="q"]');

  this.contacts = $('.contacts');
  this.contactList = $('.contacts li');
  this.contactLinks = $('.contacts a');
  this.contactsNotFoundMessage = $('.contacts-not-found');
}

ContactsView.prototype.turnCheckedClass = function(e) {
  var view = e.data;

  $(view.contactList).removeClass('checked');
  $(this).addClass('checked');
};

ContactsView.prototype.showContacts = function(contacts) {
  var checked = $('.checked');

  if(checked.length == 1) {
    var checkedId = checked.children().attr('data-with');
  }

  $(this.contactList).remove();
  $(this.contactsNotFoundMessage).remove();

  if ($.isEmptyObject(contacts)) {
    var template = $('#contacts-not-found-template').html();
    var html = ejs.render(template, data);

    $(this.contacts).html(html);
  } else {
    var data = {
      contacts: contacts,
      checkedId: checkedId
    };

    var template = $('#contacts-template').html(); 
    var html = ejs.render(template, data);

    $(this.contacts).html(html);

    this.contactList = $('.contacts li');
    this.contactLinks = $('.contacts a');
  }
}


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


$(document).ready(function() {
  var backend = new Backend();

  var contactsModelView = new ContactsModelView();
  var contactsView = new ContactsView();
  var contacts = new Contacts(backend, contactsModelView, contactsView);

  var conversationModelView = new ConversationModelView();
  var conversationView = new ConversationView();
  var conversation = new Conversation(backend, conversationModelView, conversationView);

  var controller = new Controller(contacts, conversation);

  controller.refreshContacts();

  conversationView.moveMessagesToBottom();
  conversationView.scrollDownMessages();

  if ($(conversationView.messageform).length != 0) {
    var datawith  = $(conversationView.messageform).attr('data-send-to');

    conversation.refreshMessages(datawith);
  }
});