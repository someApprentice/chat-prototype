function Contreller(model, view) {
    this.model = model;
    this.view = view;
}

Contreller.prototype.getConversation = function(withUser) {
    var that = this;

    $.getJSON('api/getmessages.php?with=' + withUser, function(results) {
        that.view.messageForm(withUser);
        that.view.messages(results);
        that.moveMessagesToBottom();
        
        $('.messages').scrollTop($('.messages')[0].scrollHeight);

        window.history.pushState({}, '', '/conversation.php?with=' + withUser);
    });
}

Contreller.prototype.searchContacts = function(response, query) {
    var contacts = $.parseJSON(response);

    this.view.contacts(contacts);

    window.history.pushState({}, '', '/search.php?q=' + query);
}

Contreller.prototype.getContacts = function() {
    var that = this;
    $.getJSON('api/getcontacts.php', function(results) {
        that.view.contacts(results);
    });
}

Contreller.prototype.moveMessagesToBottom = function() {
    var space = this.model.countEmptySpaceInMessageBox();

    this.view.addSpaceToFirstMessage(space);

    $('.messages').scrollTop($('.messages')[0].scrollHeight);
}


function Model() {

}

Model.prototype.countEmptySpaceInMessageBox = function() {
    var space = 0;

    var messageContainerHeight = $('.messages').outerHeight();
    var messagesHeight = 0;

    $.each($('.message'), function(i, message) {
        messagesHeight += message.offsetHeight;
    });

    if (messagesHeight < messageContainerHeight) {
        space = messageContainerHeight - messagesHeight;
    } else {
        space = $('.message:first-child').css('padding-top');
    }

    return space;
}


function View() {

}

View.prototype.contacts = function(contacts) {
    $('.contacts li').remove();
    $('.contacts-not-found').remove();

    if ($.isEmptyObject(contacts)) {
        $('<span/>', {class: 'contacts-not-found'}).appendTo($('.contacts'));
        $('.contacts-not-found').html("Ничего не найдено");
    } else {
        $.each(contacts, function(i, contact) {
            $('<li/>').attr('data-contacat-id', contact.id).appendTo($('.contacts'));

            $('<a/>', {href: 'conversation.php?with=' + contact.id}).attr('data-with', contact.id).appendTo($('li[data-contacat-id=' + contact.id + ']'));

            $('<label/>').appendTo($('a[data-with=' + contact.id + ']')).html(contact.name);
        });
    }
}

View.prototype.messageForm = function(to) {
    if ($('.select-dialog').length) {
        $('.select-dialog').remove();

        $('<form/>', {
            method: 'post',
            name: 'message-form',
            class: 'message-form',
            action: 'send.php?to=' + to
        }).appendTo($('.conversation'));

        $('.message-form').attr('data-send-to', to);

        $('<textarea/>', {name: 'message'}).appendTo($('.message-form'));
        $('<input/>', {type: 'submit', name: 'submit', value: 'Отправить'}).appendTo($('.message-form'));
    } else if ($('.message-form').attr('data-send-to') != to) {
        $('.message-form').attr('action', 'send.php?to=' + to);
        $('.message-form').attr('data-send-to', to);
    }
};

View.prototype.messages = function(messages) {
    $('.message').remove();

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
};

View.prototype.addSpaceToFirstMessage = function(space) {
    $('.message:first-child').css({'padding-top': space});
}


$(document).ready(function() {
    var model = new Model();
    var view = new View();
    var controller = new Contreller(model, view);

    controller.moveMessagesToBottom();

    $('textarea[name="message"]').keydown(function (e) {
      if (e.ctrlKey && e.keyCode == 13) {
        $('textarea[name="message"]').val($('textarea[name="message"]').val() + "\n");
      } else if (e.keyCode == 13) {
        $('.message-form').submit();
        $('textarea[name="message"]').val('');
      }
    });


    $('input[name="q"]').keydown(function (e) {
      if (e.keyCode == 13) {
        $('.search-form-form').submit();
      }
    });


    $('.search-form').on('submit', function (e) {
        var q = $('input[name=q]').val();

        $.get(
            'api/search.php',
            {q: q},
            function(response) {
                controller.searchContacts(response, q);
            }
        );

        e.preventDefault();
    });


    $('.contacts a').on('click', function(e) {
        var datawith = $(this).attr('data-with');

        controller.getConversation(datawith);

        e.preventDefault();
    });

    $('.contacts li').on('click', function(e) {
        $('.contacts li').removeClass('checked');
        $(this).addClass('checked');
    });
    

    $('.message-form').on('submit', function(e) {
        $.post(
            'api/send.php?to=' + $(this).attr('data-send-to'),
            {message: $('textarea[name="message"]').val()},
            function(response) {}
        );

       e.preventDefault();
    });
});