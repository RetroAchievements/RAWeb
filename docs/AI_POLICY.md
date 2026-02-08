> [!NOTE]
> This policy is mostly a copy of the
[AI policy](https://github.com/ghostty-org/ghostty/blob/01ea3744c59af4d973e96c5cce2fe8d4aa485e59/AI_POLICY.md) from the Ghostty project,
with slight modifications to fit RAWeb.

# AI/LLM Contribution Policy

AI tools like Claude Code, Cursor, and GitHub Copilot are legitimate development aids, and we welcome contributors who use them responsibly. This policy exists not because we oppose AI, but because low-effort, AI-generated contributions place an unfair burden on volunteer maintainers. The tools are not the problem. Careless use of them is.

These rules apply to all outside contributions to RAWeb. Maintainers are exempt from this policy and may use AI tools at their discretion.

## Disclosure

**All AI usage must be disclosed.** State the tool you used (eg: Claude Code, Cursor, Copilot) and the extent of its involvement. A one-line note in your PR description is sufficient.

## Code Contributions

You are responsible for every line of code you submit.

- **You must understand what you are submitting.** If you cannot explain the changes in your own words during review, the PR will be closed.

- **You must test your changes.** Code must build, pass linting (`composer fix`, `pnpm lint:fix`), pass static analysis (`composer analyse`), and pass the test suite. Do not submit code for areas of the application you have not manually verified. All new behavior should also be verified by new test cases. See [CONTRIBUTING.md](CONTRIBUTING.md) for the full testing and code style requirements.

- **Contributions must be focused.** One PR, one purpose. If the AI touched unrelated files, refactored things you didn't ask for, or added unnecessary comments and abstractions, clean it up before submitting. Reviewers should not have to separate signal from AI noise.

- **You must handle review feedback yourself.** Pasting reviewer comments into an AI and submitting whatever comes back is not acceptable. You need to understand the feedback, engage in discussion when you disagree, and make intentional changes in response.

## Issues, Discussions, and PR Descriptions

AI may be used to help draft written communication, but the final result must be reviewed and edited by you before posting. Walls of verbose, obviously AI-generated text waste everyone's time. Be concise. Say what changed, why it changed, and how you tested it. Use your own words and your own understanding.

## Enforcement

Reviewers have final discretion. If a contribution appears to be unreviewed AI output — through any combination of unfocused changes, failure to address feedback, inability to explain the code, or general low quality — it will be rejected.

This policy is designed to help us maintain the sustainable development model that enables this project to work. We review all contributions the same way regardless of how they were produced. Meet the bar and your PR is welcome.
