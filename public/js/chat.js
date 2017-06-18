function Controller(model, view) {
  this.model = model;
  this.view = view;

  this.messagesInterval;

  this.view.searchbox.keydown(this.handleEnterKeyOnSearchForm.bind(this));
  this.view.searchform.submit(this.handleSubmitOnSearchForm.bind(this));

  this.view.contactList.click(this, this.handleClickOnContactItem);
  this.view.contactLinks.click(this, this.handleClickOnContact);

  this.view.messagebox.keydown(this.handleEnterKeyOnMessageForm.bind(this));
  this.view.messageform.submit(this, this.handleSubmitOnMessageForm);
}

Controller.prototype.handleEnterKeyOnMessageForm = function(e) {
  if (e.ctrlKey && e.keyCode == 13) {
    $(this.view.messagebox).val($(this.view.messagebox).val() + "\n");
  } else if (e.keyCode == 13) {
    $(this.view.messageform).submit();
    $(this.view.messagebox).val('');

    console.log($(this.view.messagebox).val());
  }
};

Controller.prototype.handleSubmitOnMessageForm = function(e) {
  e.preventDefault();

  var controller = e.data;

  $.post(
    'api/send.php?to=' + $(this).attr('data-send-to'),
    {message: $(controller.view.messagebox).val()},

    function(data) {
    }
  );

  return false;
};

Controller.prototype.handleEnterKeyOnSearchForm = function(e) {
  if (e.keyCode == 13) {
    $(this.view.searchform).submit();
  }
};

Controller.prototype.handleSubmitOnSearchForm = function(e) {
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

Controller.prototype.handleClickOnContact = function(e) {
  e.preventDefault();

  var controller = e.data;

  var datawith = $(this).attr('data-with');

  $.get(
    'api/getmessages.php',
    {with: datawith},

    function(data) {
      controller.view.showMessageForm(datawith);
      controller.view.showMessages(data);
      controller.view.moveMessagesToBottom();
      controller.view.scrollDownMessages();

      window.history.pushState({}, '', '/conversation.php?with=' + datawith);
    }
  );

  clearInterval(controller.messagesInterval);

  controller.messagesInterval = setInterval(function() {
    $.get(
      'api/getmessages.php',
      {with: datawith},

      function(data) {
        controller.view.showMessages(data);
        controller.view.moveMessagesToBottom();
        controller.view.scrollDownMessages();
      }
    );
  }, 500);
};

Controller.prototype.handleClickOnContactItem = function(e) {
  var controller = e.data;

  $(controller.view.contactList).removeClass('checked');
  $(this).addClass('checked');
};

function Model() {

}

function View() {
  this.searchform = $('.search-form');
  this.searchbox = $('input[name="q"]');

  this.contactList = $('.contacts li');
  this.contactLinks = $('.contacts a');
  this.contactsNotFoundMessage = $('.contacts-not-found');

  this.messageblock = $('.messages');
  this.messages = $('.message');
  this.messageform = $('.message-form');
  this.messagebox = $('textarea[name="message"]');
  this.selectDialogMessage = $('.select-dialog');
}

View.prototype.showContacts = function(contacts) {
  $(this.contactList).remove();
  $(this.contactsNotFoundMessage).remove();

  if ($.isEmptyObject(contacts)) {
    $('<span/>', {class: 'contacts-not-found'}).appendTo($('.contacts'));
    $(this.contactsNotFoundMessage).html("Ничего не найдено");
  } else {
    $.each(contacts, function(i, contact) {
      $('<li/>').attr('data-contacat-id', contact.id).appendTo($('.contacts'));

      $('<a/>', {href: 'conversation.php?with=' + contact.id}).attr('data-with', contact.id).appendTo($('li[data-contacat-id=' + contact.id + ']'));

      $('<label/>').appendTo($('a[data-with=' + contact.id + ']')).html(contact.name);
    });
  }

  this.contactList = $('.contacts li');
}

View.prototype.showMessageForm = function(to) {
  if ($(this.selectDialogMessage).length) {
    $(this.selectDialogMessage).remove();

    $(this.messageform).remove();

    this.messageform = $('<form/>', {
      method: 'post',
      name: 'message-form',
      class: 'message-form',
      action: 'send.php?to=' + to
    }).appendTo($('.conversation'));

    $(this.messageform).attr('data-send-to', to);

    $('<textarea/>', {name: 'message'}).appendTo($(this.messageform));
    $('<input/>', {type: 'submit', name: 'submit', value: 'Отправить'}).appendTo($(this.messageform));
  } else if ($('.message-form').attr('data-send-to') != to) {
    $(this.messageform).attr('action', 'send.php?to=' + to);
    $(this.messageform).attr('data-send-to', to);
  }
};

View.prototype.showMessages = function(messages) {
  this.messages.remove();

  $.each(messages, function(i, message) {
    $('<div/>', {class: 'message'}).attr('data-message-id', message.id).appendTo($('.messages'));

    $('<span/>', {class:'date'}).appendTo($('.message[data-message-id=' + message.id + ']'));
    $('.message[data-message-id=' + message.id + '] .date').html(message.date);

    $('<div/>', {class: 'message-container'}).appendTo($('.message[data-message-id=' + message.id + ']'));

    $('<div/>', {class: 'author'}).appendTo($('.message[data-message-id=' + message.id + '] .message-container'));
    $('<a/>', {href: 'user.php?id=' + message.author}).appendTo($('.message[data-message-id=' + message.id + '] .author'));
    $('.message[data-message-id=' + message.id + '] .author a').html(message.author);

    $('<div/>', {class: 'content'}).appendTo($('.message[data-message-id=' + message.id + '] .message-container'));
    $('.message[data-message-id=' + message.id + '] .content').html(message.content);
  });

  this.messages = $('.message');
};

View.prototype.moveMessagesToBottom = function() {
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

View.prototype.scrollDownMessages = function() {
  $(this.messageblock).scrollTop($(this.messageblock)[0].scrollHeight);
};

var model = new Model();
var view = new View();
var controller = new Controller(model, view);

view.moveMessagesToBottom();
view.scrollDownMessages();