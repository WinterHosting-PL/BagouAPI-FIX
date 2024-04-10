<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Str;

class EncryptionService
{
    // Encrypt string using XChaCha20 encryption
    public function EncryptXChaCha($plainText, $key)
    {
        if (strlen($key) !== 32) {
            throw new Exception('Invalid key length');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plainText, '', $nonce, $key);
        return base64_encode($nonce . $ciphertext);
    }

    // Decrypt string using XChaCha20 encryption
    public function DecryptXChaCha($encodedText, $key)
    {
        $decodedValue = base64_decode($encodedText);
        $decodedKey = base64_decode($key);

        if (strlen($decodedKey) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new Exception('Invalid key length');
        }
        $nonceSize = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $nonce = substr($decodedValue, 0, $nonceSize);
        $ciphertext = substr($decodedValue, $nonceSize);

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, '', $nonce, $decodedKey);

        if ($plaintext === false) {
            throw new Exception('Decryption failed.');
        }

        return $plaintext;
    }
    // Encode password/salt using PBKDF2 password hashing
    public function PBKDF2Encode($salt)
    {
        $iteration = config('services.encryption.PBKDF.iteration', 1000);
        $key = config('services.encryption.PBKDF.shared_key');

        if (!is_numeric($iteration)) {
            throw new Exception("Invalid PBKDF2_ITERATIONS configuration value");
        }
        $saltUncoded = base64_decode($salt);

        $hashedKeyRaw = hash_pbkdf2("sha256", $key, $saltUncoded, $iteration, 32, true);
        $hashedKeyBase64 = base64_encode($hashedKeyRaw);
        return $hashedKeyBase64;
    }

    // Create random salt
    public function CreateSalt()
    {
        return Str::random(16);
    }
}