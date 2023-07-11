export const config = (key: string) => window?.cfg?.[key];

export const asset = (uri: string) => {
  const assetsUrl = config('assetsUrl') ?? (window as any).assetUrl;
  return `${assetsUrl}${uri}`;
};
