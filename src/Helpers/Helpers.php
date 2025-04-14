<?php

namespace App\Helpers;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Helpers
{
    public function __construct(
        private ParameterBagInterface $parameters,
    ) {}

    public static function getClassName($entity): string
    {
        $entityClassNameParts = explode('\\', $entity);
        $entityName = end($entityClassNameParts);

        return $entityName;
    }

    public static function extractIdFromApiUrl(string $url): ?int
    {
        $parts = explode('/', trim($url, '/'));

        return isset($parts[count($parts) - 1]) ? (int) $parts[count($parts) - 1] : null;
    }

    public static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public static function createUploadedFile(string $path, string $copiedImagePath): UploadedFile
    {
        // $copiedImagePath = $this->parameters->get('kernel.project_dir') . '/public/uploads/user_images/vycab_avatar.png';
        copy($path, $copiedImagePath);

        $defaultFile = new UploadedFile(
            $copiedImagePath,
            basename($copiedImagePath),
            mime_content_type($copiedImagePath),
            null,
            true
        );

        return $defaultFile;
    }

    public static function generateRandomString(int $id)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $year = date('y');
        $month = date('n');
        $day = date('j');
        $hour = date('H');
        $second = date('s');
        $millisecond = round(microtime(true) * 1000);

        $randomCharacter1 = $characters[rand(0, strlen($characters) - 1)];
        $randomCharacter2 = $characters[rand(0, strlen($characters) - 1)];

        $randomString = "D{$year}{$id}{$month}{$randomCharacter2}{$day}{$randomCharacter1}{$hour}{$millisecond}";
        // $randomString = substr("D{$year}{$id}{$month}{$randomCharacter2}{$day}{$randomCharacter1}{$hour}{$second}{$millisecond}", 0, 8);

