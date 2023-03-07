export const config = (key: string) => window?.cfg?.[key];

export const asset = (uri: string) => `${config('assetsUrl')}${uri}`;

export const clipboard = (text: string) => {
  navigator.clipboard.writeText(text).then(() => {
    if (window.showStatusSuccess) {
      window.showStatusSuccess('Copied!');
    }
  });
};
