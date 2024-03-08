/**
 * Apply to body instead of html tag to prevent flickering.
 */

import { getCookie, setCookie } from './cookie';

export const setLogo = (selected?: string) => {
  if (!selected) {
    return;
  }

  setCookie('logo', selected);
};

export const themeChange = (isAttachingToDOM = true) => {
  if (isAttachingToDOM) {
    document.addEventListener('DOMContentLoaded', themeSelect);
  } else {
    themeSelect();
  }
};

function themeSelect() {
  // Get the references to the scheme and theme select controls
  // that live in the footer of the page.
  const themeSelect = document.querySelector<HTMLSelectElement>(
    'select[data-choose-theme]'
  );
  const schemeSelect = document.querySelector<HTMLSelectElement>(
    'select[data-choose-scheme]'
  );

  handleAutoMode('data-scheme');

  setPersistedValue('theme', 'data-theme');
  setPersistedValue('scheme', 'data-scheme');

  if (themeSelect) {
    handleSelectChange(themeSelect, 'theme', 'data-theme');
  }

  if (schemeSelect) {
    handleSelectChange(schemeSelect, 'scheme', 'data-scheme');
  }
}

/**
 * Automatically switch between light and dark mode
 * based on the user's system preference.
 */
function handleAutoMode(dataAttrName: string) {
  const isDark = '(prefers-color-scheme: dark)';
  const mediaQueryList = window.matchMedia(isDark);

  mediaQueryList.addEventListener('change', function (event) {
    const newColorScheme = event.matches ? 'dark' : 'light';

    document.body.setAttribute(dataAttrName, newColorScheme);
  });
}

/**
 * Set persisted theme/scheme values in the UI if they're stored
 * on the user's machine.
 */
function setPersistedValue(cookieName: string, dataAttrName: string) {
  const persistedValue = getCookie(cookieName);
  if (persistedValue) {
    document.body.setAttribute(dataAttrName, persistedValue);
  }

  const toggleOption = document.querySelector<HTMLOptionElement>(
    `select[data-choose-${cookieName}] [value='${persistedValue}']`
  );
  if (toggleOption) {
    toggleOption.selected = true;
  }
}

/**
 * When a user selects a scheme or theme, persist their selection.
 */
function handleSelectChange(
  select: HTMLSelectElement,
  cookieName: string,
  dataAttrName: string
) {
  select.addEventListener('change', function () {
    document.body.setAttribute(dataAttrName, this.value);
    setCookie(cookieName, document.body.getAttribute(dataAttrName));
  });

  const toggleOption = document.querySelector<HTMLOptionElement>(
    `select[data-choose-${cookieName}] [value='${getCookie(cookieName)}']`
  );
  if (toggleOption) {
    toggleOption.selected = true;
  }
}
