# Contributing Translations

Thank you for your interest in contributing translations to the RetroAchievements website! By helping translate our platform, you make it more accessible to a wider audience. This guide will walk you through the steps to add or update translations in the RAWeb repository.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Translation Guidelines](#translation-guidelines)
  - [File Structure](#file-structure)
  - [Maintaining Keys](#maintaining-keys)
  - [Handling Placeholders](#handling-placeholders)
- [Submitting Your Changes](#submitting-your-changes)
- [Review Process](#review-process)
- [Deleting Your Branch](#deleting-your-branch)

## Prerequisites

Before you begin, ensure you have the following:

- **GitHub Account:** You'll need to fork the RAWeb repository and submit pull requests.
- **Basic Git Knowledge:** Familiarity with forking repositories, creating branches, committing changes, and opening pull requests.
- **JSON Editing Skills:** Ability to read and edit JSON files without introducing syntax errors.
- **Language Proficiency:** Proficiency in the target language to ensure accurate and meaningful translations.

## Getting Started

1. **Fork the RAWeb Repository**

- Navigate to the [RAWeb GitHub repository](https://github.com/RetroAchievements/RAWeb).
- Click the **Fork** button at the top-right corner to create a personal copy of the repository associated with your GitHub account.

2. **Clone Your Fork:**

```shell
git clone https://github.com/your-username/RAWeb.git
cd RAWeb
```

3. **Create a New Branch:**

It's a best practice to create a separate branch for your translation work.

```shell
git checkout -b translate/pt_BR
```

## Translation Guidelines

### File Structure

All translations are stored in the _lang_ directory as JSON files. Each language has its own file, named using the [IETF language tag](https://en.wikipedia.org/wiki/IETF_language_tag) format, such as _en_US.json_ for American English or _pt_BR.json_ for Brazilian Portuguese.

**Example Path:**

```
RAWeb/
└── lang/
    ├── en_US.json
    └── pt_BR.json
```

### Maintaining Keys

- **Do Not Alter Keys:** Each entry in the JSON file consists of a key-value pair. **Only translate the values.** The keys are used by the application to reference the correct text and must remain unchanged.

```json
{
  ":count posts in the last 24 hours": ":count posts nas últimas 24 horas"
}
```

- **Consistent Formatting:** Ensure that the structure and formatting (such as indentation) of the JSON file remains consistent with the original.

### Handling Placeholders

Some translation strings contain placeholders (eg: `:count`, `:total`, `:gameTitle`) or special syntax used by libraries like React (eg: `<0>here</0>`). These placeholders are dynamically replaced by the application at runtime.

- **Keep Placeholders Intact:** Do not translate or modify placeholders. Ensure they remain exactly as they are in the original key.
* **For variables:** Strings like `:count`, `:total`, or `:gameTitle` should be kept as-is.
* **For HTML-like tags:** Tags like `<0>`, `</0>`, etc, must remain intact and should be placed appropriately in the translated sentence.

**Examples:**

* For variables:
```json
{
  "Next :count": "Próximo :count"
}
```
* For tags:
```json
{
  "Click <0>here</0> to visit the page.": "Clique <0>aqui</0> para visitar a página."
}
```

- **Proper Placement:** Ensure placeholders are positioned correctly within the translated string to maintain the intended meaning. You can move placeholders as appropriate for your target language.

## Submitting Your Changes

1. **Locate the Correct JSON File:**

Navigate to the _lang_ directory and open the JSON file corresponding to your target language. If the file does not exist, you can create a new one following the existing naming convention.

**Example:**

```
lang/
└── pt_BR.json
```

2. **Translate the Values:**

- Open the JSON file in a text editor or an IDE.
- Translate each value while keeping the keys unchanged.
- Ensure that the JSON syntax remains valid (commas, quotes, brackets).

3. **Validate JSON Syntax:**

Use a JSON validator to ensure there are no syntax errors. Tools like [JSONLint](https://jsonlint.com/) can be helpful.

4. **Commit Your Changes:**

```shell
git add .
git commit -m "chore: update translations for pt_BR"
```

5. **Push to Your Fork:**

```shell
git push --set-upstream origin translate/pt_BR
```

6. **Open a Pull Request:**

- Navigate to the [RAWeb repository](https://github.com/RetroAchievements/RAWeb) repository on GitHub.
- Click the **Compare & pull request** button.
- Provide a clear title and description for your pull request.
- Submit the pull request for review.

## Review Process

Once your pull request is submitted:

1. **Automated Checks:**

The repository's CI/CD pipeline will run automated tests to ensure there are no syntax errors in your JSON files.

2. **Manual Review:**

A maintainer will review your translations for adherence to these guidelines.

3. **Feedback:**

You may receive feedback or requests for revisions. Address any feedback promptly to facilitate merging your changes.

4. **Merging:**

Once approved, your translations will be merged into the `master` branch and deployed with the next site release.

## Deleting Your Branch

After you pull request is merged, it's good practice to delete your working branch to keep your fork clean. GitHub makes this easy:

1. Navigate to the merged pull request page, eg: `https://github.com/RetroAchievements/RAWeb/pull/1234`.
2. Look for the "Delete Branch" button near the bottom of the page.
3. Click the button to remove the branch from your fork.

For future contributions, always create a new branch for each set of changes. This helps keep your work organized and makes it easier for maintainers to review your contributions.
