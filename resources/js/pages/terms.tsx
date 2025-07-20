/* eslint-disable jsx-a11y/anchor-has-content -- handled automatically by the Trans component */

import { Trans, useTranslation } from 'react-i18next';

import { LegalNotice } from '@/common/components/LegalNotice';
import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const Terms: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Legal & Terms')}
        description="Review our terms of use, code of conduct, disclaimers, copyright policy, and privacy policy."
      />

      <AppLayout.Main>
        <h1 className="mb-4">{t('Legal & Terms')}</h1>

        <div className="flex flex-col gap-y-4">
          {/* 
            scroll-margin-top doesn't seem to work here. Use an invisible div instead
            so we provide some offset for the page sticky nav.
          */}
          <div className="sr-only absolute top-4" id="conditions" />

          <div className="rounded bg-embed p-4">
            <div className="flex flex-col gap-y-6">
              <div>
                <h2 className="text-h4">{t('Terms and Conditions')}</h2>
                <p className="font-bold">
                  {t("RetroAchievements.org's Terms of Use are subject to change.")}
                </p>
                <p>
                  {t(
                    'RetroAchievements may revise these terms of use at any time without notice. By using its services, you agree to the current version of these Terms of Use, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws.',
                  )}
                </p>
              </div>

              <div>
                <p role="heading" aria-level={3} className="text-lg font-medium">
                  {t('Code of Conduct')}
                </p>
                <p>
                  <Trans
                    i18nKey="By signing up on RetroAchievements.org, you agree to the <1>Users Code of Conduct</1>."
                    components={{
                      1: (
                        <a href="https://docs.retroachievements.org/guidelines/users/code-of-conduct.html" />
                      ),
                    }}
                  />
                </p>
                <p>
                  <Trans
                    i18nKey="By joining the Junior Developer and/or Developer team, you agree to the <1>Developers Code of Conduct</1>."
                    components={{
                      1: (
                        <a href="https://docs.retroachievements.org/guidelines/developers/code-of-conduct.html" />
                      ),
                    }}
                  />
                </p>
              </div>
            </div>
          </div>

          <div className="rounded bg-embed p-4">
            <div className="flex flex-col gap-y-6">
              <div>
                <h2 className="text-h4">{t('Disclaimers')}</h2>

                <p role="heading" aria-level={3} className="text-lg font-medium">
                  {t('Accountability for content')}
                </p>
                <p>
                  {t(
                    'We are not obliged to monitor the information transmitted or stored by third parties, nor to investigate circumstances that point to illegal activity. Our obligations to remove or block the use of information under generally applicable laws remain unaffected by this.',
                  )}
                </p>
              </div>

              <div>
                <p role="heading" aria-level={3} className="text-lg font-medium">
                  {t('Accountability for links')}
                </p>

                <p>
                  {t(
                    'Responsibility for the content of external links (to web pages of third parties) lies solely with the operators of the linked pages. No violations were evident to us at the time of linking. Should any legal infringement become known to us, we will remove the respective link immediately.',
                  )}
                </p>
              </div>

              <LegalNotice />
            </div>
          </div>

          <div className="rounded bg-embed p-4">
            <div className="flex flex-col gap-y-6">
              <div>
                <h2 className="text-h4">{t('Copyright')}</h2>
                <p>
                  {t(
                    'Our web pages and their contents are subject to copyright law. Unless expressly permitted by law, every form of utilizing, reproducing or processing works subject to copyright protection on our web pages requires the prior consent of the respective owner of the rights. Individual reproductions of a work are allowed only for private use. The company names, product names, service names and logotypes on this web site are for identification purposes only. All trademarks and registered trademarks are the property of their respective owners.',
                  )}
                </p>
              </div>
            </div>
          </div>

          <div className="rounded bg-embed p-4">
            <div className="flex flex-col gap-y-6">
              <div>
                <h2 className="text-h4" id="privacy-policy">
                  {t('Privacy Policy')}
                </h2>
                <p>
                  {t(
                    'Your personal data is processed by RetroAchievements.org only in accordance with the provisions of applicable data privacy laws. If links on our pages route you to other pages, please inquire there about how your data is handled in such cases.',
                  )}
                </p>
              </div>

              <ul className="list-inside list-disc">
                <li>
                  {t(
                    'Without your explicit consent or a legal basis, your personal data is not passed on to third parties.',
                  )}
                </li>
                <li>
                  {t(
                    'Before or at the time of collecting personal information, we will identify the purposes for which information is being collected.',
                  )}
                </li>
                <li>
                  {t(
                    'We will only retain personal information as long as necessary for the fulfillment of those purposes.',
                  )}
                </li>
                <li>
                  {t(
                    'We will protect personal information by reasonable security safeguards against loss or theft, as well as unauthorized access, disclosure, copying, use or modification.',
                  )}
                </li>
              </ul>

              <p>
                {t(
                  'We maintain API usage logs for a period deemed appropriate to ensure optimal service.',
                )}
              </p>

              <div>
                <p role="heading" aria-level={3} className="text-lg font-medium">
                  {t('Information about cookies')}
                </p>

                <p>
                  {t(
                    'You can prevent storage of cookies by appropriately setting your browser software; in this case, however, please note that you might not be able to fully use all functions offered by this website.',
                  )}
                </p>
              </div>
            </div>
          </div>
        </div>
      </AppLayout.Main>
    </>
  );
};

Terms.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Terms;
