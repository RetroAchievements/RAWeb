export const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text).then(() => {
    if (window.showStatusSuccess) {
      window.showStatusSuccess('Copied!');
    }
  });
};
