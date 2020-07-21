<?php

include(__DIR__ . '/config.php');

$keys = false;
if (file_exists(__DIR__ . '/auth.json')) {
    $keys = json_decode(file_get_contents(__DIR__ . '/auth.json'));
}

$generate_token = true;
if ($keys) {
    // validate the token

    $ch = curl_init('https://id.twitch.tv/oauth2/validate');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: OAuth ' . $keys->access_token
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $r = curl_exec($ch);
    $i = curl_getinfo($ch);
    curl_close($ch);

    if ($i['http_code'] == 200) {
        // the token appears good
        $generate_token = false;

        // optional to check the expires
        $data = json_decode($r);
        if (json_last_error() == JSON_ERROR_NONE) {
            if ($data->expires_in < 3600) {
                // less than an hour left
                // make a new token
                echo 'Token close to expire. Regenerate';
                $generate_token = true;
            }
        } else {
            echo 'Failed to parse JSON. Assume dead token';
            $generate_token = true;
        }
    }
}

if ($generate_token) {
    $ch = curl_init('https://id.twitch.tv/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'grant_type' => "client_credentials"
    ));

    $r = curl_exec($ch);
    $i = curl_getinfo($ch);
    curl_close($ch);

    if ($i['http_code'] == 200) {
        $data = json_decode($r);
        if (json_last_error() == JSON_ERROR_NONE) {
            echo 'Got token';
            print_r($data);

            // store the token for next run
            file_put_contents(__DIR__ . '/auth.json', $r, JSON_PRETTY_PRINT);
        } else {
            echo 'Failed to parse JSON';
        }
    } else {
        echo 'Failed with ' . $i['http_code'] . ' ' . $r;
    }
} else {
    echo 'Token OK';
    print_r($keys);
}
