<?php
/**
 * St. Lawrence Junior School - Cryptographic Google OAuth ID Token Verifier
 * 
 * Validates Google Sign-In ID tokens (JWT) using Google OpenID Connect public keys.
 * Enforces RS256 algorithm, signature verification, expiration, audience, issuer, and email verification.
 */

class GoogleAuthHelper {

    const CERTS_URL = 'https://www.googleapis.com/oauth2/v1/certs';
    const CACHE_FILE = __DIR__ . '/../../cache/google_certs.json';
    const CACHE_TTL = 21600; // 6 hours

    /**
     * Verifies a Google ID Token cryptographically and validates all claims.
     *
     * @param string      $token            Raw JWT string
     * @param string|null $expectedAudience Optional Google Client ID
     * @return array Verified payload claims
     * @throws Exception with safe error description
     */
    public static function verifyIdToken(string $token, ?string $expectedAudience = null): array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Malformed JWT: token must have 3 parts.');
        }

        // 1. Decode and Validate Header
        $headerJson = self::base64UrlDecode($parts[0]);
        $header = json_decode($headerJson, true);
        if (!is_array($header)) {
            throw new Exception('Invalid JWT header structure.');
        }

        // Enforce RS256 algorithm strictly
        if (!isset($header['alg']) || $header['alg'] !== 'RS256') {
            throw new Exception('Unsupported or insecure JWT algorithm: only RS256 is permitted.');
        }

        if (empty($header['kid'])) {
            throw new Exception('Missing key ID (kid) in token header.');
        }

        $kid = $header['kid'];

        // 2. Fetch Google Public Signing Certificates
        $certs = self::getGoogleCertificates();
        if (!isset($certs[$kid])) {
            // Force refresh cache once in case of recent Google key rotation
            $certs = self::getGoogleCertificates(true);
            if (!isset($certs[$kid])) {
                throw new Exception('Unknown signing key ID: certificate not recognized by Google.');
            }
        }

        $certPem = $certs[$kid];
        $publicKey = openssl_pkey_get_public($certPem);
        if (!$publicKey) {
            throw new Exception('Failed to load Google public key certificate.');
        }

        // 3. Cryptographically Verify Signature with OpenSSL
        $dataToVerify = $parts[0] . '.' . $parts[1];
        $signature = self::base64UrlDecode($parts[2]);

        $verificationResult = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verificationResult !== 1) {
            throw new Exception('Cryptographic verification failed: Google token signature is invalid.');
        }

        // 4. Decode and Validate Payload Claims
        $payloadJson = self::base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            throw new Exception('Invalid JWT payload structure.');
        }

        // Validate Issuer
        if (!isset($payload['iss']) || ($payload['iss'] !== 'https://accounts.google.com' && $payload['iss'] !== 'accounts.google.com')) {
            throw new Exception('Invalid token issuer.');
        }

        // Validate Audience (if configured)
        if (!empty($expectedAudience)) {
            if (!isset($payload['aud']) || $payload['aud'] !== $expectedAudience) {
                throw new Exception('Token audience mismatch.');
            }
        }

        // Validate Expiration (allow 60s clock skew)
        $now = time();
        if (!isset($payload['exp']) || ($payload['exp'] + 60) < $now) {
            throw new Exception('Google token has expired.');
        }

        // Validate Issued-At (allow 60s clock skew)
        if (isset($payload['iat']) && ($payload['iat'] - 60) > $now) {
            throw new Exception('Token issued in the future.');
        }

        // Validate Subject
        if (empty($payload['sub'])) {
            throw new Exception('Missing user subject identifier in token.');
        }

        // Validate Email and Email Verified
        if (empty($payload['email'])) {
            throw new Exception('Missing email in Google token.');
        }

        $emailVerified = $payload['email_verified'] ?? false;
        if ($emailVerified !== true && $emailVerified !== 'true' && $emailVerified !== 1) {
            throw new Exception('Google email address is not verified.');
        }

        return $payload;
    }

    /**
     * Retrieve Google signing certificates with local caching.
     */
    private static function getGoogleCertificates(bool $forceRefresh = false): array {
        // Check local cache
        if (!$forceRefresh && file_exists(self::CACHE_FILE)) {
            $cacheContent = @file_get_contents(self::CACHE_FILE);
            if ($cacheContent) {
                $cachedData = json_decode($cacheContent, true);
                if (is_array($cachedData) && isset($cachedData['timestamp']) && isset($cachedData['certs'])) {
                    if (time() - $cachedData['timestamp'] < self::CACHE_TTL) {
                        return $cachedData['certs'];
                    }
                }
            }
        }

        // Fetch fresh certificates from Google
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => "User-Agent: StLawrenceApp/1.0\r\n"
            ]
        ]);

        $response = @file_get_contents(self::CERTS_URL, false, $ctx);
        if (!$response) {
            // Fallback to expired cache if network temporarily fails
            if (file_exists(self::CACHE_FILE)) {
                $cached = json_decode(@file_get_contents(self::CACHE_FILE), true);
                if (isset($cached['certs'])) {
                    return $cached['certs'];
                }
            }
            throw new Exception('Unable to connect to Google OAuth certificate authority.');
        }

        $certs = json_decode($response, true);
        if (!is_array($certs) || empty($certs)) {
            throw new Exception('Invalid response received from Google certificate authority.');
        }

        // Save to cache
        @file_put_contents(self::CACHE_FILE, json_encode([
            'timestamp' => time(),
            'certs' => $certs
        ]));

        return $certs;
    }

    /**
     * Decode base64url encoded string.
     */
    private static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string)base64_decode(strtr($data, '-_', '+/'));
    }
}
