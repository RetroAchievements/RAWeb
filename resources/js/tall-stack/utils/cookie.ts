import Cookies from 'js-cookie';

const DEFAULT_COOKIE_EXPIRY_DAYS = 30;

export const deleteCookie = (cookieName: string) => {
  Cookies.remove(cookieName);
};

export const setCookie = (cookieName: string, value: string | null) => {
  if (value === null) {
    return;
  }

  Cookies.set(cookieName, value, {
    expires: DEFAULT_COOKIE_EXPIRY_DAYS,
    path: '/',
  });
};

export const getCookie = (cookieName: string): string | undefined => Cookies.get(cookieName);