        return $randomString;
    }

    public static function getFileMimeType($picture)
    {
        $mimeType = $picture->getClientMimeType();
        $parts = explode('/', $mimeType);
        return end($parts);
    }

    public static function randomString(int $id, string $text)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $year = date('y');
        $month = date('n');
        $day = date('j');
        $hour = date('H');
        $second = date('s');
        $millisecond = round(microtime(true) * 1000);

        $milli =  substr("{$millisecond}", 2, 4);

        $randomCharacter1 = $characters[rand(0, strlen($characters) - 1)];
        $randomCharacter2 = $characters[rand(0, strlen($characters) - 1)];

        //$randomString = substr("D{$year}{$month}5{$randomCharacter2}{$day}{$randomCharacter1}{$hour}{$second}{$millisecond}", 0, 8);
        $randomString = "{$text}{$id}{$year}{$randomCharacter2}{$day}{$second}";

        return $randomString;
    }

    public static function randomCode()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $year = date('y');
        $month = date('n');
        $day = date('j');
        $hour = date('H');
        $second = date('s');
        $millisecond = round(microtime(true) * 1000);

        $milli =  substr("{$millisecond}", 2, 4);

        $randomCharacter2 = $characters[rand(0, strlen($characters) - 1)];

        $randomString = "{$year}{$month}{$randomCharacter2}{$day}{$second}";

        return $randomString;
    }

    public static function genererCodeAlphanumerique($longueur)
    {
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $code = '';

        for ($i = 0; $i < $longueur; $i++) {
            $code .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }

        return $code;
    }

    public static function  getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta = $lon1 - $lon2;
        $mile = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $mile = acos($mile);
        $mile = rad2deg($mile);
        $mile = $mile * 60 * 1.1515;
        $foot = $mile * 5280;
        $yarn = $foot / 3;
        $km = $mile * 1.609344;
        $m = $km * 1000;
        return compact($unit);
        // return compact('mile','foot','yarn','km','m'); 
    }

    public static function isPointInsideCircle($centerLat, $centerLong, $radius, $pointLat, $pointLong, $unit = 'km')
    {
        // Calculate the distance between the center and the point
        $distance = self::getDistanceBetweenPoints($centerLat, $centerLong, $pointLat, $pointLong, $unit)[$unit];

        // Check if the distance is within the circle's radius
        return $distance <= $radius;
    }

    // getDistanceBetween center and another (lat,long) inside a circle (center, radius)

    public static function pointInPolygon($point, $polygon)
    {
        $polygon = json_decode($polygon, true);

        if ($polygon[0] != $polygon[count($polygon) - 1]) {
            $polygon[] = $polygon[0];
        }

        $j = count($polygon) - 1;
        $oddNodes = false;

        for ($i = 0; $i < count($polygon); $i++) {
            if (($polygon[$i]['lat'] < $point['lat'] && $polygon[$j]['lat'] >= $point['lat']
                || $polygon[$j]['lat'] < $point['lat'] && $polygon[$i]['lat'] >= $point['lat'])) {
                if ($polygon[$i]['lng'] + ($point['lat'] - $polygon[$i]['lat']) / ($polygon[$j]['lat'] - $polygon[$i]['lat']) * ($polygon[$j]['lng'] - $polygon[$i]['lng']) < $point['lng']) {
                    $oddNodes = !$oddNodes;
                }
            }
            $j = $i;
        }

        return $oddNodes;
    }

    public static function isWithinCircle(
        float $circleCenterLat,
        float $circleCenterLng,
        float $locationLat,
        float $locationLng,
        float $radius,
        string $unit = 'km'
    ): bool {
        // Reuse your existing distance calculation
        // getDistanceBetweenPoints() returns an array like ['km' => <float>, ...]
        $distance = self::getDistanceBetweenPoints(
            $circleCenterLat,
            $circleCenterLng,
            $locationLat,
            $locationLng,
            $unit
        );

        // Compare distance to radius
        return isset($distance[$unit]) && $distance[$unit] <= $radius;
    }

    public static function removeDuplicates(array $inputArray): array
    {
        return array_values(array_unique($inputArray));
    }




    public static function removeLastDashToDot($filename)
    {
        $lastDashPos = strrpos($filename, '-');
        $lastDotPos = strrpos($filename, '.');

        if ($lastDashPos !== false && $lastDotPos !== false && $lastDashPos < $lastDotPos) {
            $filename = substr($filename, 0, $lastDashPos) . substr($filename, $lastDotPos);
        }

        return $filename;
    }

    public static function parseMultipartFormData($request)
    {
        $content = $request->getContent();
        $boundary = substr($content, 0, strpos($content, "\r\n"));
        $parts = array_slice(explode($boundary, $content), 1);
        $data = [];

        foreach ($parts as $part) {
            if (strpos($part, '--' . $boundary . '--') !== false) break;

            $part = ltrim($part, "\r\n");

            $headerBody = explode("\r\n\r\n", $part, 2);
            if (count($headerBody) < 2) {
                continue;
            }

            list($headers, $body) = $headerBody;
            $headers = explode("\r\n", $headers);
            $contentDisposition = '';

            foreach ($headers as $header) {
                if (stripos($header, 'Content-Disposition:') !== false) {
                    $contentDisposition = $header;
                    break;
                }
            }

            if (preg_match('/name="([^"]+)"/i', $contentDisposition, $matches)) {
                $name = $matches[1];
                $keys = explode('[', str_replace(']', '', $name));
                $target = &$data;

                foreach ($keys as $key) {
                    if (!isset($target[$key])) {
                        $target[$key] = [];
                    }
                    $target = &$target[$key];
                }

                if (preg_match('/filename="([^"]+)"/i', $contentDisposition, $matches)) {
                    $filename = $matches[1];
                    $tmpFile = tempnam(sys_get_temp_dir(), 'php');
                    file_put_contents($tmpFile, rtrim($body, "\r\n"));

                    $uploadedFile = new UploadedFile(
                        $tmpFile,
                        $filename,
                        mime_content_type($tmpFile),
                        UPLOAD_ERR_OK,
                        true
                    );
                    $target = $uploadedFile;
                } else {
                    $target = stripslashes(rtrim($body, "\r\n"));
                }
            }
        }

        return $data;
    }
}
