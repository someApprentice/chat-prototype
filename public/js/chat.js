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
      },

      function(jqXHR, textStatus) {
        var template = $('#connection-error-template').html(); 
        var html = ejs.render(template);

        $('header').after(html);

        that.contacts.backend.handleError(jqXHR, textStatus);
      }
    );
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
    },

    function(jqXHR, textStatus) {
      clearInterval(that.contacts.contactsInterval);

      var template = $('#connection-error-template').html(); 
      var html = ejs.render(template);

      $('header').after(html);

      that.contacts.backend.handleError(jqXHR, textStatus);
    }
  )}, 500);
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
    },

    function(jqXHR, textStatus) {
      var template = $('#connection-error-template').html(); 
      var html = ejs.render(template);

      $('header').after(html);

      that.contacts.backend.handleError(jqXHR, textStatus);
    }
  );

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
    },

    function(jqXHR, textStatus) {
      clearInterval(that.conversation.messagesInterval);

      var template = $('#connection-error-template').html(); 
      var html = ejs.render(template);

      $('header').after(html);

      that.contacts.backend.handleError(jqXHR, textStatus);
    }
  );

  controller.conversation.refreshMessages(datawith);
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

  controller.refreshContacts();

  conversationView.moveMessagesToBottom();
  conversationView.scrollDownMessages();

  if ($(conversationView.messageform).length != 0) {
    var datawith  = $(conversationView.messageform).attr('data-send-to');

    conversation.refreshMessages(datawith);
  }
});