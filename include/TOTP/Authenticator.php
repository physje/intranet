<?php

# https://github.com/hosseinhezami/totp-authenticator

/**
 * Two-Factor Authentication implementation for Time-based One-Time Passwords (TOTP)
 * Provides methods for secret generation, code validation, and QR code generation
 */
class Authenticator
{
    private const CODE_LENGTH = 6;
    private const SECRET_MIN_LENGTH = 16;
    private const SECRET_MAX_LENGTH = 128;
    private const TIME_SLICE = 30;

    /**
     * Creates a new cryptographically secure secret key
     *
     * @param int $secretLength Length of the secret (16-128 characters)
     * @return string Base32 encoded secret
     * @throws \Exception If no secure random source is available or invalid length
     */
    public static function createSecret(int $secretLength = 16): string
    {
        if ($secretLength < self::SECRET_MIN_LENGTH || $secretLength > self::SECRET_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Secret length must be between %d and %d characters', self::SECRET_MIN_LENGTH, self::SECRET_MAX_LENGTH)
            );
        }

        $randomBytes = self::generateRandomBytes($secretLength);
        $validChars = self::getBase32Characters();

        $secret = '';
        for ($i = 0; $i < $secretLength; ++$i) {
            $secret .= $validChars[ord($randomBytes[$i]) & 31];
        }

        return $secret;
    }

    /**
     * Generates a time-based one-time password
     *
     * @param string $secret Base32 encoded secret key
     * @param int|null $timeSlice Specific time slice (defaults to current time)
     * @return string 6-digit verification code
     */
    public static function generateCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice ??= (int)floor(time() / self::TIME_SLICE);
        $decodedSecret = self::base32Decode($secret);

        $binaryTime = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        $hmac = hash_hmac('SHA1', $binaryTime, $decodedSecret, true);

        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);

        $value = unpack('N', $hashPart)[1];
        $value = $value & 0x7FFFFFFF;

        $modulo = 10 ** self::CODE_LENGTH;

        return str_pad($value % $modulo, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Generates QR code URL for authenticator apps
     *
     * @param string $accountName User identifier (email/username)
     * @param string $secret Base32 encoded secret key
     * @param string|null $issuer Service name
     * @param array $params Additional parameters (width, height, level)
     * @return string QR code URL
     */
    public static function generateQrCodeUrl(
        string $accountName,
        string $secret,
        ?string $issuer = null,
        array $params = []
    ): string {
        $width = $params['width'] ?? 500;
        $height = $params['height'] ?? 500;
        $errorCorrectionLevel = $params['level'] ?? 'M';

        $validErrorLevels = ['L', 'M', 'Q', 'H'];
        if (!in_array($errorCorrectionLevel, $validErrorLevels, true)) {
            $errorCorrectionLevel = 'M';
        }

        $urlParams = [
            'secret' => $secret,
            'issuer' => $issuer
        ];

        $queryString = http_build_query(array_filter($urlParams));
        $otpUrl = 'otpauth://totp/' . rawurlencode($accountName) . '?' . $queryString;

        return sprintf(
            'https://quickchart.io/qr?text=%s&dark=000&light=fff&ecLevel=%s&format=png&margin=1&size=%d',
            rawurlencode($otpUrl),
            $errorCorrectionLevel,
            $width
        );
    }

    /**
     * Verifies a provided code against the secret
     *
     * @param string $secret Base32 encoded secret key
     * @param string $code 6-digit code to verify
     * @param int $allowedTimeDrift Allowed time drift in 30-second units
     * @param int|null $currentTimeSlice Specific time slice for verification
     * @return bool True if code is valid
     */
    public static function verifyCode(
        string $secret,
        string $code,
        int $allowedTimeDrift = 1,
        ?int $currentTimeSlice = null
    ): bool {
        if (strlen($code) !== self::CODE_LENGTH) {
            return false;
        }

        $currentTimeSlice ??= (int)floor(time() / self::TIME_SLICE);

        for ($i = -$allowedTimeDrift; $i <= $allowedTimeDrift; ++$i) {
            $calculatedCode = self::generateCode($secret, $currentTimeSlice + $i);
            if (self::isTimingSafeEqual($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decodes base32 encoded string to binary
     *
     * @param string $secret Base32 encoded string
     * @return string Binary decoded secret
     */
    private static function base32Decode(string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $base32Chars = self::getBase32Characters();
        $base32Flipped = array_flip($base32Chars);

        // Remove padding and convert to uppercase
        $secret = rtrim(strtoupper($secret), '=');
        $secretChars = str_split($secret);

        $binaryString = '';
        $secretLength = count($secretChars);

        for ($i = 0; $i < $secretLength; $i += 8) {
            $binaryChunk = '';

            for ($j = 0; $j < 8; ++$j) {
                $char = $secretChars[$i + $j] ?? '=';
                if ($char === '=' || !isset($base32Flipped[$char])) {
                    continue;
                }
                $binaryChunk .= str_pad(decbin($base32Flipped[$char]), 5, '0', STR_PAD_LEFT);
            }

            $eightBitChunks = str_split($binaryChunk, 8);
            foreach ($eightBitChunks as $eightBitChunk) {
                if (strlen($eightBitChunk) < 8) {
                    continue;
                }
                $binaryString .= chr(bindec($eightBitChunk));
            }
        }

        return $binaryString;
    }

    /**
     * Returns base32 character set
     *
     * @return array<string> Base32 characters
     */
    private static function getBase32Characters(): array
    {
        return [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
            'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
            '=',  // padding char
        ];
    }

    /**
     * Generates cryptographically secure random bytes
     *
     * @param int $length Number of bytes to generate
     * @return string Random bytes
     * @throws \Exception If no secure random source is available
     */
    private static function generateRandomBytes(int $length): string
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $cryptoStrong = false;
            $randomBytes = openssl_random_pseudo_bytes($length, $cryptoStrong);

            if ($cryptoStrong && $randomBytes !== false) {
                return $randomBytes;
            }
        }

        throw new \Exception('No cryptographically secure random number generator available');
    }

    /**
     * Timing-safe string comparison to prevent timing attacks
     *
     * @param string $knownString Known correct string
     * @param string $userString User-provided string
     * @return bool True if strings are identical
     */
    private static function isTimingSafeEqual(string $knownString, string $userString): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($knownString, $userString);
        }

        $knownLength = strlen($knownString);
        $userLength = strlen($userString);

        if ($userLength !== $knownLength) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $knownLength; ++$i) {
            $result |= (ord($knownString[$i]) ^ ord($userString[$i]));
        }

        return $result === 0;
    }
}
