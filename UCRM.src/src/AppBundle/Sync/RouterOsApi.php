<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Sync\Exceptions\ConnectTimeoutException;
use AppBundle\Sync\Exceptions\LoginException;
use AppBundle\Sync\Exceptions\RemoteCommandException;

/*****************************
 *
 * RouterOS PHP API class v1.6
 * Author: Denis Basta
 * Contributors:
 *    Nick Barnes
 *    Ben Menking (ben [at] infotechsc [dot] com)
 *    Jeremy Jefferson (http://jeremyj.com)
 *    Cristian Deluxe (djcristiandeluxe [at] gmail [dot] com)
 *    Mikhail Moskalev (mmv.rus [at] gmail [dot] com)
 *
 * http://www.mikrotik.com
 * http://wiki.mikrotik.com/wiki/API_PHP_class
 *
 ******************************/

class RouterOsApi
{
    public $debug = false; //  Show debug information
    public $connected = false; //  Connection state
    public $port = 8728;  //  Port to connect to
    public $timeout = 3;     //  Connection attempt timeout and data read timeout
    public $attempts = 5;     //  Connection attempt count
    public $delay = 3;     //  Delay between connection attempts in seconds

    public $socket;            //  Variable for storing socket resource
    public $error_no;          //  Variable for storing connection error number, if any
    public $error_str;         //  Variable for storing connection error text, if any

    /**
     * Print text for debug purposes.
     *
     * @param string $text Text to print
     */
    public function debug($text)
    {
        if ($this->debug) {
            echo $text . "\n";
        }
    }

    /**
     * @param string $length
     *
     * @return int
     */
    public function encodeLength($length)
    {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        return $length;
    }

