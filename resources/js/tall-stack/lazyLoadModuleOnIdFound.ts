/**
 * Dynamically imports a JavaScript module when an element with the specified `id` is found in the DOM.
 * The imported module will be attached to the window object with the given `moduleName`.
 *
 * @param {Object} options - Configuration options for the lazy loading process.
 * @param {string} options.elementId - The `id` attribute value of the DOM element that triggers the import when found.
 * @param {string} options.codeFileName - The name of the file in the dynamic folder to lazy load.
 * @param {string} options.moduleNameToAttachToWindow - The name under which the imported module will be attached to the window object.
 */
export const lazyLoadModuleOnIdFound = (options: {
  elementId: string;
  codeFileName: string;
  moduleNameToAttachToWindow: string;
}) => {
  const { elementId, codeFileName, moduleNameToAttachToWindow } = options;

  document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector(`#${elementId}`)) {
      import(`./dynamic/${codeFileName}.ts`).then((lazyLoadedModule) => {
        (window as unknown as Record<string, unknown>)[moduleNameToAttachToWindow] =
          lazyLoadedModule;
      });
    }
  });
};
