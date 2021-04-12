<?php

//Contoh Data Callback
//{
//"api_key": "xxxx",
//"account_id": 10,
//"module": "bca",
//"account_name": "Budi santoso",
//"account_number": "12341123455",
//"balance": 12720562,
//"data_mutasi": [
//    {
//        "transaction_date": "2018-12-27 05:03:02",
//        "description": "PRMA CR Transfer 1270007989179 5047101250062758",
//        "type": "CR",
//        "amount": 1000261,
//        "balance": 0
//        }
//    ]
//}

$data = json_decode(file_get_contents('php://input'), true);

//TOKEN ANDA YANG ANDA DAPATKAN DI MUTASIBANK.CO.ID
$api_token = "TOKEN_ANDA";

$token = $data['api_key'];
if ($api_token != strval($token)) {
    echo "invalid api token";
    exit;
}
        
//MODULE BANK (bca,bri,bni,mandiri)
$module = $data['module'];

//DATA MUTASI
foreach ($data['data_mutasi'] as $dtm) {
    //Tanggal Transaksi terjadi di bank
    $date = $dtm['transaction_date'];
    
    //Note atau deskripsi dari bank
    $note = $dtm['description'];
    
    //Tipe transaksi (DB ATAU CR)
    $type = $dtm['type'];
    
    //Jumlah Dana
    $amount = $dtm['amount'];
    
    //Saldo saat ini
    $saldo = $dtm['balance'];
    
    //ID Transaksi Mutasi 
    $id = $dtm['id'];
    
    //Module Bank 
    $module = $data['module'];
    
    $headers = [
        "Authorization: $api_token",
        'Content-Type: application/json'
    ];
    //validate transaction = 
    $result_v = http_get("https://mutasibank.co.id/api/v1/validate/$id", $headers);
    $data_r = json_decode($result_v);
    
    if ($data_r->valid && $data_r->data->amount == $amount) {
        //transaksi valid
        //proses untuk selanjutnya
        //Logic validasi transaksi selanjutnya ada disini
        
    }else {
        echo "Tansaksi $id not valid ";
    }
}

public static function http_get($url, $headers = array())
{

    // is cURL installed yet?
    if (!function_exists('curl_init')) {
        die('Sorry cURL is not installed!');
    }

    // OK cool - then let's create a new cURL resource handle
    $ch = curl_init();

    // Now set some options (most are optional)

    // Set URL to download
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // Set a referer
    curl_setopt($ch, CURLOPT_REFERER, $url);

    // User agent
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0");

    // Include header in result? (0 = yes, 1 = no)
    curl_setopt($ch, CURLOPT_HEADER, 0);

    // Should cURL return or print out the data? (true = return, false = print)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Timeout in seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 240);

    // Download the given URL, and return output
    $output = curl_exec($ch);

    // Close the cURL resource, and free system resources
    curl_close($ch);

    return $output;
}
?>
