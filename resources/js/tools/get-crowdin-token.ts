export function getCrowdinToken(): string {
  const personalToken = process.env.CROWDIN_PERSONAL_TOKEN;
  if (!personalToken) {
    throw new Error(
      '\n' +
        '🔑 Missing Crowdin API Token\n' +
        'Please set the CROWDIN_PERSONAL_TOKEN environment variable.\n' +
        'You can get this from: https://crowdin.com/settings#api-key\n',
    );
  }

  return personalToken;
}
