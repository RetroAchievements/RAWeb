import fs from 'fs';

import { dirnameSanitize } from './helper';

export default {
  /**
   *
   * @param dirname
   */
  getJsonLocale: (dirname: string): string[] => {
    dirname = dirnameSanitize(dirname);

    if (!fs.existsSync(dirname)) {
      // console.error(`No such directory: '${dirname}'`);
      return [];
    }

    return fs
      .readdirSync(dirname)
      .filter((basename) => fs.statSync(dirname + basename).isFile())
      .filter((basename) => !/^php_/.test(basename))
      .map((basename) => basename.replace('.json', ''))
      .sort();
  },
  /**
   *
   * @param dirname
   */
  getPhpLocale: (dirname: string): string[] => {
    dirname = dirnameSanitize(dirname);

    if (!fs.existsSync(dirname)) {
      // console.error(`No such directory: '${dirname}'`);
      return [];
    }

    return fs
      .readdirSync(dirname)
      .filter((basename) => fs.statSync(dirname + basename).isDirectory())
      .filter(
        (folder) =>
          fs.readdirSync(dirname + folder).filter((basename) => /\.php$/.test(basename)).length > 0,
      )
      .sort();
  },
};
