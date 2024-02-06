A simple JavaScript chat application that is implemented with prototypes.

[./public/js/chat.js](public/js/chat.js)
```
function Chat(backend, crypter, contacts, conversation, view) {
  this.backend = backend;
  this.crypter = crypter;
  this.contacts = contacts;
  this.conversation = conversation;

  this.view = view;

  // The following code MUST be in the appropriate component view custructors.
  // This code was implemented 6 or 7 years ago and I didn't have enough knowledge.
  this.contacts.handleEnterKeyOnSearchForm();
  this.contacts.handleSubmitOnSearchForm(this.conversation.runMessages.bind(this.conversation));

  if ($(this.conversation.view.moreMessages).length != 0) {
    this.conversation.handleClickOnMoreMessages();
    this.conversation.handleScrollOnMessageBlock();
  }

  this.conversation.handleEnterKeyOnMessageForm();
  this.conversation.handleClickOnMessageInput();
  this.conversation.handleSubmitOnMessageForm();
}

...

Chat.prototype.login = function(id, name, hash, token) {
  // execute a client authentication logic
};

...

$(document).ready(function() {
  var backend = new Backend();

  var crypter = new Crypter();

  var contactsModelView = new ContactsModelView();
  var contactsView = new ContactsView();
  var contacts = new Contacts(backend, contactsModelView, contactsView);

  var conversationView = new ConversationView();
  var conversation = new Conversation(backend, crypter, conversationView);

  var controllerView = new ChatView();
  var controller = new Chat(backend, crypter, contacts, conversation, controllerView);

  ...

  // run the application
});
```
