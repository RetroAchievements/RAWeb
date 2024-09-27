/* eslint-disable @typescript-eslint/no-unused-vars */

import { createElement, useEffect, useState } from 'react';

import { Context } from './context';
import type DefaultOptionsInterface from './interfaces/default-options';
import type I18nProviderProps from './interfaces/i18n-provider-props';
import type ReplacementsInterface from './interfaces/replacements';
import pluralization from './utils/pluralization';
import recognizer from './utils/recognizer';
import replacer from './utils/replacer';
import resolver from './utils/resolver';

/**
 *
 */
const isServer = typeof window === 'undefined';

/**
 * Map object for translations.
 */
const translation = new Map();

/**
 * Get document lang meta from HTML.
 */
const documentLang =
  typeof document !== 'undefined'
    ? document?.documentElement?.lang?.replace('-', '_') || 'en'
    : 'en';

/**
 * The default options.
 */
const defaultOptions: DefaultOptionsInterface = {
  locale: documentLang,
  fallbackLocale: documentLang,
  prevLocale: documentLang,
  files: {},
};

/**
 * Laravel React I18n Provider:
 */
export default function LaravelReactI18nProvider({
  children,
  ssr,
  ...currentOptions
}: I18nProviderProps) {
  const [isFirstRender, setIsFirstRender] = useState<boolean>(true);
  const [loading, setLoading] = useState<boolean>(!isServer);
  const [options, setOptions] = useState<DefaultOptionsInterface>({
    ...defaultOptions,
    ...currentOptions,
  });
  const { getLocales, isLocale } = recognizer(options.files);

  // Determine if files are eagerly loaded.
  const filesAreEagerlyLoaded = Object.values(options.files).every(
    (value) => typeof value === 'object' && value !== null,
  );

  const { locale, fallbackLocale } = options;

  if (!translation.get(locale)) {
    if (filesAreEagerlyLoaded) {
      fetchLocaleSync(locale);
    } else if (isServer) {
      fetchLocaleServer(locale);
    }
  }

  if (locale !== fallbackLocale && !translation.get(fallbackLocale)) {
    if (filesAreEagerlyLoaded) {
      fetchLocaleSync(fallbackLocale);
    } else if (isServer) {
      fetchLocaleServer(fallbackLocale);
    }
  }

  useEffect(() => {
    if (!filesAreEagerlyLoaded) {
      if (!translation.get(locale)) fetchLocaleClient(locale);
      if (locale !== fallbackLocale && !translation.get(fallbackLocale))
        fetchLocaleClient(fallbackLocale);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [options.locale]);

  function fetchLocaleSync(locale: string): void {
    const responses = resolver(options.files, locale);

    for (const response of responses) {
      translation.set(locale, {
        ...(translation.get(locale) || {}),
        ...response.default,
      });
    }
  }

  /**
   * Initialise translations for server.
   */
  if (isServer) {
    const { locale, fallbackLocale } = options;

    if (!translation.get(locale)) fetchLocaleServer(locale);
    if (locale !== fallbackLocale && !translation.get(fallbackLocale))
      fetchLocaleServer(fallbackLocale);
  }

  /**
   * Fetching locale for client side.
   */
  function fetchLocaleClient(locale: string): void {
    const promises = resolver(options.files, locale);

    setLoading(true);
    Promise.all(promises)
      .then((responses) => {
        for (const response of responses) {
          translation.set(locale, {
            ...(translation.get(locale) || {}),
            ...response.default,
          });
        }
      })
      .then(() => {
        if (isFirstRender) setIsFirstRender(false);
        setLoading(false);
      });
  }

  /**
   * Fetching locale for server side.
   */
  function fetchLocaleServer(locale: string): void {
    const responses = resolver(options.files, locale);

    for (const response of responses) {
      translation.set(locale, {
        ...(translation.get(locale) || {}),
        ...response.default,
      });
    }
  }

  /**
   * Get the translation for the given key.
   */
  function t(key: string, replacements: ReplacementsInterface = {}): string {
    const { locale, fallbackLocale, prevLocale } = options;

    let message = translation.get(fallbackLocale)?.[key]
      ? translation.get(fallbackLocale)[key]
      : key;

    if (isLocale(locale)) {
      if (translation.get(locale)?.[key]) {
        message = translation.get(locale)[key];
      } else if (translation.get(prevLocale)?.[key]) {
        message = translation.get(prevLocale)[key];
      } else if (translation.get(fallbackLocale)?.[key]) {
        message = translation.get(fallbackLocale)[key];
      }
    }

    return replacer(message, replacements);
  }

  /**
   * Translates the given message based on a count.
   */
  function tChoice(key: string, number: number, replacements: ReplacementsInterface = {}): string {
    const message = t(key, replacements);
    const locale = isLocale(options.locale) ? options.locale : options.fallbackLocale;

    return replacer(pluralization(message, number, locale), {
      ...replacements,
      count: number.toString(),
    });
  }

  /**
   * Set locale.
   */
  function setLocale(locale: string) {
    if (!isServer) {
      // When setting the HTML lang attribute, hyphen must be use instead of underscore.
      document.documentElement.setAttribute('lang', locale.replace('_', '-'));
    }

    setOptions((prevState) => ({
      ...options,
      locale,
      prevLocale: prevState.locale,
    }));
  }

  /**
   * Current locale.
   */
  function currentLocale(): string {
    return options.locale || options.fallbackLocale;
  }

  return createElement(
    Context.Provider,
    {
      value: {
        t,
        tChoice,
        loading,
        isLocale,
        getLocales,
        currentLocale,
        setLocale,
      },
    },
    children,
  );
}
