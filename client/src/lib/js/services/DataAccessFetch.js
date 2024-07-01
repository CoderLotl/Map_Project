
export class DataAccessFetch
{
    // GET
    async getData(url, params = null, returnResponse = false, showError = true)
    {
        const queryString = new URLSearchParams(params).toString();
        const requestUrl = queryString ? `${url}?${queryString}` : url;

        try {
            const response = await fetch(requestUrl,
            {
                method: 'GET',
                headers:
                {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });            

            if(response.ok)
            {
                const contentType = response.headers.get('Content-Type');
                if(contentType.startsWith('image/'))
                {
                    try {
                        const blob = await response.blob();
                        
                        return blob;
                    }
                    catch (error)
                    {
                        console.error('Error converting response to blob:', error);
                        // Handle error (return error or display fallback)
                    }
                }
                else
                {
                    if(returnResponse == true || params != null)
                    {
                        return response;
                    }
                    else
                    {
                        return true;
                    }
                }
            }
            else
            {
                if(returnResponse == true || params != null)
                {
                    return response;
                }
                else
                {
                    return false;
                }
            }
        }
        catch (error)
        {
            if(showError == true)
            {
                console.error('Error posting data:', error);
                throw error;
            }
        }
    }
    
    // POST
    async postData(url, payload = null, returnResponse = false, showError = true)
    {
        try{
            let response;
            let payL = payload !== null ? JSON.stringify(payload) : '';
    
            response = await fetch(url,
            {
                method: 'POST',
                headers:
                {
                    'Content-Type': 'application/json'
                },
                body: payL,
                credentials: 'include'
            });
    
            if(response.ok)
            {
                if(returnResponse == true || payload != null)
                {
                    return response;
                }
                else
                {
                    return true;
                }
            }
            else
            {
                if(returnResponse == true || payload != null)
                {
                    return response;
                }
                else
                {
                    return false;
                }
            }
        }
        catch(error)
        {
            if(showError == true)
            {
                console.error('Error posting data:', error);
                throw error;
            }
        }
    }

    // PUT
    async putData(url, payload)
    {
        try {
            const response = await fetch(url,
            {
                method: 'PUT',
                headers:
                {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
                credentials: 'include'
            });
            if(response)
            {
                return response;
            }
            else
            {
                return false;
            }
        }
        catch (error)
        {
            console.error('Error updating data:', error);
            throw error;
        }
    }
    
    // DELETE
    async deleteData(url, params = null)
    {
        const queryString = new URLSearchParams(params).toString();
        const requestUrl = queryString ? `${url}?${queryString}` : url;
        try {
            const response = await fetch(requestUrl,
            {
                method: 'DELETE',
                headers:
                {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });
            if(response)
            {
                return response;
            }
            else
            {
                return false;
            }
        }
        catch (error)
        {
            console.error('Error deleting data:', error);
            throw error;
        }
    }
}