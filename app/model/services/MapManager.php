<?php

namespace Model\Services;

use Model\Services\DataAccess;
use Model\Utilities\Log;
use Slim\Psr7\Response;

class MapManager
{

    /**
     * Turns an image into tiles, returning an array of objects like [ 'x' => xPos, 'y' => yPos, 'data' => base64(tile_data)].
     * xPos and yPos represent the X,Y coords of the center of the tile in relation with the whole picture.
     * @param mixed $imagePath
     * @param mixed $rows
     * @param mixed $cols
     * 
     * @return array
     */
    public static function TurnMapIntoB64Tiles($imagePath, $rows, $cols)
    {
        $image = imagecreatefromjpeg($imagePath);
        $width = imagesx($image);
        $height = imagesy($image);    

        $tileWidth = $width / $cols;
        $tileHeight = $height / $rows;
        $tiles = [];
        $xPos = $tileWidth / 2;
        $yPos = $height - ($tileHeight / 2);

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
                $xPos += $tileWidth;
            }
            $yPos -= $tileHeight;
        }
        imagedestroy($image);

        // Return the array of base64 strings representing tiles
        return $tiles;
    }

    /**
     * Receives an array of arrays containing, each element, the base64 data of a tile, the x-center, and y-center coords of the center of that tile.
     * Ex.: array([ ['x'=>int, 'y'=>int, 'data'=>string], [...] ], [ [...], [...] ]);
     * Returns a GdImage composed of all the tiles.
     * @param mixed $tiles
     * 
     * @return GdImage
     */
    private static function TurnB64TilesIntoMap($tiles)
    {
        $map = [];
        $x = 0;
        $xPos = 0;
        $y = 0;
        $yPos = 0;
        $tileHeight = $tiles[0]['y'] * 2;
        $tileWidth= $tiles[0]['x'] * 2;
        $mapHeight = 0;
        $mapWidth = 0;    

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

        $mapHeight = count($map) * $tileHeight;
        $mapWidth = count($map[0]) * $tileWidth;
        $image = imagecreatetruecolor($mapWidth, $mapHeight);
        
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

    public static function ReturnClip($request, $response)
    {
        $params = self::GetRequest($request);
        $tiles = DataAccess::Select('map');

        $clip = self::ReturnImageByCoords($params['coord_x'], $params['coord_y'], $tiles, 100, true, true);
        
        return self::ReturnImageResponse($clip);
    }

    // public static function ReturnLocationData($request, $response)
    // {
    //     $params = self::GetRequest($request);        
    //     $location = (DataAccessMDB::FindDocument(DATABASE, 'locations', ['x', 'y', 'type'], [(int) $params['coord_x'], (int) $params['coord_y'], (int) $params['type']]))[0];

    //     return self::ReturnResponse($request, $response, $location);
    // }

    private static function ReturnImageByCoords($charX, $charY, $tiles, $distance = 100, $centerDot = false, $compass = false, $movement = null)
    {
        $map = []; // This is the initial map we're going to make, not the real whole map. From this we're gonna cut a clip.
        $tileWidth = $tiles[0]['x'] * 2;
        $tileHeight = $tiles[0]['y'] * 2;

        $x = 0;
        $xPos = 0;
        $y = 0;
        $yPos = 0;
        $mapHeight = 0;
        $mapWidth = 0;   

        // Defining the search area as a square. The corners are top left, top right, bottom left, and bottom right.
        // It's important to keep in mind that the top left corner of the real picture is the point 0x,0y, and the bottom right is mapWidth-x,mapHeight-y.
        $topLeft = [$charX - $distance, $charY - $distance];
        $topRight = [$charX + $distance, $charY - $distance];
        $bottomLeft = [$charX - $distance, $charY + $distance];
        $bottomRight = [$charX + $distance, $charY + $distance];

        // We make sure the search area is within the map's area.
        $topLeft[0] < 0 ? 0 : $topLeft[0]; $topLeft[1] < 0 ? 0 : $topLeft[1];    
        $topRight[0] > $mapWidth ? $mapWidth : $topRight[0]; $topRight[1] < 0 ? 0 : $topRight[1];
        $bottomLeft[0] < 0 ? 0 : $bottomLeft[0]; $bottomLeft[1] > $mapHeight ? $mapHeight : $bottomLeft[1];    
        $bottomRight[0] > $mapWidth ? $mapWidth : $bottomRight[0]; $bottomRight[1] > $mapHeight ? $mapHeight : $bottomRight[1];

        $searchLeft = $topLeft[0];
        $searchRight = $bottomRight[0];
        $searchTop = $topLeft[1];
        $searchBottom = $bottomRight[1];
            
        foreach ($tiles as $tile)
        {
            $tileCenterX = $tile['x'];
            $tileCenterY = $tile['y'];
        
            // We define the sides of the tile
            $tileLeft = $tileCenterX - $tileWidth / 2;
            $tileRight = $tileCenterX + $tileWidth / 2;
            $tileTop = $tileCenterY - $tileHeight / 2;
            $tileBottom = $tileCenterY + $tileHeight / 2;

            if(
                // We check if the tile is in any point inside the search area.
                ($searchLeft <= $tileRight && $searchRight >= $tileLeft) && // Overlap on X
                ($searchTop <= $tileBottom && $searchBottom >= $tileTop)
            )
            {
                // If so, we proceed to push the tiles into the new map.
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

                array_push($map[$y], $tile);        
            }
        }    

        // We calculate the size of the new map and create the base image.
        $mapHeight = count($map) * $tileHeight;
        $mapWidth = count($map[0]) * $tileWidth;
        $image = imagecreatetruecolor($mapWidth, $mapHeight);
        
        // We proceed to make a collage with the tiles.
        for($yy = 0; $yy < $y + 1; $yy++)
        {
            for($xx = 0; $xx < $x + 1; $xx++)
            {
                $imageData = unserialize(gzuncompress(base64_decode($map[$yy][$xx]['data'])));
                $tile = imagecreatefromstring($imageData);
                imagecopy($image, $tile, $xx * $tileWidth, $yy * $tileHeight, 0, 0, $tileWidth, $tileHeight);
                imagedestroy($tile);
            }
        }

        // Now we cut the area we need to show.
        //First we find the center of our image.
        $mapCenterX = $charX - ($map[0][0]['x'] - ($tileWidth / 2));
        $mapCenterY = $charY - ($map[0][0]['y'] - ($tileHeight / 2));

        $clipWidth = (($distance * 2) <= $mapWidth ) ? $distance * 2 : $mapWidth;
        $clipHeight = (($distance * 2) <= $mapHeight ) ? $distance * 2 : $mapHeight;

        // src_x and src_y represent the point 0:0 of the clip, which is the top left corner.
        $src_x = $mapCenterX - $distance;
        $src_y = $mapCenterY - $distance;
        
        if($centerDot)
        {
            $color_centrepoint = imagecolorallocate ($image, 255, 165, 0);
            imagefilledellipse ($image, $mapCenterX, $mapCenterY, 6, 6, $color_centrepoint);
        }

        $clip = imagecreatetruecolor($clipWidth, $clipHeight);
        imagecopyresampled($clip, $image, 0, 0, $src_x, $src_y, $clipWidth, $clipHeight, $clipWidth, $clipHeight);    
        imagedestroy($image);

        if($movement)
        {
            $direction = $movement['direction'];
            $speed = $movement['speed'];
            $lineLength = min($distance * 0.7, $speed);
            $radians = deg2rad($direction);

            $centerX = imageSX($clip) / 2;
            $centerY = imageSY($clip) / 2;
            $startX = $centerX + cos($radians) * $lineLength;
            $startY = $centerY + sin($radians) * $lineLength;
            
            self::imageSmoothAlphaLine($clip, $startX, $startY, $centerX, $centerY, 255, 0, 0, 70); // Adjust thickness with the last argument
        }

        if($compass)
        {
            $compassImage = imagecreatefromgif(APP_ROOT . '/model/assets/compassdigit.gif');
            imagecolortransparent ($compassImage, imagecolorat ($compassImage, 100, 100));
            $newCompass = imagecreatetruecolor($distance * 2, $distance * 2);
            imagecopyresampled($newCompass, $compassImage, 0, 0, 0, 0, $distance * 2, $distance * 2, 189, 189);
            imagedestroy($compassImage);
            imagecolortransparent ($newCompass, imagecolorat ($newCompass, 100, 100));
            imagecopymerge ($clip, $newCompass, 0, 0, 0, 0, $clipWidth, $clipHeight, 100);
            imagedestroy($newCompass);
        }

        return $clip;
    }

    /*
    // UNUSED, but could be used.
    */
    public static function ReturnRandomCoords($request, $response)
    {
        $tiles = DataAccess::Select('map');
        
        $map = [];
        $x = 0;
        $xPos = 0;
        $y = 0;
        $yPos = 0;
        $tileHeight = $tiles[0]['y'] * 2;
        $tileWidth= $tiles[0]['x'] * 2;
        $mapHeight = 0;
        $mapWidth = 0; 

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
        $mapHeight = count($map) * $tileHeight;
        $mapWidth = count($map[0]) * $tileWidth;

        $newX = rand(1, $mapWidth);
        $newY = rand(1, $mapHeight);

        $payload = [$newX, $newY];
        return self::ReturnResponse($request, $response, $payload);
    }

    /**
     * @param mixed $request
     * @param mixed $response
     * @param bool $isPic Determines if the function should return a picture or just numeric params.
     * 
     * @return [type]
     */
    public static function ReturnArrival($request, $response, $isPic = false)
    {
        $params = self::GetRequest($request);
        $speed = 20 * $params['speed'] / 100;
        $landMasses = DataAccess::Select('land_masses');
        $tiles = DataAccess::Select('map');
        $aground = false;        
        $arrivalPoint = null;
        $returnMessage = null;

        $dimensions = self::GetMapDimnensions($tiles);

        // If the speed is 0, we return either the same pic or the same coords.
        if($params['speed'] == 0)
        {
            if($isPic)
            {
                $clip = self::ReturnImageByCoords($params['x'], $params['y'], $tiles, 100, true, true);        
                return self::ReturnImageResponse($clip);
            }
            else
            {
                return self::ReturnResponse($request, $response, [$params['x'], $params['y'], $params['speed'], 'The ship is floating quietly.', false]);
            }
        }



        // - - - - -
        // Else we proceed to check the trip ...
        // - - - - -
        // We go from speed 0, to the max set by the client ...
        for($i = 0; $i < $speed; $i++)
        {
            if(!$aground)
            {
                // We get the arrival point for the iterated speed ...
                [$arrivalX, $arrivalY] = self::CalculateArrivalPoint($params['x'], $params['y'], $params['angle'], $i, $dimensions, true);
                
                // For each landmass we check if the current arrival point collides with the landmass or not.
                foreach($landMasses as $landMass)
                {            
                    $stdChunk = json_decode($landMass['corners']);
                    
                    $chunk = [];
                    foreach($stdChunk as $bit)
                    {
                        $chunk[] = (array) $bit;
                    }
        
                    $isInArea = self::IsInArea(['x' => $arrivalX, 'y' => $arrivalY], $chunk);            
                    if($isInArea)
                    {
                        // If the current arrival point collides with the landmass, then we set the flag $aground, and exit the iteration
                        // of landmasses.
                        $aground = true;                    
                        break;
                    }                    
                }

                if(!$aground)
                {
                    // If the ship is not aground, then we set the arrival point as safe and go for the next iteration.
                    $arrivalPoint = [$arrivalX, $arrivalY];
                }
            }
            else
            {
                break;
            }
        }

        if($aground)
        {
            $speed = 0;
            $returnMessage = 'The ship stops to prevent crashing on land.';
        }
        else
        {            
            $returnMessage = 'The ship is sailing.';
        }

        if($isPic)
        {
            $clip = self::ReturnImageByCoords($arrivalPoint[0], $arrivalPoint[1], $tiles, 100, true, true);        
            return self::ReturnImageResponse($clip);
        }
        else
        {
            return self::ReturnResponse($request, $response, [$arrivalPoint[0], $arrivalPoint[1], $speed, $returnMessage, $aground]);
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - -

    private static function GetMapDimnensions($map)
    {
        $rows = 0;
        $columns = 0;
        $xLimit = 0;
        $yLimit = 0;
        $dimensions = ["x"=> 0, "y"=>0];

        foreach($map as $tile)
        {
            if($tile['x'] > $xLimit)
            {
                $xLimit = $tile['x'];
                $columns++;
            }
            if($tile['y'] > $yLimit)
            {
                $yLimit = $tile['y'];
                $rows++;
            }
        }

        $dimensions["x"] = ($map[0]['x'] * 2) * $columns;
        $dimensions["y"] = ($map[0]['y'] * 2) * $rows;

        return $dimensions;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - -

    private static function IsInArea($point, $polygon)
    {
        $intersections = 0;
        $count = count($polygon);
        for($i = 0; $i < $count; $i++)
        {
            $vertex1 = $polygon[$i];
            $vertex2 = $polygon[($i + 1) % $count]; // Wrap around for last point                        
          
            // Check if point is on a horizontal edge
            if($vertex1['y'] == $vertex2['y'] && $point['y'] == $vertex1['y'] && $point['x'] > min($vertex1['x'], $vertex2['x']) && $point['x'] < max($vertex1['x'], $vertex2['x']))
            {
                return "boundary"; // Point is on the boundary
            }
            
            // Check if ray intersects edge
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) && $point['y'] <= max($vertex1['y'], $vertex2['y']) && $point['x'] <= max($vertex1['x'], $vertex2['x']) && $vertex1['y'] != $vertex2['y'])
            {
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters)
                {
                    $intersections++;
                }
            }
        }
        
        // If the number of intersections is odd, the point is inside
        return ($intersections % 2 != 0) ? true : false;
    }

    private static function CalculateArrivalPoint($startX, $startY, $angle, $distance, $limits, $round = true)
    {            
        // Convert angle to radians (0 degrees is 6 o'clock)
        $angleInRadians = deg2rad($angle - 90);
    
        // Calculate delta X and delta Y based on angle and distance
        $deltaX = cos($angleInRadians) * $distance;
        $deltaY = sin($angleInRadians) * $distance;
    
        // Calculate arrival point coordinates
        $arrivalX = $startX + $deltaX;
        $arrivalY = $startY + $deltaY;
    
        // Round coordinates if needed
        if ($round)
        {
            $arrivalX = round($arrivalX); // Adjust rounding precision as needed
            $arrivalY = round($arrivalY);
        }

        if($arrivalX > $limits['x'])
        {
            $arrivalX = $arrivalX - $limits['x'];
        }
        elseif($arrivalX < 0)
        {
            $arrivalX = $limits['x'] + $arrivalX;
        }
        if($arrivalY > $limits['y'])
        {
            $arrivalY = $arrivalY - $limits['y'];
        }
        elseif($arrivalY < 0)
        {
            $arrivalY = $limits['y'] + $arrivalY;
        }        
    
        return [$arrivalX, $arrivalY];
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - -

    private static function GetRequest($request)
    {        
        if($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
        {
            $params = $request->getQueryParams();
        }
        else
        {
            $params = $request->getParsedBody();
        }        
        
        return $params;
    }

    private static function ReturnResponse($request, $response, $payload, $status = 200)
    {        
        $response->getBody()->write(json_encode(['response' => $payload]));        
        return $response->withStatus($status);
    }

    private static function ReturnImageResponse($payload)
    {
        $response = new Response();        

        // Set the content type header
        $response = $response->withHeader('Content-Type', image_type_to_mime_type(IMAGETYPE_PNG)); // Adjust for desired format (JPG, PNG, etc.)
        
        // Use output buffering to capture the image data
        ob_start();
        imagepng($payload); // Replace with appropriate function for your format (imagejpeg, imagegif, etc.)
        $imageData = ob_get_contents();
        ob_end_clean();
        
        // Write the image data to the response body
        $response->getBody()->write($imageData);
        
        return $response;  
    }

    private static function imageSmoothAlphaLine ($image, $x1, $y1, $x2, $y2, $r, $g, $b, $alpha=0)
    {
        $icr = $r;
        $icg = $g;
        $icb = $b;
        $dcol = imagecolorallocatealpha($image, $icr, $icg, $icb, $alpha);
        
        if ($y1 == $y2 || $x1 == $x2)
        {
            imageline($image, $x1, $y2, $x1, $y2, $dcol);
        }
        else
        {
            $m = ($y2 - $y1) / ($x2 - $x1);
            $b = $y1 - $m * $x1;
      
            if (abs ($m) <2)
            {
                $x = min($x1, $x2);
                $endx = max($x1, $x2) + 1;
        
                while ($x < $endx)
                {
                    $y = $m * $x + $b;
                    $ya = ($y == floor($y) ? 1: $y - floor($y));
                    $yb = ceil($y) - $y;
                
                    $trgb = ImageColorAt($image, $x, floor($y));
                    $tcr = ($trgb >> 16) & 0xFF;
                    $tcg = ($trgb >> 8) & 0xFF;
                    $tcb = $trgb & 0xFF;
                    imagesetpixel($image, $x, floor($y), imagecolorallocatealpha($image, ($tcr * $ya + $icr * $yb), ($tcg * $ya + $icg * $yb), ($tcb * $ya + $icb * $yb), $alpha));
            
                    $trgb = ImageColorAt($image, $x, ceil($y));
                    $tcr = ($trgb >> 16) & 0xFF;
                    $tcg = ($trgb >> 8) & 0xFF;
                    $tcb = $trgb & 0xFF;
                    imagesetpixel($image, $x, ceil($y), imagecolorallocatealpha($image, ($tcr * $yb + $icr * $ya), ($tcg * $yb + $icg * $ya), ($tcb * $yb + $icb * $ya), $alpha));
            
                    $x++;
                }
            }
            else
            {
            $y = min($y1, $y2);
            $endy = max($y1, $y2) + 1;
        
            while ($y < $endy) {
                $x = ($y - $b) / $m;
                $xa = ($x == floor($x) ? 1: $x - floor($x));
                $xb = ceil($x) - $x;
        
                $trgb = ImageColorAt($image, floor($x), $y);
                $tcr = ($trgb >> 16) & 0xFF;
                $tcg = ($trgb >> 8) & 0xFF;
                $tcb = $trgb & 0xFF;
                imagesetpixel($image, floor($x), $y, imagecolorallocatealpha($image, ($tcr * $xa + $icr * $xb), ($tcg * $xa + $icg * $xb), ($tcb * $xa + $icb * $xb), $alpha));
        
                $trgb = ImageColorAt($image, ceil($x), $y);
                $tcr = ($trgb >> 16) & 0xFF;
                $tcg = ($trgb >> 8) & 0xFF;
                $tcb = $trgb & 0xFF;
                imagesetpixel ($image, ceil($x), $y, imagecolorallocatealpha($image, ($tcr * $xb + $icr * $xa), ($tcg * $xb + $icg * $xa), ($tcb * $xb + $icb * $xa), $alpha));
        
                $y ++;
            }
            }
        }
    }
}