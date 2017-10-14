<?php
namespace App\Model;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Crypter
{
    const KEY_TYPE = 'RSA';
    const KEY_LENGTH = 4096;
    const SUBKEY_TYPE = 'RSA';
    const SUBKEY_LENGTH = 4096;
    const EXPIRE_DATE = 0;

    public function generateKeys($login, $passphrase)
    {
        //+ check keyring dir

        $keyType = self::KEY_TYPE;
        $keyLength = self::KEY_LENGTH;
        $subKeyType = self::SUBKEY_TYPE;
        $subKeyLength = self::SUBKEY_LENGTH;
        $expireDate = self::EXPIRE_DATE;

        $name = $login;
        $comment = $login;
        $email = $login . '@crypter.mail';

        $process = new Process('gpg --batch --gen-key');
        $process->setInput(<<<EOT
Key-Type: $keyType
Key-Length: $keyLength
Subkey-Type: $subKeyType
Subkey-Length: $subKeyLength
Expire-Date: $expireDate
Name-Real: $name
Name-Comment: $name
Name-Email: $email
Passphrase: $passphrase
%commit
%echo done
EOT
);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        //var_dump($process->getOutput());
    }


    public function getKeys()
    {
        $process = new Process('gpg --list-keys');
        $process->run();

        //var_dump($process->getOutput());
    }

    public function getPrivateKey($id)
    {
        $process = new Process("gpg --armor --export-secret-key {$id}");
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $publicKey = $process->getOutput();

        return $publicKey;
    }

    // public function removePrivateKey($id)
    // {
    //     $process = new Process("gpg --delete-secret-keys {$id}");

    //     $input = new InputStream();

    //     $process->setInput($input);

    //     $process->start();

    //     $input->write('y');
    //     $input->write('y');

    //     $input->close();

    //     $process->stop();

    //     if (!$process->isSuccessful()) {
    //         throw new ProcessFailedException($process);
    //     }
    // }

    public function getPublicKey($id)
    {
        $process = new Process("gpg --armor --export {$id}");
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $publicKey = $process->getOutput();

        return $publicKey;
    }

    // public function removePublicKey($id)
    // {
    //     $process = new Process("gpg --delete-keys {$id}");

    //     $input = new InputStream();
        
    //     $process->setInput($input);

    //     $process->start();

    //     $input->write('y');

    //     $input->close();

    //     $process->stop();

    //     if (!$process->isSuccessful()) {
    //         throw new ProcessFailedException($process);
    //     }
    // }

    public function encrypt($recipients, $message)
    {
        //+ check if key in keyring

        $process = "gpg --batch --armor --encrypt";

        foreach ($recipients as $recipient) {
            $process .= " --recipient {$recipient->getLogin()}";
        }

        $process = new Process($process);
        $process->setInput($message);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $encrypted = $process->getOutput();

        return $encrypted;
    }

    public function decrypt($id, $passphrase, $encrypted)
    {
        // $process = new Process("gpg --batch --decrypt --recipient {$id} --passphrase-fd 0");

        // $input = new InputStream();
        // $input->write($encrypted);

        // $process->setInput($input);

        // $process->start();

        // $input->write($passphrase);
        // $input->close();

        // //$process->stop();

        // if (!$process->isSuccessful()) {
        //     throw new ProcessFailedException($process);
        // }

        // $decrypted = $process->getOutput();

        $descriptorspec = array(
           0 => array("pipe", "r"),
           1 => array("pipe", "w"),
           2 => array("file", "/tmp/error-output.txt", "a"),
           3 => array("pipe", "r")
        );

        $process = proc_open("gpg --batch --decrypt --recipient {$id} --passphrase-fd 3", $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $encrypted);
            fclose($pipes[0]);

            fwrite($pipes[3], $passphrase);
            fclose($pipes[3]);

            $decrypted = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $return_value = proc_close($process);
        }

        return $decrypted;
    }
}