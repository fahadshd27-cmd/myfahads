<?php

namespace App\Services;

class ProvablyFairService
{
    public function generateServerSeedPlain(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashServerSeed(string $serverSeedPlain): string
    {
        return hash('sha256', $serverSeedPlain);
    }

    /**
     * Deterministic roll in [0, 1) based on server/client seeds and nonce.
     */
    public function roll(string $serverSeedPlain, string $clientSeed, int $nonce): float
    {
        return $this->rollWithSalt($serverSeedPlain, $clientSeed, $nonce, '');
    }

    /**
     * Deterministic roll in [0, 1) based on server/client seeds, nonce, and a salt.
     * Useful for deriving multiple independent random values from the same seeds.
     */
    public function rollWithSalt(string $serverSeedPlain, string $clientSeed, int $nonce, string $salt): float
    {
        $message = $clientSeed.':'.$nonce;
        if ($salt !== '') {
            $message .= ':'.$salt;
        }
        $hmac = hash_hmac('sha256', $message, $serverSeedPlain);

        // Use first 13 hex chars (~52 bits) to stay within float precision.
        $slice = substr($hmac, 0, 13);
        $int = hexdec($slice);
        $max = 2 ** (13 * 4);

        return $int / $max;
    }
}
