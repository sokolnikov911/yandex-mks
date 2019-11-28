<?php

namespace sokolnikov911\MKS;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$token = 'some_token_here';
$apiUrl = 'https://some.api.addr.ru/api/';

$client = new Client($token, $apiUrl);

// Get Contents list
echo $client->getContents();

$urlsArray = [
    'http://testdomain1.com/url',
    'http://testdomain2.com/url',
    'http://testdomain3.com/url',
];


// Send Claim for array of URLs
echo $client->setClaim(57, 'proj345', 3, $urlsArray);


// Get Claim info
echo $client->getClaim(133);