    /**
     * Login to RouterOS.
     *
     * @param string $ip       Hostname (IP or domain) of the RouterOS server
     * @param string $login    The RouterOS username
     * @param string $password The RouterOS password
     *
     * @return bool If we are connected or not
     *
     * @throws LoginException
     */
    public function connect($ip, $login, $password)
    {
        for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; ++$ATTEMPT) {
            $this->connected = false;
            $this->debug('Connection attempt #' . $ATTEMPT . ' to ' . $ip . ':' . $this->port . '...');
            $this->socket = @fsockopen($ip, $this->port, $this->error_no, $this->error_str, $this->timeout);
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->write('/login');
                $RESPONSE = $this->read(false);
                if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                    $MATCHES = [];
                    if (preg_match_all('/[^=]+/i', $RESPONSE[1], $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret' && strlen($MATCHES[0][1]) == 32) {
                            $this->write('/login', false);
                            $this->write('=name=' . $login, false);
                            $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $MATCHES[0][1])));
                            $RESPONSE = $this->read(false);
                            if ($RESPONSE[0] == '!done') {
                                $this->connected = true;
                                break;
                            }
                            if ($RESPONSE[0] == '!trap') {
                                throw new LoginException();
                            }
                        }
                    }
                }
                fclose($this->socket);
            }
            sleep($this->delay);
        }

        if ($this->connected) {
            $this->debug('Connected...');
        } else {
            $this->debug('Error...');
            throw new LoginException();
        }

        return $this->connected;
    }

    /**
     * Disconnect from RouterOS.
     */
    public function disconnect()
    {
        // let's make sure this socket is still valid.  it may have been closed by something else
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->connected = false;
        $this->debug('Disconnected...');
    }

    /**
     * Parse response from Router OS.
     *
     * @param array $response Response data
     *
     * @return array Array with parsed data
     */
    public function parseResponse($response)
    {
        if (is_array($response)) {
            $PARSED = [];
            $CURRENT = null;
            $singlevalue = null;
            foreach ($response as $x) {
                if (in_array($x, ['!fatal', '!re', '!trap'])) {
                    if ($x == '!re') {
                        $CURRENT = &$PARSED[];
                    } else {
                        $CURRENT = &$PARSED[$x][];
                    }
                } elseif ($x != '!done') {
                    $MATCHES = [];
                    if (preg_match_all('/[^=]+/i', $x, $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret') {
                            $singlevalue = $MATCHES[0][1];
                        }
                        $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                    }
                }
            }

            if (empty($PARSED) && null !== $singlevalue) {
                $PARSED = $singlevalue;
            }

            return $PARSED;
        }

        return [];
    }

    /**
     * Parse response from Router OS.
     *
     * @param array $response Response data
     *
     * @return array Array with parsed data
     */
    public function parseResponse4Smarty($response)
    {
        if (is_array($response)) {
            $PARSED = [];
            $CURRENT = null;
            $singlevalue = null;
            foreach ($response as $x) {
                if (in_array($x, ['!fatal', '!re', '!trap'])) {
                    if ($x == '!re') {
                        $CURRENT = &$PARSED[];
                    } else {
                        $CURRENT = &$PARSED[$x][];
                    }
                } elseif ($x != '!done') {
                    $MATCHES = [];
                    if (preg_match_all('/[^=]+/i', $x, $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret') {
                            $singlevalue = $MATCHES[0][1];
                        }
                        $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                    }
                }
            }
            foreach ($PARSED as $key => $value) {
                $PARSED[$key] = $this->arrayChangeKeyName($value);
            }

            return $PARSED;
        }
    }

    /**
     * Change "-" and "/" from array key to "_".
     *
     * @param array $array Input array
     *
     * @return array Array with changed key names
     */
    public function arrayChangeKeyName(&$array)
    {
        if (is_array($array)) {
            $array_new = [];
            foreach ($array as $k => $v) {
                $tmp = str_replace('-', '_', $k);
                $tmp = str_replace('/', '_', $tmp);
                if ($tmp) {
                    $array_new[$tmp] = $v;
                } else {
                    $array_new[$k] = $v;
                }
            }

            return $array_new;
        }

        return $array;
    }

    /**
     * Read data from Router OS.
     *
     * @param bool $parse Parse the data? default: true
     *
     * @return array Array with parsed or unparsed data
     *
     * @throws ConnectTimeoutException
     */
    public function read($parse = true)
    {
        $RESPONSE = [];
        $receiveddone = false;
        while (true) {
            // Read the first byte of input which gives us some or all of the length
            // of the remaining reply.
            $r = fread($this->socket, 1);
            if ($r === false) {
                throw new ConnectTimeoutException();
            }
            $BYTE = ord($r);
            $LENGTH = 0;
            // If the first bit is set then we need to remove the first four bits, shift left 8
            // and then read another byte in.
            // We repeat this for the second and third bits.
            // If the fourth bit is set, we need to remove anything left in the first byte
            // and then read in yet another byte.
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) {
                    $r = fread($this->socket, 1);
                    if ($r === false) {
                        throw new ConnectTimeoutException();
                    }
                    $LENGTH = (($BYTE & 63) << 8) + ord($r);
                } else {
                    if (($BYTE & 224) == 192) {
                        $r = fread($this->socket, 1);
                        if ($r === false) {
                            throw new ConnectTimeoutException();
                        }
                        $LENGTH = (($BYTE & 31) << 8) + ord($r);
                        $r = fread($this->socket, 1);
                        if ($r === false) {
                            throw new ConnectTimeoutException();
                        }
                        $LENGTH = ($LENGTH << 8) + ord($r);
                    } else {
                        if (($BYTE & 240) == 224) {
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = (($BYTE & 15) << 8) + ord($r);
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = ($LENGTH << 8) + ord($r);
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = ($LENGTH << 8) + ord($r);
                        } else {
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = ord($r);
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = ($LENGTH << 8) + ord($r);
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = ($LENGTH << 8) + ord($r);
                            $r = fread($this->socket, 1);
                            if ($r === false) {
                                throw new ConnectTimeoutException();
                            }
                            $LENGTH = ($LENGTH << 8) + ord($r);
                        }
                    }
                }
            } else {
                $LENGTH = $BYTE;
            }

            $_ = '';

            // If we have got more characters to read, read them in.
            if ($LENGTH > 0) {
                $_ = '';
                $retlen = 0;
                while ($retlen < $LENGTH) {
                    $toread = $LENGTH - $retlen;
                    $r = fread($this->socket, $toread);
                    if ($r === false) {
                        throw new ConnectTimeoutException();
                    }

                    $_ .= $r;
                    $retlen = strlen($_);
                }
                $RESPONSE[] = $_;
                $this->debug('>>> [' . $retlen . '/' . $LENGTH . '] bytes read.');
            }

            // If we get a !done, make a note of it.
            if ($_ == '!done') {
                $receiveddone = true;
            }

            $STATUS = socket_get_status($this->socket);
            if ($LENGTH > 0) {
                $this->debug('>>> [' . $LENGTH . ', ' . $STATUS['unread_bytes'] . ']' . $_);
            }

            if ((! $this->connected && ! $STATUS['unread_bytes']) || ($this->connected && ! $STATUS['unread_bytes'] && $receiveddone)) {
                break;
            }
        }

        if ($parse) {
            $RESPONSE = $this->parseResponse($RESPONSE);
        }

        return $RESPONSE;
    }

    /**
     * Write (send) data to Router OS.
     *
     * @param string $command A string with the command to send
     * @param mixed  $param2  If we set an integer, the command will send this data as a "tag"
     *                        If we set it to boolean true, the funcion will send the comand and finish
     *                        If we set it to boolean false, the funcion will send the comand and wait for next command
     *                        Default: true
     *
     * @return bool Return false if no command especified
     *
     * @throws ConnectTimeoutException
     */
    public function write($command, $param2 = true)
    {
        if ($command) {
            $data = explode("\n", $command);
            foreach ($data as $com) {
                $com = trim($com);
                if (fwrite($this->socket, $this->encodeLength(strlen($com)) . $com) === false) {
                    throw new ConnectTimeoutException();
                }
                $this->debug('<<< [' . strlen($com) . '] ' . $com);
            }

            if (gettype($param2) == 'integer') {
                if (fwrite($this->socket, $this->encodeLength(strlen('.tag=' . $param2)) . '.tag=' . $param2 . chr(0)) === false) {
                    throw new ConnectTimeoutException();
                }
                $this->debug('<<< [' . strlen('.tag=' . $param2) . '] .tag=' . $param2);
            } elseif (gettype($param2) == 'boolean') {
                if (fwrite($this->socket, ($param2 ? chr(0) : '')) === false) {
                    throw new ConnectTimeoutException();
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Write (send) data to Router OS.
     *
     * @param string $com A string with the command to send
     * @param array  $arr An array with arguments or queries
     *
     * @return array Array with parsed
     *
     * @throws RemoteCommandException
     */
    public function comm($com, $arr = [])
    {
        $count = count($arr);
        $this->write($com, ! $arr);
        $i = 0;
        if (is_iterable($arr)) {
            foreach ($arr as $k => $v) {
                switch ($k[0]) {
                    case '?':
                        $el = "$k=$v";
                        break;
                    case '~':
                        $el = "$k~$v";
                        break;
                    default:
                        $el = "=$k=$v";
                        break;
                }

                $last = ($i++ == $count - 1);
                $this->write($el, $last);
            }
        }

        $res = $this->read();
        if ($res != null && isset($res['!trap'])) {
            /** @var array $arr */
            throw new RemoteCommandException($com, $arr, $res['!trap'][0]['message']);
        }

        return $res;
    }

    /**
     * Standard destructor.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
