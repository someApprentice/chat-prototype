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