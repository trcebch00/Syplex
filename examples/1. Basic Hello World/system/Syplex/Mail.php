<?php

namespace Syplex;

class Mail {
  public $subject;
  public $from;
  public $recipients;
  public $transport;
  public $mailer;
  public $message;

  /**
   * @param String $name
   *
   * @return String|Array
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function ParseName(String $name) {
    if (preg_match("/^([^<>]+) <([^<>]+)>$/", $name, $match)) {
      return [$match[2] => $match[1]];
    } else {
      return $name;
    }
  }

  /**
   * @param Array $config
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(Array $config=[]) {
    $this->subject = $config["subject"] ?? NULL;
    $this->body = $config["body"] ?? NULL;
    $this->from = $config["from"] ?? NULL;
    $this->recipients = $config["recipients"] ?? [];
    $this->transport = $config["transport"] ?? NULL;
    $this->mailer = $config["mailer"] ?? NULL;
  }

  /**
   * Establishes a TCP connection to the given SMTP server.
   *
   * @param String $host     The IP address or domain of the SMTP server (e.g. mail.mywebsite.com)
   * @param int    $port     The port to be used for the connection, which defaults to 25 (i.e. no security.)
   * @param String $security The name of the security to use (e.g. SSL or TLS.)
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function connect(String $host, int $port=25, String $security=NULL) {
    $this->transport = new \Swift_SmtpTransport($host, $port, $security);
  }

  /**
   * Passes the given user credentials to the SMTP server and
   * instantiates the \Swift_Mailer object to prepare for
   * sending email messages.
   *
   * @param String $username
   * @param String $password
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function login(String $username, String $password) {
    $this->transport->setUsername($username)->setPassword($password);
    $this->mailer = new \Swift_Mailer($this->transport);
  }

  /**
   * Ensures that an SMTP connection is established. If
   * connection and/or login credentials were not passed
   * to the \Syplex\Mail constructor, the values in the
   * global config file will be used.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function forceReady() {
    if (!$this->transport) {
      $this->connect(
        Config::$current->mail["host"],
        Config::$current->mail["port"],
        Config::$current->mail["security"]
      );
    }

    if (!$this->mailer) {
      $this->login(
        Config::$current->mail["username"],
        Config::$current->mail["password"]
      );
    }
  }

  /**
   * @param String $message     The message body of the email to be sent.
   * @param String $contentType The content type of the message body. Defaults to text/html.
   *
   * @return int The total number of messages successfully delivered.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function send(String $message=NULL, String $contentType="text/html"):int {
    $this->forceReady();

    if (!$message) {
      $message = (new \Swift_Message($this->subject))
        ->setContentType($contentType)
        ->setBody($this->body);
      $message->setFrom(self::ParseName($this->from));
    }

    $totalSent = 0;

    foreach ($this->recipients as $recipient) {
      try {
        $message->setTo(self::ParseName($recipient));
        $totalSent += $this->mailer->send($message);
      } catch (Exception $error) {
        error_log($error);
      }
    }

    return $totalSent;
  }
}