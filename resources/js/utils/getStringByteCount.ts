// Some characters, such as "á, ã, â, ü, ç, ñ, à, Á, Ã, Â, Ü, Ç, Ñ, À" or
// emojis, are actually more than 1 character. We need to count those properly
// when computing if the user is getting close to the field's max length.
export function getStringByteCount(value: string) {
  return encodeURI(value).split(/%..|./).length - 1;
}
