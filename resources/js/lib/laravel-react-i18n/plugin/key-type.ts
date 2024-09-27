import fs from 'fs';
import path from 'path';

/**
 *
 * @param dirname
 * @param basename
 */
export function convertToKeyType(dirname: string, basename: string): string {
  const result = fs.readFileSync(`${dirname + path.sep + basename}.json`, 'utf8');
  const obj = Object.entries(JSON.parse(result));

  let str = '';
  for (const [index, [key]] of obj.entries()) {
    // Escaping a key
    const escKey = key.replace(/[!@#$%^&*()+=\-[\]\\';,/{}|":<>?~_]/g, '\\$&');

    str = obj.length === 1 || obj.length - 1 === index ? `${str}'${escKey}'` : `${str}'${escKey}'|`;
  }

  return str;
}

/**
 *
 * @param keys
 * @param dirname
 */
export function saveKeyTypeToFile(keys: string, dirname = 'resources/js') {
  const sanitizeDirname = dirname.replace(/[\\/]$/, '') + path.sep;
  const data = `export type I18nKeyType = ${keys};`.replace(/[\r\n]+/g, '');

  fs.writeFileSync(`${sanitizeDirname}LaravelReactI18n.types.ts`, data);
}
