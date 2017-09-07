function Contacts(backend, modelView, view) {
  this.backend = backend;
  this.modelView  = modelView;
  this.view = view;

  this.timeout;
  this.t = 500;
}

Contacts.prototype.handleEnterKeyOnSearchForm = function() {
  this.view.searchbox.keydown(
    function(e) {
      if (e.keyCode == 13) {
        $(this.view.searchform).submit();
      }
    }.bind(this)
  );
};

Contacts.prototype.handleSubmitOnSearchForm = function(runMessages) {
  this.view.searchform.submit(
    function(e) {
      e.preventDefault();

      var that = this;

      var q = $(that.view.searchbox).val();

      clearTimeout(that.timeout);

      if (q != '') {
        that.backend.searchContacts(q).then(
          function(data) {
            that.view.showContacts(data);
            that.handleClickOnContact(runMessages);
          },

          function(jqXHR, textStatus) {
            that.view.showConnectionError();

            that.backend.handleError(jqXHR, textStatus);
          }
        );
      } else {
        that.refreshContacts(runMessages);
      }

      return false;
    }.bind(this)
  );
};

Contacts.prototype.handleClickOnContact = function(runMessages) {
  this.view.contactLinks.click(function(e) {
    e.preventDefault();
  });

  this.view.contactLinks.mousedown(function(e) {
    var datawith = $(this).attr('data-with');

    var url = window.location.href;
    var matches = url.match(/with=(\d+)/);

    if (!matches || datawith != matches[1]) {
      window.history.pushState({}, '', '/conversation.php?with=' + datawith);
    }

    runMessages(datawith);
  });

  this.view.contactList.mousedown(this.view, this.view.turnCheckedClass);
};

Contacts.prototype.refreshContacts = function(runMessages) {
  var that = this;

  that.modelView.contacts = that.backend.getContacts().then(
    function(data) {
      that.view.showContacts(data);
      that.handleClickOnContact(runMessages);

      clearTimeout(that.timeout);

      that.timeout = setTimeout(function() {
        that.refreshContacts(runMessages);
      }, that.t);

      return data;
    },

    function(jqXHR, textStatus) {
      that.view.showConnectionError();

      that.backend.handleError(jqXHR, textStatus);
    }
  );
};


function ContactsModelView() {
  this.contacts;
}

function ContactsView() {
  this.contactbox = $('.contact-box');
  this.searchform = $('.search-form', this.contactbox);
  this.searchbox = $('input[name="q"]', this.searchform);

  this.contacts = $('.contacts', this.contactbox);
  this.contactList = $('li', this.contacts);
  this.contactLinks = $('a', this.contacts);

  this.contactsNotFoundMessage = $('.contacts-not-found', this.contactbox);
}

ContactsView.prototype.turnCheckedClass = function(e) {
  var that = e.data;

  $(that.contactList).removeClass('checked');
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
};

ContactsView.prototype.showConnectionError = function() {
  if ($('.connection-error').length == 0) {
    var template = $('#connection-error-template').html(); 
    var html = ejs.render(template);

    $('header').after(html);

    var removeErrorTimeot = setTimeout(function() {
      $('.connection-error').remove();
    }, 5000);
  }
};