function Controller(contacts, conversation) {
  this.contacts = contacts;
  this.conversation = conversation;

  this.contacts.view.contactLinks.click(this, this.handleConversation);
}

Controller.prototype.handleConversation = function(e) {
  var controller = e.data;

  var datawith = $(this).attr('data-with');

  controller.conversation.getMessages(datawith);
  controller.conversation.refreshMessages(datawith);
}


function Contacts(modelView, view) {
  this.modelView  = modelView;
  this.view = view;

  this.view.searchbox.keydown(this.handleEnterKeyOnSearchForm.bind(this));
  this.view.searchform.submit(this.handleSubmitOnSearchForm.bind(this));

  this.view.contactList.click(this.view, this.view.turnCheckedClass);
  this.view.contactLinks.click(this.handleClickOnContact);
}

Contacts.prototype.handleEnterKeyOnSearchForm = function(e) {
  if (e.keyCode == 13) {
    $(this.view.searchform).submit();
  }
};

Contacts.prototype.handleSubmitOnSearchForm = function(e) {
  e.preventDefault();

  var that = this;

  var q = $(that.view.searchbox).val();

  $.get(
    'api/search.php',
    {q: q},

    function(data) {
      that.view.showContacts(data);

      window.history.pushState({}, '', '/search.php?q=' + q);
    }
  );

  return false;
};

Contacts.prototype.handleClickOnContact = function(e) {
  e.preventDefault();

  var datawith = $(this).attr('data-with');

  window.history.pushState({}, '', '/conversation.php?with=' + datawith);
};

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
  $(this.contactList).remove();
  $(this.contactsNotFoundMessage).remove();

  if ($.isEmptyObject(contacts)) {
    var html = new EJS({url: 'js/templates/contacts-not-found.ejs'}).render(data);
    $(this.contacts).html(html);
  } else {
    var data = {contacts: contacts};

    var html = new EJS({url: 'js/templates/contacts.ejs'}).render(data);

    $(this.contacts).html(html);

    this.contactList = $('.contacts li');
  }
}


function Conversation(modelView, view) {
  this.modelView = modelView;
  this.view = view;

  this.messagesInterval;

  this.view.messagebox.keydown(this.handleEnterKeyOnMessageForm.bind(this));
  this.view.messageform.submit(this.handleSubmitOnMessageForm.bind(this));
}

Conversation.prototype.handleEnterKeyOnMessageForm = function(e) {
  if (e.ctrlKey && e.keyCode == 13) {
    $(this.view.messagebox).val($(this.view.messagebox).val() + "\n");
  } else if (e.keyCode == 13) {
    $(this.view.messageform).submit();
    $(this.view.messagebox).val('');
  }
};

Conversation.prototype.handleSubmitOnMessageForm = function(e) {
  e.preventDefault();

  var to = $(this.view.messageform).attr('data-send-to');

  $.post(
    'api/send.php?to=' + to,
    {message: $(this.view.messagebox).val()},

    function(data) {
    }
  );

  return false;
};

Conversation.prototype.getMessages = function(datawith) {
  var that = this;

  $.get(
    'api/getmessages.php',
    {with: datawith},

    function(data) {
      that.view.showMessageForm(datawith);
      that.view.showMessages(data);
      that.view.moveMessagesToBottom();
      that.view.scrollDownMessages();
    }
  );
};

Conversation.prototype.refreshMessages = function(datawith) {
  var that = this;

  clearInterval(that.messagesInterval);

  that.messagesInterval = setInterval(function() {
    $.get(
      'api/getmessages.php',
      {with: datawith},

      function(data) {
        that.view.showMessages(data);
        that.view.moveMessagesToBottom();
        that.view.scrollDownMessages();
      }
    );
  }, 500);
};

function ConversationModelView() {

}

function ConversationView() {
  this.conversation = $('.conversation');
  this.messageblock = $('.messages');
  this.messages = $('.message');
  this.messageform = $('.message-form');
  this.messagebox = $('textarea[name="message"]');
  this.selectDialogMessage = $('.select-dialog');
}

ConversationView.prototype.showMessageForm = function(to) {
  if ($(this.selectDialogMessage).length) {
    $(this.selectDialogMessage).remove();

    $(this.messageform).remove();

    var data = {to: to};

    var html = new EJS({url: 'js/templates/messageform.ejs'}).render(data);

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

  var html = new EJS({url: 'js/templates/messages.ejs'}).render(data);

  $(this.messageblock).html(html);

  this.messages = $('.message');
};

ConversationView.prototype.moveMessagesToBottom = function() {
  var space = 0;

  var messageContainerHeight = $('.messages').outerHeight();
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
  var contactsModelView = new ContactsModelView();
  var contactsView = new ContactsView();
  var contacts = new Contacts(contactsModelView, contactsView);

  var conversationModelView = new ConversationModelView();
  var conversationView = new ConversationView();
  var conversation = new Conversation(conversationModelView, conversationView);

  var controller = new Controller(contacts, conversation);

  conversationView.moveMessagesToBottom();
  conversationView.scrollDownMessages();

  if ($(conversationView.messageform).length != 0) {
    var datawith  = $(conversationView.messageform).attr('data-send-to');

    conversation.refreshMessages(datawith);
  }
});