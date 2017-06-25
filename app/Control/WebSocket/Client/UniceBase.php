<?php
/**
 * Created by PhpStorm.
 * User: btica
 * Date: 09/06/2017
 * Time: 11:33
 */

namespace App\Control\WebSocket\Client;


use App\Control\Unice\SDK\Message\Message;
use App\Control\Unice\SDK\Message\MessageAbstract;
use App\Control\Unice\SDK\Message\Payload\Payload;
use App\Control\Unice\SDK\Unice\Unice;

use Tik\WebSocket\Client\ClientAbstract;

class UniceBase extends ClientAbstract
{

    public function __construct($protocol = null, $host = null, $port = null)
    {
        $protocol = is_null($protocol) ? config('control.uniceCommunication.connection.protocol', 'ws') : $protocol;
        $host = is_null($host) ? config('control.uniceCommunication.connection.host', '127.0.0.1') : $host;
        $port = is_null($port) ? config('control.uniceCommunication.connection.port', '98765') : $port;

        parent::__construct($protocol, $host, $port);
    }

    public function newConnection()
    {
        if ($this->client) {
            $this->client->close();
        }

        $this->client = new  \Hoa\Websocket\Client(
            new \Hoa\Socket\Client($this->getURI())
        );

        $this->client->setHost($this->host);

        $this->client->connect();

        return $this;
    }

    public function send(MessageAbstract $message)
    {
        $this->newConnection();
        $unice = Unice::getByUid('base_client_12349876');

        $authMessage = new Message([
            'receiver' => $message->receiver,
            'code' => Message::UID_CHECK,
            'sender' => $unice->getUid(), //todo should do this dynamic.
            'payload' => new Payload($unice)
        ]);

        //send a message in order to auth and join channel.
        $this->client->send((string)$authMessage);

        //if we are kicked out, return false.
        //todo update this in order to be valid.
        if (!$this->client) {
            return false;
        }

        //let's send message to channel
        $this->client->send((string)$message);

        $this->client->close();
        return true;
    }

    public static function sendNow(MessageAbstract $message)
    {
        return (new static())->send($message);
    }

}