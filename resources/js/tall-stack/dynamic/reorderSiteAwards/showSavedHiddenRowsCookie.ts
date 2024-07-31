import { getCookie, setCookie } from '@/utils/cookie';

const cookieName = 'prefers_seeing_saved_hidden_rows_when_reordering';

function get(): boolean {
  return getCookie(cookieName) === 'true';
}

function set(newValue: boolean): void {
  setCookie(cookieName, String(newValue));
}

export const showSavedHiddenRowsCookie = { get, set };
