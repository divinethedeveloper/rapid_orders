<?php
$token    = 'EAAhSnd3h03kBRVAHbjcGSAfKWuZBI9brCCDb8HezkYsDjtD0D0QcrEOO1XSoOd7aYIqr7ZArjzG0lU6Ctz2rZCpJh4PPZAkRdO291BRYBSmXLAHSZAzb6IgLeDS4nddHSZC8dDU5lQjqcYXx4QPVlpAvi4o3BHZAM5vTVpuixwQZBpXbU58sf1zSZAouDxJpfrGbCwB859rBKcEgprcJJ36v2gYiknPyGLKAYT1WpzWZAuHZBK5g0p8twLfE6GmCJ6vMcgYX6g9XlOfoVtahxTwGmHHCQZDZD';        // your access token
$phoneId  = '1096392023557388';    // your Phone Number ID
$myNumber = '233508482800';      // YOUR WhatsApp number to receive the test

$url     = "https://graph.facebook.com/v19.0/{$phoneId}/messages";
$payload = json_encode([
    'messaging_product' => 'whatsapp',
    'to'                => $myNumber,
    'type'              => 'text',
    'text'              => ['body' => 'Hello from Rapid Orders! The API is working.']
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        "Authorization: Bearer {$token}"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n";