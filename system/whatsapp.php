<?php
function send_whatsapp($to, $message, $token, $phone_id)
{
    $url = "https://graph.facebook.com/v19.0/$phone_id/messages";

    $data = [
        "messaging_product" => "whatsapp",
        "to" => "91".$to,
        "type" => "text",
        "text" => ["body" => $message]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
