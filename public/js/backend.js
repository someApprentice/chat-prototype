function Backend() {
  this.logged;

}

Backend.prototype.register = function(login, name, password, retryPassword) {
  var promise = $.post(
    'api/v1/register.php',
    {
      login: login,
      name: name,
      password: password,
      retryPassword: retryPassword
    }
  );

  return promise;
};

Backend.prototype.login = function(login, password) {
  var promise = $.post(
    'api/v1/login.php',
    {
      login: login,
      password: password
    }
  );

  return promise;
};

Backend.prototype.getLogged = function() {
  var promise = $.get({
    url: 'api/v1/getlogged.php',
    dataType: 'json'
  });

  return promise;
};

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
};

Backend.prototype.getMessages = function(datawith, offset) {
  if (offset === undefined) {
    offset = 1;
  }
  
  var promise = $.get(
    'api/v1/getmessages.php',
    {
      with: datawith,
      offset: offset
    }
  );

  return promise;
};

Backend.prototype.getLastMessages= function(datawith, offset) {
  if (offset === undefined) {
    offset = 1;
  }
  
  var promise = $.get(
    'api/v1/getlastmessages.php',
    {
      with: datawith,
      offset: offset
    }
  );

  return promise;
};

Backend.prototype.getNewMessages = function(datawith, since) {  
  var promise = $.get(
    'api/v1/getnewmessages.php',
    {
      with: datawith,
      since: since
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

Backend.prototype.getPrivateKey = function() {
  var promise = $.get(
    'api/v1/getprivatekey.php'
  );

  return promise;
};

Backend.prototype.getPublicKeys = function(id) {
  var promise = $.get(
    'api/v1/getpublickeys.php',
    {
      id: id
    }
  );

  return promise;
};

Backend.prototype.handleError = function(jqXHR, textStatus) {
  if (textStatus == "timeout") {
    console.log("Timeout");
  } else if (jqXHR.status == 0) {
    console.log("No connection");
  } else if (jqXHR.status == 500) {
    console.log("Server error");
  } else if (textStatus == "parsererror") {
    console.log("JSON decode error");
  }
}