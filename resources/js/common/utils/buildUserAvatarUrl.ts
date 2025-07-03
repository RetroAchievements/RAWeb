export function buildUserAvatarUrl(path: string) {
  const baseUrl = globalThis.mediaUrl;

  return `${baseUrl}/UserPic/${path}`;
}
