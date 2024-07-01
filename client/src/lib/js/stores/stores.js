import { writable } from 'svelte/store';

export let BASE_PATH = writable('');
export let BACK_PATH = writable('');
export let APP_NAME = writable('');
export let TOKEN = writable('');