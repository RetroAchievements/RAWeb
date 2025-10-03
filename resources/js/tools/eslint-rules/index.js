import {
  rule as enforceTypeScriptInAppCode,
  RULE_NAME as ENFORCE_TYPESCRIPT_IN_APP_CODE,
} from './enforce-typescript-in-app-code.js';
import {
  rule as noCrossBoundaryImports,
  RULE_NAME as NO_CROSS_BOUNDARY_IMPORTS,
} from './no-cross-boundary-imports.js';

export default {
  rules: {
    [ENFORCE_TYPESCRIPT_IN_APP_CODE]: enforceTypeScriptInAppCode,
    [NO_CROSS_BOUNDARY_IMPORTS]: noCrossBoundaryImports,
  },
};
