import { Head } from '@inertiajs/react';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const Contact: AppPage = () => {
  return (
    <>
      <Head title="Contact Us">
        <meta
          name="description"
          content="Get in touch with RetroAchievements teams for reporting issues, seeking assistance, or contributing to the community. Contact admins, developers, QA, and more."
        />
      </Head>

      <AppLayout.Main>
        <h1 className="mb-4">Contact Us</h1>

        <div className="grid gap-2 lg:grid-cols-2 lg:gap-4">
          <div className="flex flex-col gap-2 lg:gap-4">
            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">Admins and Moderators</h2>
                <p>
                  <a href={route('message.create', { to: 'RAdmin' })}>Send a message to RAdmin</a>{' '}
                  for:
                </p>
                <ul className="list-inside list-disc">
                  <li>Reporting offensive behavior.</li>
                  <li>Reporting copyrighted material.</li>
                  <li>Requesting to be untracked.</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">Developer Compliance</h2>
                <p>
                  <a href={route('message.create', { to: 'DevCompliance' })}>
                    Send a message to DevCompliance
                  </a>{' '}
                  for:
                </p>
                <ul className="list-inside list-disc">
                  <li>Requesting set approval or early set release.</li>
                  <li>Reporting achievements or sets with unwelcome concepts.</li>
                  <li>Reporting sets failing to cover basic progression.</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">Quality Assurance</h2>
                <p>
                  <a href={route('message.create', { to: 'QATeam' })}>Send a message to QATeam</a>{' '}
                  for:
                </p>
                <ul className="list-inside list-disc">
                  <li>Reporting a broken set, leaderboard, or rich presence.</li>
                  <li>Requesting a set be playtested.</li>
                  <li>Hash compatibility questions.</li>
                  <li>Hub organizational questions.</li>
                  <li>Getting involved in a QA sub-team.</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">Art Team</h2>
                <p>
                  <a href={route('message.create', { to: 'RAArtTeam' })}>
                    Send a message to RAArtTeam
                  </a>{' '}
                  for:
                </p>
                <ul className="list-inside list-disc">
                  <li>Icon Gauntlets and how to start one.</li>
                  <li>Proposing art updates.</li>
                  <li>Questions about art-related rule changes.</li>
                  <li>Requests for help with creating a new badge or badge set.</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">DevQuest</h2>
                <p>
                  <a href={route('message.create', { to: 'DevQuest' })}>
                    Send a message to DevQuest
                  </a>{' '}
                  for submissions, questions, ideas, or reporting issues related to{' '}
                  <a href={route('game.show', { game: 5686 })}>DevQuest</a>.
                </p>
              </div>
            </div>
          </div>

          <div className="flex flex-col gap-2 lg:gap-4">
            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">Cheating Reports</h2>
                <p className="mb-2">
                  <a href={route('message.create', { to: 'RACheats' })}>
                    Send a message to RACheats
                  </a>{' '}
                  if you believe someone is in violation of our{' '}
                  <a href="https://docs.retroachievements.org/guidelines/users/global-leaderboard-and-achievement-hunting-rules.html#not-allowed">
                    Global Leaderboard and Achievement Hunting Rules
                  </a>
                  .
                </p>
                <p>
                  Please include as much evidence as possible to support your claim. This could
                  include screenshots, videos, links to suspicious profiles, or any other relevant
                  information that demonstrates the alleged violation. Describe each piece of
                  evidence in detail, explaining why it suggests a violation of the rules. The more
                  comprehensive and clear your submission, the more efficiently we can evaluate and
                  address the issue.
                </p>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">Writing Team</h2>
                <p>
                  <a href={route('message.create', { to: 'WritingTeam' })}>
                    Send a message to WritingTeam
                  </a>{' '}
                  for:
                </p>
                <ul className="list-inside list-disc">
                  <li>Reporting achievements with grammatical mistakes.</li>
                  <li>Reporting achievements with unclear or confusing descriptions.</li>
                  <li>Requesting help from the team with proofreading achievement sets.</li>
                  <li>Requesting help for coming up with original titles for achievements.</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">RANews</h2>
                <p>
                  <a href={route('message.create', { to: 'RANews' })}>Send a message to RANews</a>{' '}
                  for:
                </p>
                <ul className="list-inside list-disc">
                  <li>Submitting a Play This Set, Wish This Set, or RAdvantage entry.</li>
                  <li>Submitting a retrogaming article.</li>
                  <li>Proposing a new article idea.</li>
                  <li>Getting involved with RANews.</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">RAEvents</h2>
                <p className="mb-4">
                  <a href={route('message.create', { to: 'RAEvents' })}>
                    Send a message to RAEvents
                  </a>{' '}
                  for submissions, questions, ideas, or reporting issues related to{' '}
                  <a href={route('game.show', { game: 3105 })}>community events</a>.
                </p>
                <p>
                  <a href={route('message.create', { to: 'TheUnwanted' })}>
                    Send a message to TheUnwanted
                  </a>{' '}
                  for submissions, questions, ideas, or reporting issues specifically related to{' '}
                  <a href={route('game.show', { game: 4721 })}>The Unwanted</a>.
                </p>
              </div>
            </div>
          </div>
        </div>
      </AppLayout.Main>
    </>
  );
};

Contact.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Contact;
