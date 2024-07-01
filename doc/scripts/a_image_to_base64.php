<?php

require '../app/model/services/DataAccess.php';

function ImageToB64Tiles($imagePath, $rows, $cols)
{
    $image = imagecreatefromjpeg($imagePath);
    $width = imagesx($image);
    $height = imagesy($image);
    
    $tileWidth = $width / $cols;
    $tileHeight = $height / $rows;
    $tiles = [];
    $xPos = $tileWidth / 2;
    $yPos = $tileHeight / 2;    

    for ($y = 0; $y < $rows; $y++)
    {
        for ($x = 0; $x < $cols; $x++)
        {
            $tile = imagecreatetruecolor($tileWidth, $tileHeight);
            imagecopyresampled($tile, $image, 0, 0, $x * $tileWidth, $y * $tileHeight, $tileWidth, $tileHeight, $tileWidth, $tileHeight);

            ob_start(); // Start output buffering
            imagejpeg($tile, null, 100); // Capture image data (adjust format if needed)
            $imageData = ob_get_contents();
            ob_end_clean(); // Stop output buffering
      
            // Encode image data to base64
            $base64 = base64_encode(gzcompress(serialize($imageData), 9));
      
            // Free memory from temporary tile image
            imagedestroy($tile);

            $tileData =
            [
                'x' => $xPos,
                'y' => $yPos,
                'data' => $base64
            ];
      
            // Add base64 string to output array
            $tiles[] = $tileData;
            
            if($x + 1 < $cols)
            {
                $xPos += $tileWidth;                
            }
            else
            {
                $xPos = $tileWidth / 2;                
            }
        }
        $yPos += $tileHeight;
    }
    imagedestroy($image);

    // Return the array of base64 strings representing tiles
    return $tiles;
}

$imagePath = './example_map.jpg'; // Replace with your image path
$rows = 4;
$cols = 4;

$tiles = ImageToB64Tiles($imagePath, $rows, $cols);

DataAccess::$pdo = new PDO('sqlite:' . '../app/db/db.sqlite');
DataAccess::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

foreach($tiles as $tile)
{
    DataAccess::Insert('locations', ['x', 'y', 'data'], [$tile['x'], $tile['y'], $tile['data']]);
}