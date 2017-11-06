function Chat(backend, crypter, contacts, conversation, view) {
  this.backend = backend;
  this.crypter = crypter;
  this.contacts = contacts;
  this.conversation = conversation;

  this.view = view;

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

Chat.prototype.handleSubmitOnRegisterForm = function() {
  $(this.view.authorizationForm).submit(function(e) {
    e.preventDefault();

    var that = this;

    var login = $(that.view.loginAuthorizationBox).val(),
        name = $(that.view.nameAuthorizationBox).val(),
        password = $(that.view.passwordAuthorizationBox).val(),
        retryPassword = $(that.view.retryPasswordAuthorizationBox).val();

    $(that.view.authorizationForm).remove();

    that.view.showKeysLoader();

    that.backend.register(login, name, password, retryPassword).then(
      function(data) {
        $(that.view.keysGeneration).remove();

        if (data['status'] == 'Ok') {
          that.login(data.user.id, data.user.name, data.user.hash, data.user.token);

          that.crypter.passphrase = password;
        } else {
          that.view.showAuthorizationForm(login, name, data.errors);
          that.handleSubmitOnRegisterForm();
        }
      }
    );

  }.bind(this));
}

Chat.prototype.handleSubmitOnLoginForm = function() {
  $(this.view.authenticationForm).submit(function(e) {
    e.preventDefault();

    var that = this;

    var login = $(that.view.loginAuthenticationBox).val(),
        password = $(that.view.passwordAuthenticationBox).val();

    that.backend.login(login, password).then(function(data) {
      $(that.view.loginAuthenticationBox).next('.form-error').text('');

      if (data['status'] == 'Ok') {
        $(that.view.authenticationForm).remove();

        that.login(data.user.id, data.user.name, data.user.hash, data.user.token);

        that.crypter.passphrase = password;
      } else {
        $(that.view.loginAuthenticationBox).next('.form-error').text(data.errors['login']);
      }
    });
  }.bind(this));
};

Chat.prototype.login = function(id, name, hash, token) {
  var that = this;

  var expires = 30 * 12 * 3;

  Cookies.set('id', id, { expires: expires });
  Cookies.set('hash', hash, { expires: expires });
  Cookies.set('token', token, { expires: expires });

  that.view.showHeader(name, token);

  that.view.showChatBox();

  that.contacts.view.contactBox = $('.contact-box');
  that.contacts.view.searchForm = $('.search-form', that.contacts.view.contactBox);
  that.contacts.view.searchBox = $('input[name="q"]', that.contacts.view.searchForm);

  that.contacts.view.contacts = $('.contacts', that.contacts.view.contactBox);

  that.conversation.token = Cookies.get('token');

  that.conversation.view.conversation = $('.conversation');

  that.conversation.view.messagesContainer = $('.messages-container', that.conversation.view.conversation);
  that.conversation.view.messageBlock = $('.messages', that.conversation.view.conversation);

  that.contacts.handleEnterKeyOnSearchForm();
  that.contacts.handleSubmitOnSearchForm(that.conversation.runMessages.bind(that.conversation));

  that.contacts.refreshContacts(that.conversation.runMessages.bind(that.conversation));

  if ($(that.conversation.view.messageForm).length != 0) {
    var datawith  = $(that.conversation.view.messageForm).attr('data-send-to');

    that.conversation.runMessages(datawith);
  }

  window.history.pushState({}, '', '/');
};

function ChatView() {
  this.authorizationForm = $('.authorization-form[name="registration"]');
  this.loginAuthorizationBox = $('input[name="login"]', this.authorizationForm);
  this.nameAuthorizationBox = $('input[name="name"]', this.authorizationForm);
  this.passwordAuthorizationBox = $('input[name="password"]', this.authorizationForm);
  this.retryPasswordAuthorizationBox = $('input[name="retryPassword"]', this.authorizationForm);

  this.authenticationForm = $('.authorization-form[name="login"]');
  this.loginAuthenticationBox = $('input[name="login"]', this.authenticationForm);
  this.passwordAuthenticationBox = $('input[name="password"]', this.authenticationForm);

  this.keysGeneration = $('.keys-generation');
}

ChatView.prototype.showHeader = function(name, token) {
  var template = $('#header-template').html();
  var html = ejs.render(
    template,
    {
      name: name,
      token: token
    }
  );

  $('body').append(html);
};

ChatView.prototype.showAuthorizationForm = function(login, name, errors) {
  var template = $('#authorization-form-template').html();
  var html = ejs.render(
    template,
    {
      login: login,
      name: name,
      errors: errors
    }  
  );

  $('.container').append(html);

  this.authorizationForm = $('.authorization-form[name="registration"]');
  this.loginAuthorizationBox = $('input[name="login"]', this.authorizationForm);
  this.nameAuthorizationBox = $('input[name="name"]', this.authorizationForm);
  this.passwordAuthorizationBox = $('input[name="password"]', this.authorizationForm);
  this.retryPasswordAuthorizationBox = $('input[name="retryPassword"]', this.authorizationForm);
};

ChatView.prototype.showChatBox = function() {
  var template = $('#chat-box-template').html();
  var html = ejs.render(template, {});

  $('body').append(html);
};

ChatView.prototype.showKeysLoader = function() {
  var template = $('#keys-generation-template').html();
  var html = ejs.render(template, {});

  $('body').append(html);

  this.keysGeneration = $('.keys-generation');
};

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

  backend.getLogged().then(function(logged) {
    if (logged['status'] == 'Ok') {
      controller.contacts.refreshContacts(conversation.runMessages.bind(conversation));

      if ($(conversationView.messageForm).length != 0) {
        var datawith  = $(conversationView.messageForm).attr('data-send-to');

        conversation.runMessages(datawith);
      }
    } else {
      controller.handleSubmitOnRegisterForm();
      controller.handleSubmitOnLoginForm();
    }
  });
});