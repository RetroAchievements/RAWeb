import { Trans, useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const Contact: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Contact Us')}
        description="Get in touch with RetroAchievements teams for reporting issues, seeking assistance, or contributing to the community."
      />

      <AppLayout.Main>
        <h1 className="mb-4">{t('Contact Us')}</h1>

        <div className="grid gap-2 lg:grid-cols-2 lg:gap-4">
          <div className="flex flex-col gap-2 lg:gap-4">
            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('Admins and Moderators')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to RAdmin</1> for:"
                    components={{ 1: <a href={route('message.create', { to: 'RAdmin' })} /> }}
                  />
                </p>
                <ul className="list-inside list-disc">
                  <li>{t('Reporting offensive behavior.')}</li>
                  <li>{t('Reporting copyrighted material.')}</li>
                  <li>{t('Requesting to be untracked.')}</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('Developer Compliance')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to DevCompliance</1> for:"
                    components={{
                      1: <a href={route('message.create', { to: 'DevCompliance' })} />,
                    }}
                  />
                </p>
                <ul className="list-inside list-disc">
                  <li>{t('Requesting set approval or early set release.')}</li>
                  <li>{t('Reporting achievements or sets with unwelcome concepts.')}</li>
                  <li>{t('Reporting sets failing to cover basic progression.')}</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('Quality Assurance')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to QATeam</1> for:"
                    components={{ 1: <a href={route('message.create', { to: 'QATeam' })} /> }}
                  />
                </p>
                <ul className="list-inside list-disc">
                  <li>{t('Reporting a broken set, leaderboard, or rich presence.')}</li>
                  <li>{t('Requesting a set be playtested.')}</li>
                  <li>{t('Hash compatibility questions.')}</li>
                  <li>{t('Hub organizational questions.')}</li>
                  <li>{t('Getting involved in a QA sub-team.')}</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('Art Team')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to RAArtTeam</1> for:"
                    components={{ 1: <a href={route('message.create', { to: 'RAArtTeam' })} /> }}
                  />
                </p>
                <ul className="list-inside list-disc">
                  <li>{t('Icon Gauntlets and how to start one.')}</li>
                  <li>{t('Proposing art updates.')}</li>
                  <li>{t('Questions about art-related rule changes.')}</li>
                  <li>{t('Requests for help with creating a new badge or badge set.')}</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('DevQuest')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to DevQuest</1> for submissions, questions, ideas, or reporting issues related to <2>DevQuest</2>."
                    components={{
                      1: <a href={route('message.create', { to: 'DevQuest' })} />,
                      2: <a href={route('game.show', { game: 5686 })} />,
                    }}
                  />
                </p>
              </div>
            </div>
          </div>

          <div className="flex flex-col gap-2 lg:gap-4">
            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('Cheating Reports')}</h2>
                <p className="mb-2">
                  <Trans
                    i18nKey="<1>Send a message to RACheats</1> if you believe someone is in violation of our <2>Global Leaderboard and Achievement Hunting Rules</2>."
                    components={{
                      1: <a href={route('message.create', { to: 'RACheats' })} />,
                      2: (
                        <a href="https://docs.retroachievements.org/guidelines/users/global-leaderboard-and-achievement-hunting-rules.html#not-allowed" />
                      ),
                    }}
                  />
                </p>
                <p>
                  {t(
                    'Please include as much evidence as possible to support your claim. This could include screenshots, videos, links to suspicious profiles, or any other relevant information that demonstrates the alleged violation. Describe each piece of evidence in detail, explaining why it suggests a violation of the rules. The more comprehensive and clear your submission, the more efficiently we can evaluate and address the issue.',
                  )}
                </p>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('Writing Team')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to WritingTeam</1> for:"
                    components={{ 1: <a href={route('message.create', { to: 'WritingTeam' })} /> }}
                  />
                </p>
                <ul className="list-inside list-disc">
                  <li>{t('Reporting achievements with grammatical mistakes.')}</li>
                  <li>{t('Reporting achievements with unclear or confusing descriptions.')}</li>
                  <li>{t('Requesting help from the team with proofreading achievement sets.')}</li>
                  <li>
                    {t('Requesting help for coming up with original titles for achievements.')}
                  </li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('RANews')}</h2>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to RANews</1> for:"
                    components={{ 1: <a href={route('message.create', { to: 'RANews' })} /> }}
                  />
                </p>
                <ul className="list-inside list-disc">
                  <li>{t('Submitting a Play This Set, Wish This Set, or RAdvantage entry.')}</li>
                  <li>{t('Submitting a retrogaming article.')}</li>
                  <li>{t('Proposing a new article idea.')}</li>
                  <li>{t('Getting involved with RANews.')}</li>
                </ul>
              </div>
            </div>

            <div className="rounded bg-embed p-4">
              <div>
                <h2 className="text-h4">{t('RAEvents')}</h2>
                <p className="mb-4">
                  <Trans
                    i18nKey="<1>Send a message to RAEvents</1> for submissions, questions, ideas, or reporting issues related to <2>community events</2>."
                    components={{
                      1: <a href={route('message.create', { to: 'RAEvents' })} />,
                      2: <a href={route('game.show', { game: 3105 })} />,
                    }}
                  />
                </p>
                <p>
                  <Trans
                    i18nKey="<1>Send a message to TheUnwanted</1> for submissions, questions, ideas, or reporting issues specifically related to <2>The Unwanted</2>."
                    components={{
                      1: <a href={route('message.create', { to: 'TheUnwanted' })} />,
                      2: <a href={route('game.show', { game: 4271 })} />,
                    }}
                  />
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
