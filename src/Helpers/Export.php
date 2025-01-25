<?php

namespace App\Helpers; 

use InvalidArgumentException;
use SimpleXMLElement;
class Export
{
    public static function export($data, $format = 'json', $filename = null)
    {
        $filename = $filename.'.'.$format;
        switch ($format) {
            case 'json':
                $contentType = 'application/json';
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $exportedData = json_encode($data);
                break;
            case 'csv':
                $contentType = 'text/csv';
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $exportedData = self::arrayToCsv($data);
                break;
            case 'xml':
                $contentType = 'application/xml';
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $exportedData = self::arrayToXml($data);
                break;
            default:
                throw new InvalidArgumentException("Invalid export format: $format");
        }

        return $exportedData;
    }

    
    private static function arrayToCsv(array $data)
    {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'City', 'Street', 'Zipcode', 'Tags']); // Header row
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    private static function arrayToXml(array $data, $rootElement = 'contacts')
    {
        $xml = new SimpleXMLElement("<{$rootElement}/>");
        foreach ($data as $row) {
            $address = $xml->addChild('address');
            $address->addChild('id', $row['id']);
            $address->addChild('name', $row['name']);
            $address->addChild('email', $row['email']);
            $address->addChild('phone', $row['phone']);
            $address->addChild('city', $row['city_name']);
            $address->addChild('street', $row['street']);
            $address->addChild('zipcode', $row['zipcode']);
            $address->addChild('tags', $row['tags']);
        }
        
        $output = fopen('php://output', 'w');
        fwrite($output, $xml->asXML());
        fclose($output);
    }
}