# Contributing Translations

Thank you for your interest in contributing translations to the RetroAchievements website! By helping translate our platform, you make it more accessible to a wider audience. We use Crowdin as our platform for managing website translations. This guide will walk you through the process of translating content using Crowdin.

NOTE: The website does not support RTL (right-to-left) languages at this time.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Request a New Language](#request-a-new-language)
- [Handling Special Elements](#handling-special-elements)
- [Review Process](#review-process)
- [Getting Help](#getting-help)
- [Tips for New Translators](#tips-for-new-translators)

## Prerequisites

Before you begin, ensure you have the following:

- **CrowdIn Account:** Create a free account at [crowdin.com](https://accounts.crowdin.com/login?continue=%2Fproject%2Fretroachievements).
- **Language Proficiency:** Proficiency in the target language to ensure accurate and meaningful translations.

## Getting Started

1. [Create a Crowdin account](https://accounts.crowdin.com/login?continue=%2Fproject%2Fretroachievements) if you haven't already done so.
2. While logged in to Crowdin, visit [the RetroAchievements Crowdin project page](https://crowdin.com/project/retroachievements).
3. Click the green "Join" button at the top right of the page to join the project as a translator.
4. Select the language you want to translate and click on the "en_US.json" link that appears.

## Request a New Language

On the main RetroAchievements Crowdin project page, you will see a "Request New Language" button. You can click this button to select a language you believe we should support on the website. If approved, the language will be added to the project so you can begin your translation work.

## Handling Special Elements

Some translation strings contain special elements that must be handled carefully:

1. **Variables**

   - Examples: `{{count, number}}`, `{{total}}`, `{{gameTitle}}`
   - Do not translate these placeholders
   - Keep them exactly as they appear in the original text

   ```
   Original: "Next {{count, number}}"
   Translation: "Suivant {{count, number}}"
   ```

2. **HTML-like Tags**
   - Examples: `<1>text</1>`, `<2>link</2>`
   - Keep tags intact and in the correct order
   ```
   Original: "Click <1>here</1> to visit the page"
   Translation: "Cliquez <1>ici</1> pour visiter la page"
   ```

## Review Process

1. **Proofreading**

- Other translators can review and vote on translations.
- You can review and vote on other translators' translations.
- Be open to constructive feedback you may receive on your translations.

2. **Approval**

- Approved translations will be synced to the RAWeb repository automatically every week.
- Changes typically go live with the next site deployment.

## Getting Help

- Use the comments feature in Crowdin to discuss specific translations and text.
- Join the [RetroAchievements Discord](https://discord.gg/invite/retroachievements) and use the [#localization](https://discord.com/channels/310192285306454017/1299083037908406402) channel to be automatically notified when there are new things to translate.

## Tips for New Translators

1. **Start Small**

- Begin with shorter, simpler strings.
- Gradually work up to more complex content.
- Learn from existing translations.

2. **Stay Active**

- Regular small contributions are better than occasional large ones.
- Keep an eye on new content that needs translation. Use the [#localization](https://discord.com/channels/310192285306454017/1299083037908406402) channel in our Discord to be notified of when there is new stuff to translate.

3. **Use Available Tools**

- Familiarize yourself with Crowdin's features.
- Use Crowdin's translation memory and glossary.
- Take advantage of Crowdin's automated quality checks.

Thank you for helping make RetroAchievements accessible to more people around the world! Your contributions help build a stronger, more inclusive community.
