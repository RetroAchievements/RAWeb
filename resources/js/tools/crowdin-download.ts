import { CrowdinError, ProjectsGroups, Translations } from '@crowdin/crowdin-api-client';
import { execSync } from 'child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'fs';
import { tmpdir } from 'os';
import { join } from 'path';

/**
 * RetroAchievements Translation Sync Script
 * ========================================
 *
 * This script downloads translations from Crowdin and places them in our codebase.
 *
 * TO ADD A NEW LANGUAGE:
 * 1. First add the language in Crowdin: https://crowdin.com/project/retroachievements
 * 2. Then add it to the LANGUAGES list below using this format:
 *    "standard_code": "crowdin_folder_name"
 *
 * Examples of standard codes vs crowdin folder names:
 *   "de_DE": "de"      -> German
 *   "pt_BR": "pt-BR"   -> Brazilian Portuguese
 *   "ru_RU": "ru"      -> Russian
 *
 * Notes:
 * - The "standard_code" (left side) is what appears in our /lang folder (pt_BR.json).
 * - The "crowdin_folder_name" (right side) is the folder name in the Crowdin zip.
 * - You can see the "crowdin_folder_name" in the Crowdin web interface.
 */

// ============================================================================
// CONFIGURATION - THIS IS THE PART YOU MIGHT NEED TO MODIFY
// ============================================================================

/**
 * List of supported languages and their Crowdin folder names.
 * ADD NEW LANGUAGES HERE!
 */
const LANGUAGES = {
  de_DE: 'de',
  en_GB: 'en-GB',
  es_ES: 'es-ES',
  fr_FR: 'fr',
  pl_PL: 'pl',
  ru_RU: 'ru',
  sv_SE: 'sv-SE',
  pt_BR: 'pt-BR',
  vi_VN: 'vi-VN',
} as const;

// ============================================================================
// IMPLEMENTATION - YOU PROBABLY DON'T NEED TO MODIFY ANYTHING BELOW THIS LINE
// ============================================================================

const CROWDIN = {
  PROJECT_ID: 734399,
  SOURCE_FILE: 'en_US.json',
  OUTPUT_DIR: 'lang',
} as const;

type SupportedLocale = keyof typeof LANGUAGES;

function getCrowdinDirectoryName(code: string): string {
  if (code in LANGUAGES) {
    return LANGUAGES[code as SupportedLocale];
  }

  console.warn(
    '\n' +
      '⚠️  WARNING: Unknown Language Found\n' +
      `The language "${code}" was found in Crowdin but isn't in our LANGUAGES list.\n` +
      'To fix this:\n' +
      '1. Look up its folder name in Crowdin\n' +
      `2. Add it to the LANGUAGES object: "${code}": "folder-name"\n`,
  );

  return code.toLowerCase();
}

async function downloadTranslations() {
  const token = process.env.CROWDIN_PERSONAL_TOKEN;
  if (!token) {
    throw new Error(
      '\n' +
        '🔑 Missing Crowdin API Token\n' +
        'Please set the CROWDIN_PERSONAL_TOKEN environment variable.\n' +
        'You can get this from: https://crowdin.com/settings#api-key\n',
    );
  }

  try {
    console.log('🌍 Fetching target locales...');
    const projectsApi = new ProjectsGroups({ token });
    const project = await projectsApi.getProject(CROWDIN.PROJECT_ID);
    const targetLanguages = project.data.targetLanguages;

    console.log('🏗️  Building translations...');
    const translationsApi = new Translations({ token });
    const build = await translationsApi.buildProject(CROWDIN.PROJECT_ID);

    let buildStatus = await translationsApi.checkBuildStatus(CROWDIN.PROJECT_ID, build.data.id);
    while (buildStatus.data.status === 'inProgress') {
      await new Promise((resolve) => setTimeout(resolve, 1000));
      buildStatus = await translationsApi.checkBuildStatus(CROWDIN.PROJECT_ID, build.data.id);
    }

    console.log('📦 Downloading translation package...');
    const downloadLink = await translationsApi.downloadTranslations(
      CROWDIN.PROJECT_ID,
      build.data.id,
    );
    const response = await fetch(downloadLink.data.url);

    if (!response.ok) {
      throw new Error(`Failed to download translations: ${response.status} ${response.statusText}`);
    }

    const tempDir = mkdtempSync(join(tmpdir(), 'crowdin-'));
    const zipFile = join(tempDir, 'translations.zip');

    const buffer = await response.arrayBuffer();
    writeFileSync(zipFile, Buffer.from(buffer));
    execSync(`unzip -o "${zipFile}" -d "${tempDir}"`);

    console.log('\n📝 Processing each language:');
    for (const lang of targetLanguages) {
      const code = lang.locale.replace('-', '_');
      if (code === 'en_US') {
        continue;
      }

      const crowdinDir = getCrowdinDirectoryName(code);
      const sourcePath = join(tempDir, crowdinDir, CROWDIN.SOURCE_FILE);
      const targetPath = join(CROWDIN.OUTPUT_DIR, `${code}.json`);

      try {
        const content = readFileSync(sourcePath, 'utf-8');
        const translation = JSON.parse(content);
        writeFileSync(targetPath, JSON.stringify(translation, null, 4));

        console.log(`✓ ${code}`);
      } catch (err) {
        console.error(`✗ Error processing ${code}:`, err);
      }
    }

    rmSync(tempDir, { recursive: true, force: true });
    console.log('\n✨ All done! Translations have been updated.');
  } catch (err) {
    if (err instanceof CrowdinError) {
      console.error('❌ Crowdin API Error:', err.message);
    }

    throw err;
  }
}

downloadTranslations();
