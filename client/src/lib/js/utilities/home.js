import { DataAccessFetch } from "../services/DataAccessFetch.js";
import { StorageManager } from '../services/StorageManager.js';
// @ts-ignore
import { BACK_PATH, APP_NAME } from "../stores/stores.js";
// @ts-ignore
import { writable, get } from 'svelte/store';

let dataAccess = new DataAccessFetch();
let storageManager = new StorageManager();

export async function Init()
{
    let payload = {coord_x: 571, coord_y: 360, angle: 0, speed: 0};
    let newRoamer = { x: payload['coord_x'], y: payload['coord_y'], angle: 0, speed: 0 };
    storageManager.WriteSS('roamer', JSON.stringify(newRoamer));    

    GetImageByCoords(payload, '/get_clip_by_coords');
    document.getElementById('coords').innerHTML = `Coords X:<br> ${payload['coord_x']} - Y: ${payload['coord_y']}`;

    document.getElementById('clip').addEventListener('click', (event)=>
    {
        SetNewAngleByClick(event);
    });

    document.getElementById('next_turn').addEventListener('click', async ()=>
    {
        let BACK_PATH_ = get(BACK_PATH);
        SetButton();
        let roamer = JSON.parse(storageManager.ReadSS('roamer'));
        GetImageByCoords(roamer, '/next_turn_pic');
        let serverResponse = await dataAccess.getData(`${BACK_PATH_}` + '/next_turn_coords', roamer, true);        
        // @ts-ignore
        let svResp = await serverResponse.json();
        svResp = svResp['response'];
        
        roamer['x'] = svResp[0];        
        roamer['y'] = svResp[1];        
        roamer['speed'] = svResp[2];
        document.getElementById('sv_msg').textContent = svResp[3];
        if(svResp[4] == true)
        {
            // @ts-ignore
            document.getElementById('speed').value = 0;
        }
        storageManager.WriteSS('roamer', JSON.stringify(roamer));
        document.getElementById('coords').innerHTML = `Coords X:<br> ${roamer['x']} - Y: ${roamer['y']}`;
    });
}

async function GetInitialCoords()
{
    let BACK_PATH_ = get(BACK_PATH);
    let serverResponse = await dataAccess.getData(`${BACK_PATH_}` + '/get_random_coords', null, true);
    
    if(serverResponse)
    {
        // @ts-ignore
        let svResp = await serverResponse.json();
        return svResp['response'];
    }
}

async function GetImageByCoords(payload, endpoint)
{
    let BACK_PATH_ = get(BACK_PATH);
    let serverResponse = await dataAccess.getData(`${BACK_PATH_}` + endpoint, payload, true);

    if(serverResponse)
    {        
        let image = document.getElementById('clip');        
        // @ts-ignore
        image.src = URL.createObjectURL(serverResponse);
    }
}

function SetNewAngleByClick(event)
{
    let field = event.target;
    // Get click coordinates relative to the field element
    const clickX = event.offsetX;
    const clickY = event.offsetY;

    // Get the center coordinates of the field (assuming it's a square)
    const fieldCenterX = field.clientWidth / 2;
    const fieldCenterY = field.clientHeight / 2;

    // Calculate the difference in X and Y coordinates
    const deltaX = clickX - fieldCenterX;
    const deltaY = clickY - fieldCenterY;

    // Calculate the angle using Math.atan2 (more accurate than arctangent)
    const angleInRadians = Math.atan2(deltaY, deltaX);

    // Convert radians to degrees (optional)
    const angleInDegrees = angleInRadians * 180 / Math.PI;

    // Adjust the angle for a 0-360 degree range (optional)
    let adjustedAngle = angleInDegrees + 90; // Add 90 degrees to start from top (adjust as needed)
    if (adjustedAngle < 0)
    {
        adjustedAngle += 360;
    }

    adjustedAngle = parseInt(adjustedAngle.toString());

    // @ts-ignore
    document.getElementById('degrees').value = adjustedAngle;
    
    // Use the angle value for further processing
}

function SetButton()
{
    let roamer = JSON.parse(storageManager.ReadSS('roamer'));
    // @ts-ignore
    roamer['angle'] = parseInt(document.getElementById('degrees').value);
    // @ts-ignore
    roamer['speed'] = parseInt(document.getElementById('speed').value);
    storageManager.WriteSS('roamer', JSON.stringify(roamer));
}