# Contribution Guide

Please read and understand the contribution guide before creating an issue or pull request.

## Viability

When requesting or submitting new features, first consider whether it might be useful to others. Think about whether
your feature is likely to be used by other users of the project.

## Procedure

Before filing an issue:

- Attempt to replicate the problem, to ensure that it wasn't a coincidental incident.
- Check to make sure your feature suggestion isn't already present within the project.
- Check the pull requests tab to ensure that the bug doesn't have a fix in progress.
- Check the pull requests tab to ensure that the feature isn't already in progress.

Before submitting a pull request:

- Check the codebase to ensure that your feature doesn't already exist.
- Check the pull requests to ensure that another person hasn't already submitted the feature or fix.

## Requirements

- Follow **[PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)**. Run `composer fix` to fix most code style issues automatically.

- Follow **[Airbnb JavaScript Style Guide](https://github.com/airbnb/javascript)**. Run `npm run fix` to fix most code style issues automatically.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](https://semver.org/). Randomly breaking public APIs is not an option.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](https://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

- **Use [Tailwind CSS](https://tailwindcss.com/docs) for styling whenever possible** - We try to avoid inline CSS or custom styling wherever possible. The Tailwind CSS framework covers most cases.

## Testing, Code Style (CS) & Static Analysis

**PHPUnit** is used for testing. Write feature and/or unit tests where applicable.  

**PHP CS Fixer** comes as a dev dependency. Use it before you commit. See the [README](README.md) 

**PHPStan** is used for static analysis. Make sure to run it and that your code follows its advice.

## APIs, backwards compatibility & deprecation

APIs have to accommodate to old clients' requirements as upgrade paths are very slow for some of them.

Deprecations have to be communicated to users upfront.

## Implementation Details

**Authorization**

Define abilities in policies.

Authorize explicitly in controllers/actions. There is a variety of custom abilities, we don't want to end up in a hit or miss scenario.
Do not use authorizeResource() in Controllers' constructor.

Use FormRequests in controllers for validation. Ignore Form Requests' authorization capabilities (should be done in controllers/actions instead). 

**Controllers, actions, Reusable action classes**

Controllers should be slim, start with an ability authorization, even if it's an "always-public" route.
 
In case of complex procedures, delegate to a reusable Action that can be injected from the container.
If too much is going on in a controller's action it might be a good candidate to be extracted into a dedicated action.
Especially if it's something that can be reused.

**Frontend assets**

This is not a Single Page Application.

JavaScript kept vanilla and to a minimum - most dynamic features use Laravel LiveWire and/or Alpine.js.

**Routes**

Middleware gets applied for basic abilities (guest, auth, can:accessManagementTools) - specific abilities should be authorized in controller actions. 

Slugs are kebab-cased and _appended_ to model IDs (which in turn can be hashed/masked).
This allows keeping /create, /edit, etc routes without having to deal with conflicts.

Route keys for route model binding should be validated in the respective RouteServiceProvider. 
