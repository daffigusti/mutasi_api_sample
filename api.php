<?php 

require_once("Helper.php");

// Get Account information
$result = Helper::GetAccount();
$data = json_decode($result);
foreach($data->data as $row){
    echo $row->id ."<br>";
    echo $row->bank ."<br>";
    echo $row->module ."<br>";
    echo $row->account_no ."<br>";
    echo $row->account_name ."<br>";
    echo $row->balance ."<br>";
    echo "--------------------<br>";
}

$date_from = '2018-11-15';
$date_to = '2018-11-19';
$acc_id = 1;

/// Get Account Statement
$result = Helper::GetAccountStatement($acc_id,$date_from,$date_to);

$data = json_decode($result);
if($data->error == false){
    echo "Balance : ".$data->balance;

    foreach($data->data as $statement){
        echo $statement->transaction_date ."<br>";
        echo $statement->description ."<br>";
        echo $statement->amount ."<br>";
        echo $statement->type ."<br>";
        echo $statement->balance ."<br>";
        echo "--------------------<br>";
    }
}