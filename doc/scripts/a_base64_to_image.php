<?php

require __DIR__ . '../../../vendor/autoload.php'; // Classes Autoloader.
require __DIR__ . '../../../app/config/config.php'; // Initial configs.

use Model\Services\DataAccess;
use Model\Utilities\Log;

function TurnB64TilesIntoMap($tiles)
{
    $map = [];
    $x = 0;
    $xPos = 0;
    $y = 0;
    $yPos = 0;
    $clipMapHeight = 0;
    $clipMapWidth = 0;    

    // - - - Getting the map's columns and rows
    $cols = 1;
    $col = $tiles[0]['x'];
    $rows = 1;
    $row = $tiles[0]['y'];

    foreach($tiles as $tile)
    {
        if($tile['x'] > $col)
        {
            $cols++;
            $col = $tile['x'];
        }
        if($tile['y'] > $row)
        {
            $rows ++;
            $row = $tile['y'];
        }        
    }
    // - - - - - - - - - - - - - - - - - - - - -

    // Finding the 1st positiveX-positiveY tile.
    $positive_base = ($cols * ($rows / 3)) + ($cols / 3);

    // With the 1st true positive tile we can get the tiles height and width.
    $tileHeight = $tiles[$positive_base]['y'] * 2;
    $tileWidth= $tiles[$positive_base]['x'] * 2;

    // We make the base of our map organizing a matrix.
    foreach($tiles as $tile)
    {
        if($yPos == 0)
        {
            $yPos = $tile['y'];
            array_push($map, []);
        }
        elseif($tile['y'] > $yPos)
        {
            $yPos = $tile['y'];
            $y++;
            array_push($map, []);
        }

        if($xPos == 0)
        {
            $xPos = $tile['x'];
        }
        elseif($tile['x'] > $xPos)
        {
            $xPos = $tile['x'];
            $x++;
        }
        else
        {
            $xPos = $tile['x'];
            $x = 0;
        }

        array_push($map[$y], $tile['data']);
    }

    // We get the total height and width of our map.
    $clipMapHeight = count($map) * $tileHeight;
    $clipMapWidth = count($map[0]) * $tileWidth;
    // We create a blank image with those dimensions.
    $image = imagecreatetruecolor($clipMapWidth, $clipMapHeight);
    
    // With the blank image and the matrix we proceed to turn the data of the tiles into pictures and make a collage.
    for($yy = 0; $yy < $y + 1; $yy++)
    {
        for($xx = 0; $xx < $x + 1; $xx++)
        {
            $imageData = unserialize(gzuncompress(base64_decode($map[$yy][$xx])));
            $tile = imagecreatefromstring($imageData);
            imagecopy($image, $tile, $xx * $tileWidth, $yy * $tileHeight, 0, 0, $tileWidth, $tileHeight);
            imagedestroy($tile);
        }
    }

    return $image;
}

$tiles = DataAccess::Select('map');

$image = TurnB64TilesIntoMap($tiles);

imagejpeg($image, './test.jpeg');