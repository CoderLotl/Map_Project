export class StorageManager
{
    WriteLS(key, value)
    {
        localStorage.setItem(key, value);
    }

    WriteSS(key, value)
    {
        sessionStorage.setItem(key, value);
    }

    ReadLS(key)
    {
        return localStorage.getItem(key);
    }

    ReadSS(key)
    {
        return sessionStorage.getItem(key);
    }

    RemoveLS(key)
    {
        localStorage.removeItem(key);
    }

    RemoveSS(key)
    {
        sessionStorage.removeItem(key);
    }    
}