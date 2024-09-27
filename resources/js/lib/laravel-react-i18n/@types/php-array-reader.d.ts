/**
 * Declaration patch for module `php-array-reader`
 *
 * (fromString)
 */
declare module 'php-array-reader' {
  export const fromString: (phpString: string) => object;
}
