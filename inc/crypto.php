<?php

require_once "/srv/http/secrets.php";

function dsHash() {
    $os = PHP_OS;

    $info = '';

    $info .= shell_exec("lscpu | grep 'Model name'");
    $info .= shell_exec("ifconfig | grep ether");

    $info = preg_replace('/\s+/', '', $info);

    $deviceHash = hash('sha256', $info);

    return $deviceHash;
}

function requestsPerMinute() {
    // Get the current timestamp
    $current_time = time();
    
    // Initialize session variables if they don't exist
    if (!isset($_SESSION['request_count'])) {
        $_SESSION['request_count'] = 0;         // Total request count for the current minute
        $_SESSION['start_time'] = $current_time; // Start of the current minute
    }
    
    // Calculate the difference in seconds between now and the last recorded start time
    $time_elapsed = $current_time - $_SESSION['start_time'];

    // If more than a minute (60 seconds) has passed, calculate the average and reset
    if ($time_elapsed >= 60) {
        // Calculate the average requests per minute
        $average_requests_per_minute = $_SESSION['request_count'] / ($time_elapsed / 60);
        
        // Reset the counter and time
        $_SESSION['request_count'] = 1; // Start new count with this request
        $_SESSION['start_time'] = $current_time; // Reset the start time to the current time

        return $average_requests_per_minute;
    } else {
        // We're still within the same minute, increment the request count
        $_SESSION['request_count']++;
    }

    return $_SESSION['request_count'] / (($time_elapsed / 60) + 1);
}

function clientHash() {
    // Collecting more information for better uniqueness
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    $acceptEncoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
    $acceptCharset = isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : '';
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';

    // Combine all gathered data with a constant salt for additional uniqueness
    $data = $userAgent . $remoteAddr . $acceptLanguage . $acceptEncoding . $acceptCharset . $accept . SALT;

    // Create a SHA-256 hash and encode it in base64
    return base64_encode(hash("sha256", $data, true));
}

function validatePoW($sol, $difficulty = 15) {
    $day = strval(intval(time() / 86400));
    $client = clientHash();

    $combined = $client . $day . $sol;
    $hash = hash("sha256", $combined, true);

    $binaryHash = '';
    foreach (str_split($hash) as $char) {
        $binaryHash .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }

    $leadingZeros = 0;
    for ($i = 0; $i < strlen($binaryHash); $i++) {
        if ($binaryHash[$i] === '0') {
            $leadingZeros++;
        } else {
            break;
        }
    }

    return $leadingZeros >= $difficulty;
}

function generateTripcode($name) {
    if (!preg_match('/^([^#]+)?(##|#)(.+)$/', $name, $match))
	return $name;

    $name = $match[1];
    $trip = $match[3];

    // convert to SHIT_JIS encoding
    $trip = mb_convert_encoding($trip, 'Shift_JIS', 'UTF-8');

    // generate salt
    $salt = substr($trip . 'H..', 1, 2);
    $salt = preg_replace('/[^.-z]/', '.', $salt);
    $salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

    $trip = '!' . substr(crypt($trip, $salt), -10);

    return $name . $trip;
}

function generateSecureTripcode($name) {
    if (strpos($name, "##") == true) {
        $split_string = explode("##", $name);
        if (count($split_string) == 2) {
            $name = $split_string[0];
            $password = $split_string[1];
            $salt = SALT;
            $name = $name . "!!" . base64_encode(hash("sha256", $password.$salt));
        }
    }
    return $name;
}

function bcrypt($password) {
    $options = [
        "cost" => 10,
    ];
    return password_hash($password,PASSWORD_BCRYPT,$options);
}
?>
