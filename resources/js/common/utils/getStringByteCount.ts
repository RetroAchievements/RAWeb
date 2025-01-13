// Some characters, such as "á, ã, â, ü, ç, ñ, à, Á, Ã, Â, Ü, Ç, Ñ, À" or
// emojis, are actually more than 1 character. We need to count those properly
// when computing if the user is getting close to the field's max length.
export function getStringByteCount(value: string) {
  // Replace newline characters with a 2-character placeholder
  const valueWithReplacedNewlines = value.replace(/\n/g, '--');

  return encodeURI(valueWithReplacedNewlines).split(/%..|./).length - 1;
}
