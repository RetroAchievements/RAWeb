/**
 * Dynamically imports a JavaScript module when an element with the specified `id` is found in the DOM.
 * The imported module will be attached to the window object with the given `moduleName`.
 *
 * @param {Object} options - Configuration options for the lazy loading process.
 * @param {string} options.elementId - The `id` attribute value of the DOM element that triggers the import when found.
 * @param {string} options.codePath - The relative path to the JavaScript module that should be imported.
 * @param {string} options.moduleName - The name under which the imported module will be attached to the window object.
 */
export const lazyLoadModuleOnIdFound = (options: {
  elementId: string;
  codePath: string;
  moduleName: string;
}) => {
  const { elementId, codePath, moduleName } = options;

  document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector(`#${elementId}`)) {
      import(codePath).then((lazyLoadedModule) => {
        (window as any)[moduleName] = lazyLoadedModule;
      });
    }
  });
};
