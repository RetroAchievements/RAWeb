/**
 * Using this during SSR or any code path invoked by
 * SSR will almost certainly cause a hydration error.
 *
 * TODO send assetsUrl through HandleInertiaRequests
 */
export function dangerouslyGetClientSideAssetUrl(uri: string): string {
  const assetsUrl = getConfigValue('assetsUrl') ?? window.assetUrl;

  return `${assetsUrl}${uri}`;
}

function getConfigValue(key: string) {
  return window?.cfg?.[key];
}
