function Contacts(backend, modelView, view) {
  this.backend = backend;
  this.modelView  = modelView;
  this.view = view;

  this.contactsInterval;
}

Contacts.prototype.handleEnterKeyOnSearchForm = function() {
  this.view.searchbox.keydown(
    function(e) {
      if (e.keyCode == 13) {
        $(this.view.searchform).submit();
      }
    }.bind(this)
  );
}

Contacts.prototype.handleSubmitOnSearchForm = function(runMessages) {
  this.view.searchform.submit(
    function(e) {
      e.preventDefault();

      var that = this;

      var q = $(that.view.searchbox).val();

      clearInterval(that.contactsInterval);

      if (q != '') {
        that.backend.searchContacts(q).then(
          function(data) {
            that.view.showContacts(data);
            that.handleClickOnContact(runMessages);
          },

          function(jqXHR, textStatus) {
            var template = $('#connection-error-template').html(); 
            var html = ejs.render(template);

            $('header').after(html);

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

    window.history.pushState({}, '', '/conversation.php?with=' + datawith);

    runMessages(datawith);
  });

  this.view.contactList.mousedown(this.view, this.view.turnCheckedClass);
};

Contacts.prototype.refreshContacts = function(runMessages) {
  var that = this;

  clearInterval(that.contactsInterval);

  that.contactsInterval = setInterval(function() {
    that.backend.getContacts().then(
      function(data) {
        that.view.showContacts(data);
        that.handleClickOnContact(runMessages);
      },

      function(jqXHR, textStatus) {
        clearInterval(that.contactsInterval);

        var template = $('#connection-error-template').html(); 
        var html = ejs.render(template);

        $('header').after(html);

        that.backend.handleError(jqXHR, textStatus);
      }
    )
  }, 500);
};

function ContactsModelView() {

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
}