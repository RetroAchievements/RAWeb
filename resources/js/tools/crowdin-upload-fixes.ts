import type { StringTranslationsModel } from '@crowdin/crowdin-api-client';
import { ProjectsGroups, SourceStrings, StringTranslations } from '@crowdin/crowdin-api-client';
import { execFileSync } from 'child_process';
import { basename } from 'path';

import { CROWDIN } from './crowdin-project';
import { getCrowdinToken } from './get-crowdin-token';

/**
 * Every weekly sync rewrites the files in lang/.
 * This script sends the fixes reviewers apply on that sync PR back to Crowdin.
 */

const PLURAL_FORM_NAMES = [
  'zero',
  'one',
  'two',
  'few',
  'many',
  'other',
] as const satisfies readonly StringTranslationsModel.PluralCategoryName[];

interface CrowdinStringRef {
  stringId: number;
  pluralCategoryName?: StringTranslationsModel.PluralCategoryName;
}

interface ResolvedTranslation extends CrowdinStringRef {
  label: string;
  languageId: string;
  text: string;
}

function runGit(...args: string[]): string {
  return execFileSync('git', args, { encoding: 'utf-8', stdio: 'pipe' });
}

function readLangFileAtRevision(revision: string, path: string): Record<string, string> {
  return JSON.parse(runGit('show', `${revision}:${path}`));
}

/**
 * crowdin-download.yml lands the sync work as a single commit on top of master.
 */
function collectTranslationFixes() {
  const [syncBranchCommit] = runGit(
    'rev-list',
    '--reverse',
    '--first-parent',
    'HEAD',
    '^origin/master',
  ).split('\n');
  if (!syncBranchCommit) {
    return [];
  }

  const changedPaths = runGit(
    'diff',
    '--name-only',
    '--diff-filter=M',
    syncBranchCommit,
    'HEAD',
    '--',
    `${CROWDIN.LANG_DIR}/*.json`,
    `:!${CROWDIN.LANG_DIR}/${CROWDIN.SOURCE_FILE}`,
  )
    .split('\n')
    .filter((entry) => entry !== '');

  return changedPaths.flatMap((path) => {
    const syncedContent = readLangFileAtRevision(syncBranchCommit, path);

    return Object.entries(readLangFileAtRevision('HEAD', path))
      .filter(([key, text]) => syncedContent[key] !== text)
      .map(([key, text]) => ({ locale: basename(path, '.json'), key, text }));
  });
}

/**
 * Crowdin keeps i18next plurals such as "count_one" as plural forms of one string, "count".
 */
function resolveStringReference(
  key: string,
  stringIdByIdentifier: Map<string, number>,
): CrowdinStringRef | null {
  const directMatchId = stringIdByIdentifier.get(key);
  if (directMatchId) {
    return { stringId: directMatchId };
  }

  const underscoreIndex = key.lastIndexOf('_');
  if (underscoreIndex < 1) {
    return null;
  }

  const pluralForm = PLURAL_FORM_NAMES.find(
    (category) => category === key.slice(underscoreIndex + 1),
  );
  const baseStringId = stringIdByIdentifier.get(key.slice(0, underscoreIndex));
  if (pluralForm && baseStringId) {
    return { stringId: baseStringId, pluralCategoryName: pluralForm };
  }

  return null;
}

async function findOrCreateTranslation(
  api: StringTranslations,
  fix: ResolvedTranslation,
): Promise<number> {
  const currentTranslations = await api
    .withFetchAll()
    .listStringTranslations(CROWDIN.PROJECT_ID, fix.stringId, fix.languageId);
  const matchingTranslation = currentTranslations.data.find(
    ({ data }) =>
      data.text === fix.text &&
      (!fix.pluralCategoryName || data.pluralCategoryName === fix.pluralCategoryName),
  );
  if (matchingTranslation) {
    return matchingTranslation.data.id;
  }

  const createdTranslation = await api.addTranslation(CROWDIN.PROJECT_ID, {
    stringId: fix.stringId,
    languageId: fix.languageId,
    text: fix.text,
    pluralCategoryName: fix.pluralCategoryName,
  });

  return createdTranslation.data.id;
}

async function ensureTranslationApproved(api: StringTranslations, translationId: number) {
  const currentApprovals = await api.listTranslationApprovals(CROWDIN.PROJECT_ID, {
    translationId,
  });
  if (currentApprovals.data.length === 0) {
    await api.addApproval(CROWDIN.PROJECT_ID, { translationId });
  }
}

async function uploadTranslationFixes() {
  const pendingFixes = collectTranslationFixes();
  console.log(`🔍 Found ${pendingFixes.length} fix(es) on the sync branch.`);
  if (pendingFixes.length === 0) {
    return;
  }

  const token = getCrowdinToken();
  const crowdinProject = await new ProjectsGroups({ token }).getProject(CROWDIN.PROJECT_ID);
  const languageIdByLocale = new Map(
    crowdinProject.data.targetLanguages.map((language) => [
      language.locale.replace('-', '_'),
      language.id,
    ]),
  );
  const sourceStrings = await new SourceStrings({ token })
    .withFetchAll()
    .listProjectStrings(CROWDIN.PROJECT_ID);
  const stringIdByIdentifier = new Map(
    sourceStrings.data.map(({ data }) => [data.identifier, data.id]),
  );

  // Every fix is resolved up front so writes can't happen partway through any failure.
  const resolvedTranslations = pendingFixes.map(({ locale, key, text }): ResolvedTranslation => {
    const displayLabel = `${locale} "${key}"`;
    const crowdinLanguageId = languageIdByLocale.get(locale);
    const stringRef = resolveStringReference(key, stringIdByIdentifier);
    if (!crowdinLanguageId) {
      throw new Error(`${displayLabel}: Crowdin has no target language for ${locale}.`);
    }
    if (!stringRef) {
      throw new Error(`${displayLabel}: Crowdin has no string with this key.`);
    }

    return { label: displayLabel, languageId: crowdinLanguageId, text, ...stringRef };
  });

  const stringTranslationsApi = new StringTranslations({ token });
  for (const translation of resolvedTranslations) {
    await ensureTranslationApproved(
      stringTranslationsApi,
      await findOrCreateTranslation(stringTranslationsApi, translation),
    );
    console.log(`✓ ${translation.label}`);
  }
}

uploadTranslationFixes();
