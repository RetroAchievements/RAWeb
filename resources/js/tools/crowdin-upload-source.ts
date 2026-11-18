import { CrowdinError, SourceFiles, UploadStorage } from '@crowdin/crowdin-api-client';
import { readFileSync } from 'fs';
import { join } from 'path';

import { CROWDIN } from './crowdin-project';
import { getCrowdinToken } from './get-crowdin-token';

async function uploadSourceFile() {
  const token = getCrowdinToken();

  try {
    console.log('📁 Reading RAWeb en_US.json source file...');
    const sourcePath = join(CROWDIN.LANG_DIR, CROWDIN.SOURCE_FILE);
    const sourceContent = readFileSync(sourcePath, 'utf-8');

    // Never attempt to upload invalid JSON.
    try {
      JSON.parse(sourceContent);
    } catch {
      throw new Error('Invalid JSON in en_US.json file.');
    }

    console.log(`✅ Validated RAWeb en_US.json, current length is ${sourceContent.length}.`);

    console.log('🔍 Getting upload destination information from Crowdin...');
    const sourceFilesApi = new SourceFiles({ token });
    const uploadStorageApi = new UploadStorage({ token });

    // List source files to find our target file ID.
    const files = await sourceFilesApi.listProjectFiles(CROWDIN.PROJECT_ID);
    const sourceFile = files.data.find((file) => file.data.name === CROWDIN.SOURCE_FILE);

    if (!sourceFile) {
      throw new Error('Source file not found in Crowdin project.');
    }

    console.log('📤 Uploading source file...');

    // First, add the content to storage.
    const storageResponse = await uploadStorageApi.addStorage(CROWDIN.SOURCE_FILE, sourceContent);

    // Then, update the file with the storage ID.
    await sourceFilesApi.updateOrRestoreFile(CROWDIN.PROJECT_ID, sourceFile.data.id, {
      storageId: storageResponse.data.id,
    });

    console.log('\n✨ Source file has been successfully uploaded to Crowdin!');
  } catch (err) {
    if (err instanceof CrowdinError) {
      console.error('❌ Crowdin API Error:', err.message);
    }

    throw err;
  }
}

uploadSourceFile();
