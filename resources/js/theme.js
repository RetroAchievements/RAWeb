// taken from https://github.com/saadeghi/theme-change
// modified to use cookie instead of local storage
// apply to body instead of html tag to prevent flickering
import { getCookie, setCookie } from './cookie';

/**
 * @param {string} selected
 */
export function setLogo(selected) {
  if (!selected) {
    return;
  }
  setCookie('logo', selected);
}

export function themeChange(attach = true) {
  if (attach === true) {
    document.addEventListener('DOMContentLoaded', function (event) {
      themeSelect();
    });
  } else {
    themeSelect();
  }
}

function themeSelect() {
  (function (theme = getCookie('theme'), scheme = getCookie('scheme')) {
    if (getCookie('theme')) {
      document.body.setAttribute('data-theme', theme);
      const themeToggle = document.querySelector("select[data-choose-theme] [value='" + theme.toString() + "']");
      if (themeToggle) {
        [...document.querySelectorAll("select[data-choose-theme] [value='" + theme.toString() + "']")].forEach((el) => {
          el.selected = true;
        });
      }
    }
    if (getCookie('scheme')) {
      document.body.setAttribute('data-scheme', scheme);
      const schemeToggle = document.querySelector("select[data-choose-scheme] [value='" + scheme.toString() + "']");
      if (schemeToggle) {
        [...document.querySelectorAll("select[data-choose-scheme] [value='" + scheme.toString() + "']")].forEach((el) => {
          el.selected = true;
        });
      }
    }
  }());
  if (document.querySelector('select[data-choose-theme]')) {
    [...document.querySelectorAll('select[data-choose-theme]')].forEach((select) => {
      select.addEventListener('change', function () {
        document.body.setAttribute('data-theme', this.value);
        setCookie('theme', document.body.getAttribute('data-theme'));
        [...document.querySelectorAll("select[data-choose-theme] [value='" + getCookie('theme') + "']")].forEach((option) => {
          option.selected = true;
        });
      });
    });
  }
  if (document.querySelector('select[data-choose-scheme]')) {
    [...document.querySelectorAll('select[data-choose-scheme]')].forEach((select) => {
      select.addEventListener('change', function () {
        document.body.setAttribute('data-scheme', this.value);
        setCookie('scheme', document.body.getAttribute('data-scheme'));
        [...document.querySelectorAll("select[data-choose-scheme] [value='" + getCookie('scheme') + "']")].forEach((option) => {
          option.selected = true;
        });
      });
    });
  }
}
