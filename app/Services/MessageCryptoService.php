<?php

/** Authenticated encryption for message bodies stored in the database. */
final class MessageCryptoService
{
    private string $key;

    public function __construct(?string $encodedKey = null)
    {
        if (!extension_loaded('sodium')) {
            throw new RuntimeException('Ekstensi sodium wajib tersedia untuk pesan aman');
        }
        $encodedKey ??= (string)getenv('APP_MESSAGE_KEY');
        $decoded = $encodedKey !== '' ? base64_decode($encodedKey, true) : false;
        if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            // Development-compatible key. Production deployments must provide
            // a separate 32-byte base64 APP_MESSAGE_KEY outside the repository.
            $config = require __DIR__ . '/../../config/database.php';
            $seed = implode('|', [$config['dbname'] ?? '', $config['username'] ?? '', $config['password'] ?? '']);
            $decoded = sodium_crypto_generichash('sesendok-message-v1|' . $seed, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        }
        $this->key = $decoded;
    }

    public function encrypt(string $plainText): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return [
            'ciphertext' => base64_encode(sodium_crypto_secretbox($plainText, $nonce, $this->key)),
            'nonce' => base64_encode($nonce),
        ];
    }

    public function decrypt(?string $ciphertext, ?string $nonce, string $legacy = ''): string
    {
        if (!$ciphertext || !$nonce) return $legacy;
        $cipher = base64_decode($ciphertext, true);
        $nonceBytes = base64_decode($nonce, true);
        if ($cipher === false || $nonceBytes === false) return '[Pesan tidak dapat dibuka]';
        $plain = sodium_crypto_secretbox_open($cipher, $nonceBytes, $this->key);
        return $plain === false ? '[Pesan tidak dapat dibuka]' : $plain;
    }
}
