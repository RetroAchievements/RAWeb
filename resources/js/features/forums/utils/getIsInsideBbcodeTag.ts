export function getIsInsideBbcodeTag(index: number, text: string, tagName: string): boolean {
  const beforeText = text.slice(0, index);
  const openTagPattern = new RegExp(`\\[${tagName}[\\]=]`, 'g');
  const closeTagPattern = new RegExp(`\\[\\/${tagName}\\]`, 'g');

  const openMatches = [...beforeText.matchAll(openTagPattern)];
  const closeMatches = [...beforeText.matchAll(closeTagPattern)];

  return openMatches.length > closeMatches.length;
}
