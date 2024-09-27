import fs from 'fs';
import path from 'path';
import { fromString } from 'php-array-reader';

import { dirnameSanitize } from './helper';

/**
 *
 * Parse array of PHP file.
 *
 * @param dirname
 */
export default function parser(dirname: string): { path: string; basename: string }[] {
  dirname = dirnameSanitize(dirname);

  if (!fs.existsSync(dirname)) {
    // console.error(`No such directory: '${dirname}'`);
    return [];
  }

  return fs
    .readdirSync(dirname)
    .filter((basename) => fs.statSync(dirname + basename).isDirectory())
    .sort()
    .map((locale) => ({
      locale,
      trans: convertToDottedKey(getObjectTranslation(dirname + locale)),
    }))
    .filter(({ trans }) => Object.keys(trans).length > 0)
    .map(({ locale, trans }) => {
      const basename = `php_${locale}.json`;
      fs.writeFileSync(dirname + basename, JSON.stringify(trans));

      return { basename, path: dirname + basename };
    });
}

/**
 *
 * @param source
 * @param target
 * @param keys
 */
function convertToDottedKey(
  source: object,
  target: { [kes: string]: string } = {},
  keys: string[] = [],
): { [kes: string]: string } {
  for (const [key, value] of Object.entries(source)) {
    if (Object.prototype.toString.call(value) === '[object Object]') {
      convertToDottedKey(value, target, keys.concat(key));
      continue;
    }

    target[keys.concat(key).join('.')] = value;
  }

  return target;
}

/**
 *
 * @param dirname
 */
function getObjectTranslation(dirname: string): { [kes: string]: string } {
  return fs
    .readdirSync(dirname)
    .map((basename) => {
      const absoluteFile = dirname + path.sep + basename;
      const key = basename.replace(/\.\w+$/, '');

      return fs.statSync(absoluteFile).isDirectory()
        ? { key, val: getObjectTranslation(absoluteFile) }
        : { key, val: fromString(fs.readFileSync(absoluteFile).toString()) };
    })
    .reduce((obj, { key, val }) => ({ ...obj, [key]: val }), {});
}
