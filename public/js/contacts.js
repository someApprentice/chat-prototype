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
  this.view.contactList.click(this.view, this.view.turnCheckedClass);

  this.view.contactLinks.click(function(e) {
    e.preventDefault();

    var datawith = $(this).attr('data-with');

    window.history.pushState({}, '', '/conversation.php?with=' + datawith);
  });

  this.view.contactLinks.mousedown(runMessages);
};

Contacts.prototype.refreshContacts = function(runMessages) {
  var that = this;

  clearInterval(that.contactsInterval);

  that.contactsInterval = setInterval(function() {
    that.backend.getContacts().then(
      function(data) {
        that.view.showContacts(data);

        that.view.contactLinks.click(function(e) {
          e.preventDefault();
        });

        that.view.contactList.mousedown(that.view, that.view.turnCheckedClass);
        that.view.contactLinks.mousedown(that.handleClickOnContact.bind(that));
        that.view.contactLinks.mousedown(
          function() {
            var datawith = $(this).attr('data-with');

            runMessages(datawith);
          }
        );
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
  this.searchform = $('.search-form');
  this.searchbox = $('input[name="q"]');

  this.contacts = $('.contacts');
  this.contactList = $('.contacts li');
  this.contactLinks = $('.contacts a');
  this.contactsNotFoundMessage = $('.contacts-not-found');
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