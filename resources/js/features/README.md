Features should not import from other features. Doing so can create a circular dependency which bloats the JS bundle and can be difficult to untangle.

If two different features need to share the same component, util, model, etc, consider lifting it up to "@/common".