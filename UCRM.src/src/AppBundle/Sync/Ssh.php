<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync;

use AppBundle\Sync\Exceptions\ConnectTimeoutException;
use AppBundle\Sync\Exceptions\LoginException;

class Ssh
{
    public const MAX_PACKET_LENGTH = 32768;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var resource
     */
    private $conn;

    /**
     * @var array
     */
    private $methods = [
        'kex' => 'diffie-hellman-group14-sha1,diffie-hellman-group-exchange-sha1,diffie-hellman-group1-sha1',
        'client_to_server' => [
            'crypt' => 'aes256-ctr,aes192-ctr,rijndael-cbc@lysator.liu.se,aes256-cbc,aes192-cbc,aes128-cbc,3des-cbc',
            'comp' => 'none',
            'mac' => 'hmac-sha1,hmac-sha1-96,hmac-ripemd160,hmac-ripemd160@openssh.com',
        ],
        'server_to_client' => [
            'crypt' => 'aes256-ctr,aes192-ctr,rijndael-cbc@lysator.liu.se,aes256-cbc,aes192-cbc,aes128-cbc,3des-cbc',
            'comp' => 'none',
            'mac' => 'hmac-sha1,hmac-sha1-96,hmac-ripemd160,hmac-ripemd160@openssh.com',
        ],
    ];

    public function setIp(string $ip)
    {
        $this->ip = $ip;
    }

    public function setPort(int $port)
    {
        $this->port = $port;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @throws LoginException
     */
    public function login(): void
    {
        // @ is intentional, lest we get ContextException - we want to handle the error ourselves.
        if (! ($this->conn = @ssh2_connect($this->ip, $this->port, $this->methods))) {
            throw new LoginException(sprintf('SSH connection to %s:%d failed.', $this->ip, $this->port));
        }

        // try to authenticate with username and password
        // @ is intentional, lest we get ContextException
        if (! @ssh2_auth_password($this->conn, $this->username, $this->password)) {
            throw new LoginException(
                sprintf('SSH authentication as %s@%s:%d failed.', $this->username, $this->ip, $this->port)
            );
        }
    }

    /**
     * @return bool|string
     *
     * @throws ConnectTimeoutException
     * @throws LoginException
     */
    public function execute(string $command, bool $setBlocking = true)
    {
        $this->login();

        // execute the command
        // @ is intentional, lest we get ContextException - we want to handle the error ourselves.
        $stream = @ssh2_exec($this->conn, $command);
        if (! $stream) {
            return false;
        }

        // collect returning data from command
        stream_set_blocking($stream, $setBlocking);
        $data = '';

        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }

        fclose($stream);

        return $data;
    }

    /**
     * @throws \Exception
     */
    public function downloadFile(string $filename): string
    {
        $path = sprintf(
            'ssh2.sftp://%s:%s@%s:%d/%s',
            $this->username,
            $this->password,
            $this->ip,
            $this->port > 0 ? $this->port : 22,
            $filename
        );

        try {
            $data = file_get_contents($path);
        } catch (\Exception $e) {
            throw new \Exception('File can\'t be loaded from server');
        }

        return $data;
    }

    /**
     * @throws LoginException
     */
    public function uploadFile(string $source, string $destination): bool
    {
        $this->login();

        return ssh2_scp_send($this->conn, $source, $destination);
    }
}
