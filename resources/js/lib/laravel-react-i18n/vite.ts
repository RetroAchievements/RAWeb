import fs from 'fs';
import { createLogger } from 'vite';

import { convertToKeyType, saveKeyTypeToFile } from './plugin/key-type';
import locale from './plugin/locale';
import parser from './plugin/parser';

interface ConfigInterface {
  langDirname?: string;
  typeDestinationPath?: string;
  typeTranslationKeys?: boolean;
}

/**
 *
 */
export default function i18n(config?: ConfigInterface) {
  const langDirname = config?.langDirname ? config.langDirname : 'lang';

  const logger = createLogger('info', { prefix: '[laravel-react-i18n]' });

  let isPhpLocale = false;
  let files: { path: string; basename: string }[] = [];
  let exitHandlersBound = false;
  let jsonLocales: string[] = [];
  let phpLocales: string[] = [];

  function clean() {
    for (const file of files) fs.existsSync(file.path) && fs.unlinkSync(file.path);
    files = [];
  }

  function pushKeys(keys: string[], locales: string[]) {
    if (
      typeof process.env.VITE_LARAVEL_REACT_I18N_LOCALE !== 'undefined' &&
      locales.includes(process.env.VITE_LARAVEL_REACT_I18N_LOCALE)
    ) {
      const fileName = isPhpLocale
        ? `php_${process.env.VITE_LARAVEL_REACT_I18N_LOCALE}`
        : process.env.VITE_LARAVEL_REACT_I18N_LOCALE;
      keys.push(convertToKeyType(langDirname, fileName));
    }

    if (
      typeof process.env.VITE_LARAVEL_REACT_I18N_FALLBACK_LOCALE !== 'undefined' &&
      locales.includes(process.env.VITE_LARAVEL_REACT_I18N_FALLBACK_LOCALE) &&
      process.env.VITE_LARAVEL_REACT_I18N_LOCALE !==
        process.env.VITE_LARAVEL_REACT_I18N_FALLBACK_LOCALE
    ) {
      const fileName = isPhpLocale
        ? `php_${process.env.VITE_LARAVEL_REACT_I18N_FALLBACK_LOCALE}`
        : process.env.VITE_LARAVEL_REACT_I18N_FALLBACK_LOCALE;
      keys.push(convertToKeyType(langDirname, fileName));
    }
  }

  return {
    name: 'i18n',
    enforce: 'post',
    config() {
      const keys: string[] = [];

      // Check language directory is exists.
      if (!fs.existsSync(langDirname)) {
        const msg = [
          'Language directory is not exist, maybe you did not publish the language files with `php artisan lang:publish`.',
          'For more information please visit: https://laravel.com/docs/10.x/localization#publishing-the-language-files',
        ];

        msg.map((str) => logger.error(str, { timestamp: true }));

        return;
      }

      // JSON-file locales.
      jsonLocales = locale.getJsonLocale(langDirname);

      if (config?.typeTranslationKeys) {
        pushKeys(keys, jsonLocales);
      }

      // PHP-file locales.
      phpLocales = locale.getPhpLocale(langDirname);

      if (phpLocales.length > 0) {
        files = parser(langDirname);
        isPhpLocale = true;

        if (config?.typeTranslationKeys) {
          pushKeys(keys, phpLocales);
        }
      } else {
        const msg = [
          'Language directory not contain php translations files.',
          'For more information please visit: https://laravel.com/docs/10.x/localization#introduction',
        ];

        msg.map((str) => logger.info(str, { timestamp: true }));
      }

      if (config?.typeTranslationKeys) {
        saveKeyTypeToFile(keys.join('|'), config?.typeDestinationPath);
      }
    },
    buildEnd: clean,
    handleHotUpdate(ctx: any) {
      const keys: string[] = [];

      if (config?.typeTranslationKeys) {
        pushKeys(keys, jsonLocales);
      }

      if (isPhpLocale) {
        if (/lang\/.*\.php$/.test(ctx.file)) {
          files = parser(langDirname);
        }

        if (config?.typeTranslationKeys) {
          pushKeys(keys, phpLocales);
        }
      }

      if (config?.typeTranslationKeys) {
        saveKeyTypeToFile(keys.join('|'), config?.typeDestinationPath);
      }
    },
    configureServer() {
      if (exitHandlersBound) return;

      process.on('exit', clean);
      process.on('SIGINT', process.exit);
      process.on('SIGTERM', process.exit);
      process.on('SIGHUP', process.exit);

      exitHandlersBound = true;
    },
  };
}
