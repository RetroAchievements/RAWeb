/**
 * Avoid exposing or overflowing long credentials while keeping them recognizable.
 */
export function safeFormatCredential(credential: string): string {
  if (credential.length <= 12) {
    return credential;
  }

  return `${credential.slice(0, 6)}...${credential.slice(-6)}`;
}
