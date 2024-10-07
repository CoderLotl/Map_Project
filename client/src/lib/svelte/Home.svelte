<script>
    import { onMount, tick } from 'svelte';
    import { APP_NAME, BASE_PATH } from "../js/stores/stores.js";
    import { get } from 'svelte/store';
    import { navigate } from 'svelte-routing';
    import { Init } from '../js/utilities/home.js';

    $: APP_NAME_ = $APP_NAME;

    onMount(async ()=>
    {
        await tick();
        Init();
    });
</script>

<svelte:head>
    <title>{APP_NAME_} - Home</title>
</svelte:head>

<div class="w-full h-full bg-gray-900 p-4">
    <div class="border border-white h-full rounded-3xl flex flex-col p-4">
        
        <div id="top-half" class="w-full h-1/2 flex">
            <div id="map-container" class="border-x border-t border-white w-full h-full rounded-t-3xl p-6 flex flex-col items-center justify-around">
                <!-- svelte-ignore a11y-missing-attribute -->
                <img id="clip" class="rounded-3xl w-[70%] mt-[20%] md:w-[40%] md:max-w-[70%] md:mt-[15%]">
                <p id="coords" class="text-slate-300 pb-20"></p>
            </div>
        </div>
        <div id="bottom-half" class="h-1/2 flex">

            <div id="controls" class="border-l border-b border-white w-1/2 rounded-bl-3xl p-2">
                <fieldset class="border border-white rounded-3xl p-4 w-full h-full flex flex-col items-center">
                    <legend class="px-2 bg-slate-400 rounded-xl duration-500 hover:text-red-600 hover:duration-500">
                        Controls
                    </legend>
                    <div class="flex justify-center w-full md:mt-[30%]">
                        <div class="flex flex-col mr-[25%]">
                            <span class="text-slate-300">Angle:</span>
                            <span class="text-slate-300 mt-4">Speed:</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="bg-gray-400 pr-1 flex">
                                <input id="degrees" type="text" class="text-center w-12" value="0">
                                <span class="text-center grow">°</span>
                            </span>
                            <span class="mt-4 bg-gray-400 pr-1 flex">
                                <select id="speed" class="text-center w-12">
                                    <option>0</option>
                                    <option>10</option>
                                    <option>20</option>
                                    <option>30</option>
                                    <option>40</option>
                                    <option>50</option>
                                    <option>60</option>
                                    <option>70</option>
                                    <option>80</option>
                                    <option>90</option>
                                    <option>100</option>
                                </select>
                                <span class="text-center grow">
                                    %
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="flex">                        
                        <button id="next_turn" class="mt-10 w-20 h-10 bg-orange-500 rounded-3xl font-bold duration-200 hover:bg-orange-400 hover:duration-200">Next Turn</button>
                    </div>
                </fieldset>
            </div>
            
            <div id="location-details" class="border-r border-b border-white w-1/2 rounded-br-3xl p-2">
                <fieldset class="border border-white rounded-3xl p-4 w-full h-full">
                    <legend class="px-2 bg-slate-400 rounded-xl duration-500 hover:text-red-600 hover:duration-500">
                        Details
                    </legend>
                    <p id="sv_msg" class="italic text-lime-600 md:mt-[30%] md:mx-[30%]">
                        Nothing to show.
                    </p>
                </fieldset>
            </div>
        </div>
    </div>
</div>