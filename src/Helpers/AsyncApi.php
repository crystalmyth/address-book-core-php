<?php

namespace App\Helpers;

class AsyncApi {
    private static function getApiUrls() {
        $apiUrls = [];
        for ($i=1; $i <= 10; $i++) { 
            array_push($apiUrls, "https://jsonplaceholder.typicode.com/users?page=".$i);
        }
        return $apiUrls;
    }
    public static function getData($apiUrls = []) {
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        if(empty($apiUrls)) {
            $apiUrls = self::getApiUrls();
        }
        // Add each URL to the multi-handle
        foreach ($apiUrls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout
            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = $ch;
        }

        // Execute all requests simultaneously
        do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active) {
                // Wait for activity on any curl connection
                curl_multi_select($multiHandle);
            }
        } while ($active && $status == CURLM_OK);

        // Collect responses
        $responses = array();
        foreach ($curlHandles as $ch) {
            $responses[] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        // Close the multi-handle
        curl_multi_close($multiHandle);

        return $responses;
    }
}