<script>
// @ts-nocheck

  import { Router, Route } from 'svelte-routing';
  import { BACK_PATH, BASE_PATH, APP_NAME} from "./lib/js/stores/stores.js";
  import { writable, get } from 'svelte/store';
  import { GetAppName } from './lib/js/utilities/app.js';

  import Home from './lib/svelte/Home.svelte';

  const isDev = import.meta.env.MODE;    
  
  if(isDev == 'development')
  {
    BASE_PATH.set('/client/dist');
    BACK_PATH.set('http://localhost:8000'); // << - - - SET YOUR DEV URL HERE    
  }
  else
  {
    BASE_PATH.set('');
    BACK_PATH.set(window.location.origin); // << - - - SET YOUR PROD URL HERE    
  }

  let BASE_PATH_ = get(BASE_PATH);

  GetAppName();  

  const routes = [
    {path: `${BASE_PATH_}/`, component: Home },
  ];
</script>
  
<Router>
  {#each routes as { path, component }}
    <Route {path} let:params>
        <svelte:component this={component} {...params} />
    </Route>
  {/each}
</Router>