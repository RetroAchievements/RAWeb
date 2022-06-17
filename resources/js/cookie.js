// TODO: user proper cookie lib for that?

/**
 * @param {string} cookieName
 * @param {string} value
 */
export function setCookie(cookieName, value) {
  const days = 30;
  const date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  const expires = `; expires=${date.toGMTString()}`;
  document.cookie = `${cookieName}=${value}${expires}; path=/`;
}

/**
 * @param {string} cookieName
 */
export function getCookie(cookieName) {
  const cookie = document.cookie.match(`(^|[^;]+)\\s*${cookieName}\\s*=\\s*([^;]+)`);
  return cookie ? cookie.pop() : null;
}
