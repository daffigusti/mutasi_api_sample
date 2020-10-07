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
}

?>
