<?php

const TOKEN = '180c2a76eb4ca667de86a2a15a3c5e6ad85047f21de96b61fd';
const SECRET = '775e6584f9feedd2f18a';

function api_call($action, array $params, $show_header = false)
{
    $params['Token'] = TOKEN;
    $params['ApiSignature'] = getSignature($action, $params, SECRET);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, getUrl($action));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    // curl_setopt($curl, CURLOPT_VERBOSE, 1);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    $response = curl_exec($curl);

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($curl);

    return $body;
}

function getSignature($action, array $parameters, $appSecret)
{
    $parameters = sortSignatureParams($parameters);

    $queryString = http_build_query($parameters, PHP_QUERY_RFC3986);
    $signatureBase = strtoupper('POST') .
        '&' . rawurlencode(getUrl($action)) . '&' . rawurlencode($queryString);

    return base64_encode(hash_hmac('sha1', $signatureBase, $appSecret, true));
}

function getUrl($action)
{
    return 'https://api.tme.eu/' . $action . '.json';
}

function sortSignatureParams(array $params)
{
    ksort($params);

    foreach ($params as &$value) {
        if (is_array($value)) {
            $value = sortSignatureParams($value);
        }
    }

    return $params;
}