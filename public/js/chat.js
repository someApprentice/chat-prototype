function Controller(contacts, conversation) {
  this.contacts = contacts;
  this.conversation = conversation;

  this.contacts.handleEnterKeyOnSearchForm();
  this.contacts.handleSubmitOnSearchForm(this.conversation.runMessages.bind(this.conversation));

  this.contacts.handleClickOnContact(this.conversation.runMessages.bind(this.conversation));

  this.conversation.handleScrollOnMessageBlock();
}

function Backend() {

}

Backend.prototype.getContacts = function() {
  var promise = $.get({
    url: 'api/v1/getcontacts.php',
    dataType: 'json'
  });

  return promise;
};

Backend.prototype.searchContacts = function(query) {
  var promise = $.get(
    'api/v1/search.php',
    {q: query}
  );

  return promise;
}

Backend.prototype.getMessages = function(datawith, offset = 1) {
  var promise = $.get(
    'api/v1/getmessages.php',
    {
      with: datawith,
      offset: offset
    }
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

Backend.prototype.handleError = function(jqXHR, textStatus) {
  if (textStatus == "timeout") {
    throw new Error("Timeout");
  } else if (jqXHR.status == 0) {
    throw new Error("No connection");
  } else if (jqXHR.status == 500) {
    throw new Error("Server error");
  } else if (textStatus == "parsererror") {
    throw new Error("JSON decode error");
  }
}

$(document).ready(function() {
  var backend = new Backend();

  var contactsModelView = new ContactsModelView();
  var contactsView = new ContactsView();
  var contacts = new Contacts(backend, contactsModelView, contactsView);

  var conversationModelView = new ConversationModelView();
  var conversationView = new ConversationView();
  var conversation = new Conversation(backend, conversationModelView, conversationView);

  var controller = new Controller(contacts, conversation);

  controller.contacts.refreshContacts(conversation.runMessages.bind(conversation));

  conversationView.moveMessagesToBottom();

  if ($(conversationView.messageform).length != 0) {
    var datawith  = $(conversationView.messageform).attr('data-send-to');

    conversation.runMessages(datawith);
  }
});