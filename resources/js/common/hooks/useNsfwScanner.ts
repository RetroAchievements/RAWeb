import type { NSFWJS } from 'nsfwjs';
import { useEffect } from 'react';

/**
 * How strongly the model needs to feel that an image
 * contains a certain type of NSFW content for the content
 * to be flagged by client-side validation. This value
 * ranges from 0.0 to 1.0 in terms of strictness.
 */
const NSFW_THRESHOLD = 0.7;

interface NsfwScanResult {
  isNsfw: boolean;

  scores?: Record<string, number>;
}

let modelPromise: Promise<NSFWJS> | null = null;

/**
 * Kick off model loading. Safe to call multiple times --
 * only the first call triggers a load.
 */
function loadModel(): void {
  if (modelPromise) {
    return;
  }

  modelPromise = import('@tensorflow/tfjs')
    .then(() => import('nsfwjs'))
    .then((nsfwjs) => nsfwjs.load())
    .catch((error) => {
      // Reset so the next call retries instead of caching the failure.
      modelPromise = null;
      throw error;
    });
}

async function performScan(file: File): Promise<NsfwScanResult> {
  let objectUrl: string | null = null;

  try {
    loadModel();

    const model = await modelPromise!;

    objectUrl = URL.createObjectURL(file);

    const image = await new Promise<HTMLImageElement>((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = (error) => reject(error);
      img.src = objectUrl!;
    });

    const predictions = await model.classify(image);

    const scores: Record<string, number> = {};
    for (const prediction of predictions) {
      scores[prediction.className] = prediction.probability;
    }

    const isNsfw =
      (scores['Porn'] ?? 0) >= NSFW_THRESHOLD || (scores['Hentai'] ?? 0) >= NSFW_THRESHOLD;

    return { isNsfw, scores };
  } catch {
    // If for some reason we can't do the client-side NSFW scan, bail.
    return { isNsfw: true };
  } finally {
    if (objectUrl) {
      URL.revokeObjectURL(objectUrl);
    }
  }
}

export function useNsfwScanner(options?: Partial<{ isEnabled: boolean }>) {
  const isEnabled = options?.isEnabled ?? true;

  useEffect(() => {
    if (!isEnabled) {
      return;
    }

    loadModel();
  }, [isEnabled]);

  const scanImage = async (file: File): Promise<NsfwScanResult> => {
    if (!isEnabled) {
      return { isNsfw: false };
    }

    return performScan(file);
  };

  return { scanImage };
}
