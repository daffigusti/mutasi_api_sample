<?php 

class Helper
{
    const API_URL = "https://mutasibank.co.id/api/v1";
    const API_KEY = "aFYxbzI4djR5MEo2YVFud3B2NTNrUmRTOGQ1ckU3QUY4c3VidnBCUkpZRjFCSGVZT3Z6MU51YmZhMnAy5b76e115db7cd";

    public static function GetAccount()
    {
        $header = [
            "Authorization: " . self::API_KEY,
        ];

        return self::http_get(self::API_URL . "/accounts", $header);
    }

    public static function GetUser()
    {
        $header = [
            "Authorization: " . self::API_KEY,
        ];

        return self::http_get(self::API_URL . "/user", $header);
    }

    public static function GetAccountStatement($acc_id,$from, $to)
    {
        $header = [
            "Authorization: " . self::API_KEY,
        ];

        $data = array('date_from' => $from, 'date_to' => $to);

        return self::http_post(self::API_URL . "/statements/$acc_id",$data, $header);
    }

    public static function http_post($url, $param = [], $headers = [])
    {
        $response = array();
        //set POST variables
        $fields_string = http_build_query($param);
        //open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0");
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //execute post
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        return $result;
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
}