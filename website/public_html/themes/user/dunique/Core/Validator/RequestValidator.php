<?php

namespace  Dunique\AntwanVanBoheemen\Core\Validator;

use Dunique\AntwanVanBoheemen\Core\Http\Request;

class RequestValidator
{
    // @todo make a setting
    private static $blacklist = ['127.0.0.1'];
    
    // @todo make a setting for the rate-limiting parameters
    private static $rateLimitKey = '/dunique/rate_limit/';
    private static $maxAttempts = 300; // Max allowed attempts
    private static $timeWindow = 300; // Time window in seconds

    /**
     * Detects malicious activity based on the request's headers and URI.
     * Checks for suspicious patterns in User-Agent, Referrer, Accept-Language, and query strings.
     *
     * @param Request $request The incoming request to analyze.
     * @return array An array of errors or an empty array if no issues are found.
     */
    public static function validate(Request $request)
    {
        $errors = [];  // Initialize an array to collect errors.
        $logData = [];

        // Run honeypot check
        $honeypotErrors = self::honeypot($request);
        if (!empty($honeypotErrors)) {
            $errors = $honeypotErrors;
        }

        // Origin check
        if (!self::origin($request)) {
            $errors['invalid_origin'] = 'Invalid origin or referer detected.';
        }


        $userAgent = $request->header('User-Agent');
        $referrer = $request->header('Referer');
        $acceptLanguage = $request->header('Accept-Language');
        $accept = $request->header('Accept');
        $ip = $request->ip();

        // Check for common bot patterns in user-agent
        $botPatterns = [
            'curl', 'wget', 'bot', 'crawler', 'spider', 'fetcher', 'java', 'libwww-perl',
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $errors['suspicious_user_agent'] = 'Malicious bot pattern detected in User-Agent.';
            }
        }

        // Check for missing or suspicious headers
        if (empty($acceptLanguage) || empty($accept)) {
            $errors['suspicious_headers'] = 'Missing or suspicious Accept-Language or Accept headers.';
        }

        // Check for suspicious or malformed referrers
        if (empty($referrer) || !filter_var($referrer, FILTER_VALIDATE_URL)) {
            $errors['suspicious_referrer'] = 'Suspicious or malformed Referrer.';
        }

        // Check for common attack patterns in the URL or query string
        $uri = strtolower($request->uri());
        $query =  strtolower($request->queryString());

        $suspiciousPaths = ['/admin', '/login', '/wp-admin', '/xmlrpc.php'];
        $suspiciousParams = ['unionselect', 'select%20from', 'or%201=1', 'drop%20table'];

        foreach ($suspiciousPaths as $path) {
            if (strpos($uri, $path) !== false) {
                $errors['suspicious_uri'] = 'Suspicious path in the URI.';
            }
        }

        foreach ($suspiciousParams as $param) {
            if (strpos($query, $param) !== false) {
                $errors['suspicious_parameter'] = 'Suspicious parameter in the query string.';
            }
        }

        // Check for SQL injection or XSS patterns
        $maliciousPatterns = [
            "' OR '1'='1", '" OR "1"="1', '"><script>', '<script>alert(', 'eval(', 'union%20select',
            '%27%20OR%20%271%27%3D%271', 'script%3Ealert', 'javascript%3Aalert',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (strpos($uri, $pattern) !== false || strpos($query, $pattern) !== false) {
                $errors['malicious_pattern'] = 'SQL injection or XSS pattern detected in the URI or query string.';
            }
        }
        $data = (array) $request->data();
        // Iterate over the decoded data (array or nested array) and check for malicious patterns
        array_walk_recursive($data, function($value, $key) use ($maliciousPatterns, &$errors, &$logData) {
            // Build the full field name
            $currentKey = $key;  // You can modify this if the data has nested arrays

            // Check for malicious patterns
            foreach ($maliciousPatterns as $pattern) {
                if (strpos($value, $pattern) !== false) {
                    // If pattern is found, record the error with the full field name
                    $errors['malicious_pattern_in_body'] = "Malicious pattern detected in field '$currentKey'.";
                    $logData['malicious_pattern_in_body'] = $value;
                }
            }
        });

        
        // Check CSRF protection
        if (!self::CSRF($request->csrf())) {
            $errors['csrf_protection'] = 'Your CSRF token is not valid.';
        }

        // Check if the IP is blacklisted
        if (self::isBlacklisted($ip)) {
            $errors['blacklisted'] = 'Your IP address is blacklisted.';
        }

        // Check if the request is rate-limited
        if (self::isRateLimited($request)) {
            $errors['rate_limit'] = 'Rate limit exceeded.';
        }

