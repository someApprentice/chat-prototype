function Crypter() {
  this.openpgp  = window.openpgp;
  this.openpgp.initWorker({ path:'js/openpgp.worker.min.js' });

  this.privateKey;
  this.publicKey;

  this.passphrase;
}

Crypter.prototype.addPrivateKey = function(privateKey) {
  this.privateKey = openpgp.key.readArmored(privateKey).keys[0];

  var success = this.privateKey.decrypt(this.passphrase);

  if (success) {
    return true;
  }

  return false;
};

Crypter.prototype.decrypt = function(encrypted) {
  var options = {
    message: openpgp.message.readArmored(encrypted),
    privateKey: this.privateKey
  };

  var decrypted = openpgp.decrypt(options).then(function(decrypted) {
    return decrypted;
  });

  return decrypted;
};

Crypter.prototype.encrypt = function(publicKeys, message) {
  var p = [];

  for (var i in publicKeys) {
    p.push(openpgp.key.readArmored(publicKeys[i]).keys[0]);
  }

  options = {
      data: message,
      publicKeys: p
  };

  var encrypted = openpgp.encrypt(options).then(function(encrypted) {
      return encrypted;
  });

  return encrypted;
}