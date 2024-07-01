/**
 * Clase compuesta por varios métodos que instancian y devuelven elementos HTML.
 * La función de esta clase es proveer de métodos para una creación ágil de elementos
 * a la hora de tener que definir el HTML de manera dinámica. 
 * @export
 * @class DynamicDrawer
 */
export class DynamicDrawer
{
    /**     
     * Crea un div.
     * @param {string} [id=null]
     * @param {string} [htmlClass=null]
     * @return {HTMLElement} 
     * @memberof DynamicDrawer
     */
    CreateDiv(id = null, htmlClass = null)
    {
        let div = document.createElement('div');
        if(id)
        {
            div.id = id;
        }
        if(htmlClass)
        {
            div.className = htmlClass;
        }
        return div;
    }    

    /**     
     * Crea un input.
     * @param {string} id
     * @param {string} type
     * @param {string} [name=null]
     * @param {boolean} [required=false]
     * @return {HTMLElement} 
     * @memberof DynamicDrawer
     */
    CreateInput(id = null, type, name = null, required = false, value = null, htmlClass)
    {
        let input = document.createElement('input');
        input.type = type;
        if(id)
        {
            input.id = id;        
        }
        if(name)
        {
            input.name = name;
        }
        if(value)
        {
            input.value = value;
        }
        if(required)
        {
            input.required = true;
        }
        if(htmlClass)
        {
            input.className = htmlClass;
        }
        
        return input;
    }

    CreateLabel(text, id = null, forEl = null, iconClass = null)
    {
        let lbl = document.createElement('label');    
        if(id)
        {
            lbl.id = id;
        }
        if(iconClass)
        {
            let i = document.createElement('i');
            i.className = iconClass;
            lbl.appendChild(i);            
        }
        let textNode = document.createTextNode(text);
        lbl.appendChild(textNode);
        if(forEl)
        {
            lbl.htmlFor = forEl;
        }        

        return lbl;
    }

    CreateLegend(text, id = null, iconClass = null)
    {
        let lg = document.createElement('legend');
        if(id)
        {
            lg.id = id;
        }
        if(iconClass)
        {
            let i = document.createElement('i');
            i.className = iconClass;
            lg.appendChild(i);
        }
        let textNode = document.createTextNode(text);
        lg.appendChild(textNode);

        return lg;
    }
    
    CreateRange(id, min, max, value = null)
    {
        let rng = document.createElement('input');
        rng.id = id;
        rng.type = 'range';
        rng.min = min;
        rng.max = max;
        if (value !== null)
        {
            rng.value = value;
        }
        else
        {
            rng.value = min;
        }
    
        return rng;
    }

    CreateSelect(id, options, size = null)
    {
        let select = document.createElement('select');
        select.id = id;
        
        for(let i = 0; i < options.length; i++)
        {
            let option = document.createElement('option');
            option.text = options[i];
            select.add(option);
        }

        return select;
    }    

    CreateButton(id, innerHTML)
    {
        let btn = document.createElement('button');
        btn.innerHTML = innerHTML;
        btn.id = id;

        return btn;
    }

    CreateSpan(id = null, textContent = null)
    {
        let span = document.createElement('span');
        if(id)
        {
            span.id = id;
        }
        span.textContent = textContent;
        return span;
    }

    CreateLink(rel, href)
    {
        let link = document.createElement('link');
        link.rel = rel;
        link.href = href;
        return link;
    }

    CreateH(hSize, text, id = null, htmlClass = null)
    {
        let h = document.createElement(`h${hSize}`);
        if(id)
        {
            h.id = id;
        }
        if(htmlClass)
        {
            h.className = htmlClass;
        }
        h.textContent = text;
        return h;
    }

    CreateHR(id = null, htmlClass = null)
    {
        let hr = document.createElement('hr');
        if(id)
        {
            hr.id = id;
        }
        if(htmlClass)
        {
            hr.className = htmlClass;
        }
        return hr;
    }

    CreateTable(users, tableHeaders)
    {
        let table = document.createElement('table');
        table.className = 'border'
        let tableBody = document.createElement('tbody');
        table.appendChild(tableBody);

        for(let i = 0; i < tableHeaders.length; i++)
        {
            let th = document.createElement('th');
            th.textContent = tableHeaders[i];
            th.dataset[tableHeaders[i]] = tableHeaders[i];
            th.className = 'border bg-slate-300';
            tableBody.appendChild(th);
        }

        this.LoadUsersToTable(table, tableHeaders, users);        

        return table;
    }

    LoadUsersToTable(table, tableHeaders, users)
    {
        let tableBody = table.getElementsByTagName('tbody')[0];

        users.forEach(user =>
            {            
                let row = document.createElement('tr');
                row.className = 'bg-slate-200 hover:bg-red-500 cursor-pointer';
                tableBody.appendChild(row);                
                
                for (let i = 0; i < tableHeaders.length; i++)
                {
                    let td = document.createElement('td');
                    td.className = 'text-center';
                    if(tableHeaders[i] == 'verified' || tableHeaders[i] == 'status' || tableHeaders[i] == 'type')
                    {
                        if(tableHeaders[i] == 'verified')
                        {
                            switch(user[tableHeaders[i]])
                            {
                                case 1:
                                    td.textContent = 'Yes';
                                    break;
                                case 2:
                                    td.textContent = 'No';
                                    break;
                            }
                        }
                        if(tableHeaders[i] == 'status')
                        {
                            switch(user[tableHeaders[i]])
                            {
                                case 1:
                                    td.textContent = 'Ok';
                                    break;
                                case 2:
                                    td.textContent = 'Inactive';
                                    break;
                                case 3:
                                    td.textContent = 'Banned';
                                    break;
                            }
                        }
                        if(tableHeaders[i] == 'type')
                        {
                            switch(user[tableHeaders[i]])
                            {
                                case 1:
                                    td.textContent = 'user';
                                    break;
                                case 2:
                                    td.textContent = 'staff';
                                    break;
                                case 3:
                                    td.textContent = 'admin';
                                    break;
                            }
                        }
                    }
                    else
                    {
                        td.textContent = user[tableHeaders[i]];
                    }
                    td.dataset[tableHeaders[i]] = tableHeaders[i];
                    row.appendChild(td);
                }
            }); 
    }

    CreateTimeSelect(maxValue, timeSelect = null, baseValue = null)
    {
        let newTimeSelect;
        if(timeSelect)
        {
            newTimeSelect = timeSelect;
            newTimeSelect.innerHTML = '';
        }
        else
        {
            newTimeSelect = document.createElement('select');
        }
        
        if(baseValue == null)
        {            
            for(let i = 0; i < maxValue; i++)
            {
                let opt = document.createElement('option');
    
                opt.value = i;
                if(i == 0)
                {            
                    opt.text = '00';
                }
                else
                {
                    if(i < 10)
                    {                        
                        opt.text = '0' + i;
                    }
                    else
                    {
                        opt.text = i;
                    }
                }
                newTimeSelect.appendChild(opt);
            }
        }
        else
        {
            for(let i = maxValue; i > baseValue; i--)
            {
                let opt = document.createElement('option');
    
                opt.value = i;
                if(i == 0)
                {            
                    opt.text = '00';
                }
                else
                {
                    opt.text = i;
                }
                newTimeSelect.appendChild(opt);
            }
        }
        
        return newTimeSelect;
    }
}