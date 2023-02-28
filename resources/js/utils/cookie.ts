import Cookies from 'js-cookie';

const DEFAULT_COOKIE_EXPIRY_DAYS = 30;

export const setCookie = (cookieName: string, value: string | null) => {
  if (!value) {
    return;
  }

  Cookies.set(cookieName, value, {
    expires: DEFAULT_COOKIE_EXPIRY_DAYS,
    path: '/',
  });
};

export const getCookie = (cookieName: string): string | undefined => Cookies.get(cookieName);