        if (!empty($errors)) {
            $log = ' <strong>['.$ip.'] Error in validating request:</strong><br>';
            foreach ($errors as $error => $message) {
                $log.= ' - '. $error.(isset($logData[$error]) ? ' <em>' .  htmlentities($logData[$error]). '</em>' : ''). '<br>';
            }
            $log = rtrim($log, ', ');
            ee()->load->library('logger');
            ee()->logger->developer($log);
        }


        // Return the array of errors (could be empty if no issues found)

        return $errors;  // Return the array of errors (could be empty if no issues found)
    }


    /**
     * Checks if the provided CSRF token matches the server's CSRF token.
     *
     * @param string $csrfToken The CSRF token from the request.
     * @return bool True if the token matches, otherwise false.
     */
    public static function CSRF($csrfToken)
    {
        return CSRF_TOKEN === $csrfToken;
    }

    
    /**
     * Checks if the given IP is in the blacklist.
     *
     * @param string $ip The IP address to check.
     * @return bool True if the IP is blacklisted, otherwise false.
     */
    public static function isBlacklisted($ip)
    {
        // @TODO: Manageble blacklist. maybe add a repeating offender automatically
        // self::$blacklist = array_merge(self::$blacklist, ['172.26.0.1']);
        return in_array($ip, self::$blacklist, true);
    }

    /**
     * Determines if a user has exceeded the rate limit based on their IP address.
     * Uses ExpressionEngine's Cache to track attempts and timestamps.
     *
     * @param Request $request The incoming request to check.
     * @return bool True if the IP is rate-limited, otherwise false.
     */
    public static function isRateLimited(Request $request)
    {
        $ip = $request->ip();
        $cacheKey = self::$rateLimitKey . md5($ip); // Use md5 to avoid long cache keys
        
        // Get the rate limit data from cache
        $rateLimitData = ee()->cache->get($cacheKey);
        
        // If no data exists, initialize it
        if (!$rateLimitData) {
            $rateLimitData = [
                'attempts' => 0,   // Number of requests in the current time window
                'last_request' => time() // Timestamp of the last request
            ];
        }

        // Check if we're within the allowed time window
        if (time() - $rateLimitData['last_request'] <= self::$timeWindow) {
            // If we're within the time window, check the number of attempts
            if ($rateLimitData['attempts'] >= self::$maxAttempts) {
                // If attempts exceed the limit, deny the request
                return true;
            }
        } else {
            // If we're outside the time window, reset the count
            $rateLimitData['attempts'] = 0;
        }

        // Increment the attempts count
        $rateLimitData['attempts']++;
        // Update the last request time
        $rateLimitData['last_request'] = time();

        // Save the updated rate-limiting data back to the cache with a TTL (time-to-live)
        ee()->cache->save($cacheKey, $rateLimitData, self::$timeWindow);

        return false;
    }

    public static function honeypot(Request $request)
    {
        $errors = [];
        
        // Get all POST data
        $postData = $request->data();
    
        // Check for fields that start with 'malicious_'
        foreach ($postData as $key => $value) {
            if (str_starts_with($key, '_malicious_') === true && !empty($value)) {
                // If a malicious field is filled out, it's a bot submission
                $errors['honeypot'] = 'Seduced by the honeypot.';
            }
        }
    
        // Return any errors found
        return $errors;
    }

    public static function origin(Request $request)
    {
        // Define the allowed origins
        $baseUrl = rtrim(ee()->config->item('base_url'), '/');
        // Ensure the allowed origins list includes the full base URL (including protocol and port if applicable)
        $allowedOrigins = [
            $baseUrl
        ];

        $origin = rtrim($request->header('Origin'), '/');
        $referer = rtrim($request->header('Referer'), '/');

        // Check the Origin header first
        if ($origin && !in_array($origin, $allowedOrigins, true)) {
            return false;
        }

        // Check the Referer header if Origin is not present
        if (!$origin && $referer) {
            $parsedReferer = parse_url($referer);
            if (isset($parsedReferer['host'])) {
                // Rebuild the Referer URL including the protocol and port (if any)
                $parsedRefererUrl = $parsedReferer['scheme'] . '://' . $parsedReferer['host'];
                if (isset($parsedReferer['port'])) {
                    $parsedRefererUrl .= ':' . $parsedReferer['port'];
                }
                // Compare the full Referer URL (protocol + host + port)
                if (!in_array($parsedRefererUrl, $allowedOrigins, true)) {
                    return false;
                }
            }
        }

        // If neither Origin nor Referer match, consider it invalid
        return true;
    }


    
}
