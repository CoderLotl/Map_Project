import { DataAccessFetch } from "../services/DataAccessFetch.js";
import { BACK_PATH, APP_NAME } from "../stores/stores.js";
import { writable, get } from 'svelte/store';

let dataAccess = new DataAccessFetch();

export async function GetAppName()
{
    let BACK_PATH_ = get(BACK_PATH);
    let serverResponse = await dataAccess.getData(`${BACK_PATH_}` + '/app_name', null, true);    

    if(serverResponse)
    {
        let resp = await serverResponse.json();        
        APP_NAME.set(resp['response']);        
    }
}