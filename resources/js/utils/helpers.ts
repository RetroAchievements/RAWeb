export const config = (key: string) => window?.cfg?.[key];

export const asset = (uri: string) => {
  const assetsUrl = config('assetsUrl') ?? (window as any).assetUrl;
  return `${assetsUrl}${uri}`;
};

export const clipboard = (text: string) => {
  navigator.clipboard.writeText(text).then(() => {
    if (window.showStatusSuccess) {
      window.showStatusSuccess('Copied!');
    }
  });
};
