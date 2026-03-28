<?php
// public/admin/includes/rcon.php

class MinecraftRcon
{
    private string $host;
    private int    $port;
    private string $password;
    private int    $timeout;
    private mixed  $socket = null;

    public function __construct(
        string $host     = RCON_HOST,
        int    $port     = RCON_PORT,
        string $password = RCON_PASSWORD,
        int    $timeout  = RCON_TIMEOUT
    ) {
        $this->host     = $host;
        $this->port     = $port;
        $this->password = $password;
        $this->timeout  = $timeout;
    }

    // ------------------------------------------------------------------ //
    //  PUBLIKUS API
    // ------------------------------------------------------------------ //

    /** Parancs futtatása – visszaadja a szerver válaszát, vagy false-t hiba esetén */
    public function command(string $cmd): string|false
    {
        if (!$this->connect()) return false;
        $response = $this->send(2, $cmd);
        $this->disconnect();
        return $response;
    }

    /** Szerver státusz: online/offline, játékosszám, lista */
    public function getStatus(): array
    {
        $raw = $this->command('list');

        if ($raw === false) {
            return ['online' => false, 'player_count' => 0, 'max_players' => 0, 'players' => []];
        }

        // "There are 3 of a max of 20 players online: Notch, Herobrine"
        preg_match('/There are (\d+) of a max(?: of)? (\d+)/i', $raw, $m);
        $players = [];

        if (str_contains($raw, ':')) {
            $names = trim(explode(':', $raw, 2)[1]);
            if ($names !== '') {
                $players = array_map('trim', explode(',', $names));
            }
        }

        return [
            'online'       => true,
            'player_count' => isset($m[1]) ? (int)$m[1] : count($players),
            'max_players'  => isset($m[2]) ? (int)$m[2] : 20,
            'players'      => array_filter($players), // üres stringek kidobva
        ];
    }

    /** Online játékosok tömbje – ['Notch', 'Herobrine'] */
    public function getOnlinePlayers(): array
    {
        return $this->getStatus()['players'];
    }

    // ------------------------------------------------------------------ //
    //  BELSŐ RCON PROTOKOLL
    // ------------------------------------------------------------------ //

    private function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) return false;
        stream_set_timeout($this->socket, $this->timeout);

        // AUTH csomag
        $auth = $this->send(3, $this->password);
        return $auth !== false;
    }

    private function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function send(int $type, string $payload): string|false
    {
        if (!$this->socket) return false;

        $id     = rand(1, 9999);
        $packet = pack('VVV', $id, $type, 0) . $payload . "\x00\x00";
        $length = strlen($packet);

        fwrite($this->socket, pack('V', $length) . $packet);

        // Válasz olvasása
        $lenData = fread($this->socket, 4);
        if (!$lenData || strlen($lenData) < 4) return false;

        $respLen  = unpack('V', $lenData)[1];
        $response = fread($this->socket, $respLen);
        if (!$response) return false;

        $respId   = unpack('V', substr($response, 0, 4))[1];

        // -1 = auth hiba
        if ($respId === -1 || $respId === 0xFFFFFFFF) return false;

        // payload: bájtok 8-tól a végéig, mínusz 2 null terminátor
        return rtrim(substr($response, 8), "\x00");
    }
}

/**
 * Globális helper – egyszer példányosít, utána ugyanazt adja vissza.
 * Használat: $rcon = rcon(); $rcon->command('say Hello');
 */
function rcon(): MinecraftRcon
{
    static $instance = null;
    if ($instance === null) {
        $instance = new MinecraftRcon();
    }
    return $instance;
}