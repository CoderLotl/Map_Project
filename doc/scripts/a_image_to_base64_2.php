<?php

require __DIR__ . '../../../vendor/autoload.php'; // Classes Autoloader.
require __DIR__ . '../../../app/config/config.php'; // Initial configs.

use Model\Services\DataAccess;
use Model\Utilities\Log;

// NOTE: it's designed to work ONLY with even number of cols and rows, not for ood numbers.
function ImageToB64Tiles($imagePath, $rows, $cols)
{
    $image = imagecreatefromjpeg($imagePath);
    $width = imagesx($image);
    $height = imagesy($image);    
    $tileWidth = $width / $cols;
    $tileHeight = $height / $rows;
    $tiles = [];
    $xPos = $tileWidth / 2;     // X center for the 1st tile
    $yPos = $tileHeight / 2;    // Y center for the 1st tile

    $totalCols = $cols * 2;
    $totalRows = $rows * 2;

    $colsOffset = ($cols * 2) / 4;
    $rowsOffset = ($rows * 2) / 4;

    // We create our initial blank matrix.
    for($y = 0; $y < $totalRows; $y++)
    {
        for($x = 0; $x < $totalCols; $x++)
        {
            $tiles[$y][$x] = [];
        }
    }

    // We process the image and turn it into tiles, throwing them at the center of the map keeping into account the offset.
    for($y = 0; $y < $rows; $y++)
    {        
        for($x = 0; $x < $cols; $x++)
        {            
            $tile = imagecreatetruecolor($tileWidth, $tileHeight); // We create a blank GD image.
            imagecopyresampled(
                $tile,              // Destination image ($dst_img)
                $image,             // Source image ($src_image)
                0,                  // X offset in the destination image
                0,                  // Y offset in the destination image
                $x * $tileWidth,    // X offset in the source image
                $y * $tileHeight,   // Y offset in the source image
                $tileWidth,         // X width of the newly resampled image
                $tileHeight,        // Y width of the newly resampled image
                $tileWidth,         // X width of the area to copy out of the $src_image
                $tileHeight         // Y width of the area to copy out of the $src_image
            );

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

            $tiles[$y + $rowsOffset][$x + $colsOffset] = $tileData; // We throw our tile into the matrix, keeping into acount the offset.
            
            /*
            Now... The initial map is at the center of our final map, which is y*2:x*2 the side of the initial map.
            This means there rest of the map, which is blank till now, must be filled with quarters of the initial map.
                    x1                                                                 y1:x4
                  y1                |               |                   |
                    bottom-right    |   bottom-left |   bottom-right    |   bottom-left
                    ________________|_______________|___________________|_______________
                                    |               |                   |
                    top-right       |   top-left    |   top-right       |   top-left
                    ________________|_______________|___________________|_______________
                                    |               |                   |
                    bottom-right    |   bottom-left |   bottom-right    |   bottom-left
                    ________________|_______________|___________________|_______________
                                    |               |                   |
                    top-right       |   top-left    |   top-right       |   top-left
                y4  ________________|_______________|___________________|_______________ x4
                                                                                        y4:x4
            */

            $redundantTileData = $tileData;

            if($y < ($rows / 2))
            {
                if($x < ($cols / 2)) // Top left quarter of the initial map.
                {
                    $redundantTileData['x'] += $width;                    
                    $tiles[$y + $rowsOffset][$x + ($colsOffset * 3)] = $redundantTileData; // y2:x4

                    $redundantTileData = $tileData;                    
                    $redundantTileData['y'] += $height;
                    $tiles[$y + ($rowsOffset * 3)][$x + $colsOffset] = $redundantTileData; // y4:x2

                    $redundantTileData = $tileData;                    
                    $redundantTileData['x'] += $width;
                    $redundantTileData['y'] += $height;
                    $tiles[$y + ($rowsOffset * 3)][$x + ($colsOffset * 3)] = $redundantTileData; //y4:x4
                }
                else // Top right quarter of the initial map.
                {
                    $redundantTileData['x'] -= $width;                    
                    $tiles[$y + $rowsOffset][$x - $colsOffset] = $redundantTileData; // y2:x1

                    $redundantTileData = $tileData;                    
                    $redundantTileData['x'] -= $width;
                    $redundantTileData['y'] += $height;
                    $tiles[$y + ($rowsOffset * 3)][$x - $colsOffset] = $redundantTileData; // y4:x1

                    $redundantTileData = $tileData;                    
                    $redundantTileData['y'] += $height;
                    $tiles[$y + ($rowsOffset * 3)][$x + $colsOffset] = $redundantTileData; // y4:x3
                }
            }
            else
            {
                if($x < ($cols / 2)) // Bottom left quarter.
                {
                    $redundantTileData['y'] -= $height;                    
                    $tiles[$y - $rowsOffset][$x + $colsOffset] = $redundantTileData; // y1:x2

                    $redundantTileData = $tileData;                    
                    $redundantTileData['x'] += $width;
                    $redundantTileData['y'] -= $height;
                    $tiles[$y - $rowsOffset][$x + ($colsOffset * 3)] = $redundantTileData; // y1:x4

                    $redundantTileData = $tileData;                    
                    $redundantTileData['x'] += $width;
                    $tiles[$y + $rowsOffset][$x + ($colsOffset * 3)] = $redundantTileData; // y3:x4
                }
                else // Bottom right quarter.
                {                    
                    $redundantTileData['x'] -= $width;
                    $redundantTileData['y'] -= $height;
                    $tiles[$y - $rowsOffset][$x - $colsOffset] = $redundantTileData; // y1:x1

                    $redundantTileData = $tileData;                    
                    $redundantTileData['y'] -= $height;
                    $tiles[$y - $rowsOffset][$x + $colsOffset] = $redundantTileData; // y1:x3

                    $redundantTileData = $tileData;                    
                    $redundantTileData['x'] -= $width;                    
                    $tiles[$y + $rowsOffset][$x - $colsOffset] = $redundantTileData; // y3:x1
                }
            }

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

DataAccess::$pdo = new PDO('sqlite:' . '../../app/db/db.sqlite');
DataAccess::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


for($y = 0; $y < $rows * 2; $y++)
{
    for($x = 0; $x < $cols * 2; $x++)
    {
        $tile = $tiles[$y][$x];        
        DataAccess::Insert('map', ['x', 'y', 'data'], [$tile['x'], $tile['y'], $tile['data']]);
    }
}