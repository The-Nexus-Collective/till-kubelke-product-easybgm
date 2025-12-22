<?php

declare(strict_types=1);

namespace TillKubelke\ModuleMarketplace\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TillKubelke\ModuleMarketplace\Entity\Category;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Entity\Tag;
use TillKubelke\ModuleMarketplace\Repository\CategoryRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Repository\TagRepository;

/**
 * Seeds production marketplace with real partner providers.
 * 
 * This command creates the initial set of approved service providers
 * for the production environment, including Ramboll and Upfit.
 * 
 * Usage:
 *   php bin/console marketplace:seed:production-providers
 *   php bin/console marketplace:seed:production-providers --force  # Skip confirmation
 */
#[AsCommand(
    name: 'marketplace:seed:production-providers',
    description: 'Seeds marketplace with production service providers (Ramboll, Upfit)',
)]
class SeedProductionProvidersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceProviderRepository $providerRepository,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created without persisting');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('Marketplace Production Provider Seeder');

        if ($isDryRun) {
            $io->note('DRY RUN MODE - No data will be persisted');
        }

        if (!$force && !$isDryRun) {
            if (!$io->confirm('This will seed production providers. Continue?', false)) {
                $io->warning('Aborted.');
                return Command::SUCCESS;
            }
        }

        // Ensure categories exist
        $categories = $this->ensureCategories($io, $isDryRun);

        // Ensure tags exist
        $tags = $this->ensureTags($io, $isDryRun);

        // Create providers
        $providersCreated = 0;

        // === RAMBOLL ===
        if ($this->createRamboll($io, $categories, $tags, $isDryRun)) {
            $providersCreated++;
        }

        // === UPFIT ===
        if ($this->createUpfit($io, $categories, $tags, $isDryRun)) {
            $providersCreated++;
        }

        if (!$isDryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf(
            '%s %d production provider(s) with their offerings.',
            $isDryRun ? 'Would create' : 'Created',
            $providersCreated
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<string, Category>
     */
    private function ensureCategories(SymfonyStyle $io, bool $isDryRun): array
    {
        $categoryData = [
            'bewegung' => ['name' => 'Bewegung', 'icon' => 'directions_run', 'sortOrder' => 1],
            'ernaehrung' => ['name' => 'Ernährung', 'icon' => 'restaurant', 'sortOrder' => 2],
            'mentale-gesundheit' => ['name' => 'Mentale Gesundheit', 'icon' => 'psychology', 'sortOrder' => 3],
            'suchtpraevention' => ['name' => 'Suchtprävention', 'icon' => 'no_drinks', 'sortOrder' => 4],
            'ergonomie' => ['name' => 'Ergonomie', 'icon' => 'chair', 'sortOrder' => 5],
            'bgm-beratung' => ['name' => 'BGM-Beratung', 'icon' => 'support_agent', 'sortOrder' => 6],
            'analyse' => ['name' => 'Analyse & Diagnostik', 'icon' => 'analytics', 'sortOrder' => 7],
        ];

        $categories = [];

        foreach ($categoryData as $slug => $data) {
            $existing = $this->categoryRepository->findOneBy(['slug' => $slug]);
            if ($existing) {
                $categories[$slug] = $existing;
                continue;
            }

            $category = new Category();
            $category->setSlug($slug);
            $category->setName($data['name']);
            $category->setIcon($data['icon']);
            $category->setSortOrder($data['sortOrder']);

            if (!$isDryRun) {
                $this->entityManager->persist($category);
            }
            $categories[$slug] = $category;
            $io->text(sprintf('  [+] Category: %s', $data['name']));
        }

        return $categories;
    }

    /**
     * @return array<string, Tag>
     */
    private function ensureTags(SymfonyStyle $io, bool $isDryRun): array
    {
        $tagNames = [
            'COPSOQ',
            'GB Psych',
            'Gefährdungsbeurteilung',
            'Arbeitspsychologie',
            'Firmenfitness',
            'Bewegte Pause',
            'Rückengesundheit',
            'Gesundheitstage',
            'Präventionskurse',
            'EAP',
            'Resilienz',
            'Stressmanagement',
            'BGM-Beratung',
            'Workshops',
            'Coaching',
            'Digitales BGM',
            'Mitarbeiterbefragung',
            'Auswertung',
            'Zertifiziert §20 SGB V',
        ];

        $tags = [];

        foreach ($tagNames as $name) {
            $slug = $this->slugify($name);
            $existing = $this->tagRepository->findOneBy(['slug' => $slug]);
            if ($existing) {
                $tags[$slug] = $existing;
                continue;
            }

            $tag = new Tag();
            $tag->setSlug($slug);
            $tag->setName($name);

            if (!$isDryRun) {
                $this->entityManager->persist($tag);
            }
            $tags[$slug] = $tag;
        }

        $io->text(sprintf('  [+] Ensured %d tags exist', count($tags)));

        return $tags;
    }

    /**
     * @param array<string, Category> $categories
     * @param array<string, Tag> $tags
     */
    private function createRamboll(SymfonyStyle $io, array $categories, array $tags, bool $isDryRun): bool
    {
        // Check if already exists
        $existing = $this->providerRepository->findOneBy(['companyName' => 'Ramboll']);
        if ($existing) {
            $io->text('  [=] Ramboll already exists, skipping.');
            return false;
        }

        $io->section('Creating Ramboll');

        $provider = new ServiceProvider();
        $provider->setCompanyName('Ramboll');
        $provider->setContactEmail('bgm@ramboll.com');
        $provider->setContactPhone('+49 40 302020-0');
        $provider->setContactPerson('BGM-Team');
        $provider->setDescription(
            'Ramboll ist ein führendes internationales Ingenieur-, Architektur- und Managementberatungsunternehmen. ' .
            'Im Bereich Betriebliches Gesundheitsmanagement (BGM) bieten wir umfassende Lösungen für die ' .
            'psychische Gefährdungsbeurteilung, COPSOQ-Analysen und arbeitspsychologische Beratung. ' .
            'Mit über 17.000 Experten weltweit unterstützen wir Unternehmen dabei, gesunde und produktive ' .
            'Arbeitsumgebungen zu schaffen. Unsere wissenschaftlich fundierten Methoden und langjährige ' .
            'Erfahrung machen uns zum idealen Partner für Ihr BGM.'
        );
        $provider->setShortDescription(
            'Führender Anbieter für Gefährdungsbeurteilung psychischer Belastung und COPSOQ-Analysen.'
        );
        $provider->setLogoUrl('https://www.ramboll.com/-/media/images/rgr/logos/ramboll-logo.png');
        $provider->setWebsite('https://www.ramboll.com');
        $provider->setStatus(ServiceProvider::STATUS_APPROVED);
        $provider->setIsNationwide(true);
        $provider->setOffersRemote(true);
        $provider->setIsPremium(true);
        $provider->setLocation([
            'city' => 'Hamburg',
            'postalCode' => '20457',
            'street' => 'Am Sandtorpark 4',
            'country' => 'DE',
        ]);
        $provider->setServiceRegions([
            'DE-HH', 'DE-NI', 'DE-SH', 'DE-HB', 'DE-NW', 'DE-HE', 'DE-RP',
            'DE-BW', 'DE-BY', 'DE-SL', 'DE-BE', 'DE-BB', 'DE-MV', 'DE-SN',
            'DE-ST', 'DE-TH'
        ]);

        // Add categories
        if (isset($categories['mentale-gesundheit'])) {
            $provider->addCategory($categories['mentale-gesundheit']);
        }
        if (isset($categories['bgm-beratung'])) {
            $provider->addCategory($categories['bgm-beratung']);
        }
        if (isset($categories['analyse'])) {
            $provider->addCategory($categories['analyse']);
        }

        // Add tags
        $rambollTags = ['copsoq', 'gb-psych', 'gefaehrdungsbeurteilung', 'arbeitspsychologie', 
                        'mitarbeiterbefragung', 'auswertung', 'bgm-beratung'];
        foreach ($rambollTags as $tagSlug) {
            if (isset($tags[$tagSlug])) {
                $provider->addTag($tags[$tagSlug]);
            }
        }

        // === Ramboll Offerings ===

        // 1. Gefährdungsbeurteilung psychischer Belastung
        $offering1 = new ServiceOffering();
        $offering1->setTitle('Gefährdungsbeurteilung psychischer Belastung');
        $offering1->setDescription(
            'Umfassende Gefährdungsbeurteilung nach §5 ArbSchG zur Ermittlung psychischer Belastungsfaktoren. ' .
            'Wir analysieren systematisch die Arbeitsbedingungen Ihrer Mitarbeiter und identifizieren ' .
            'Belastungsschwerpunkte. Das Ergebnis ist ein detaillierter Maßnahmenplan zur Verbesserung ' .
            'der psychischen Gesundheit am Arbeitsplatz.'
        );
        $offering1->setDeliveryModes([ServiceOffering::DELIVERY_ONSITE, ServiceOffering::DELIVERY_REMOTE, ServiceOffering::DELIVERY_HYBRID]);
        $offering1->setDuration('4-8 Wochen');
        $offering1->setPricingInfo([
            'type' => 'project',
            'startingFrom' => 5000,
            'currency' => 'EUR',
            'note' => 'Abhängig von Unternehmensgröße',
        ]);
        $offering1->setRelevantPhases([2]); // Phase 2: Analyse
        $offering1->setRequiredDataScopes(['employee_count', 'organizational_structure', 'work_areas']);
        $offering1->setOutputDataTypes(['risk_assessment', 'action_plan', 'documentation']);
        $offering1->setIntegrationPoints(['phase_2.analysis', 'legal.gefaehrdungsbeurteilung']);
        $offering1->setSortOrder(1);
        $provider->addOffering($offering1);

        // 2. COPSOQ-Befragung und Auswertung
        $offering2 = new ServiceOffering();
        $offering2->setTitle('COPSOQ-Befragung und Auswertung');
        $offering2->setDescription(
            'Wissenschaftlich validierte Mitarbeiterbefragung nach dem Copenhagen Psychosocial Questionnaire (COPSOQ). ' .
            'Wir führen die komplette Befragung durch, analysieren die Ergebnisse und liefern einen ' .
            'detaillierten Ergebnisbericht mit Benchmarking und konkreten Handlungsempfehlungen.'
        );
        $offering2->setDeliveryModes([ServiceOffering::DELIVERY_REMOTE]);
        $offering2->setDuration('3-6 Wochen');
        $offering2->setPricingInfo([
            'type' => 'per_employee',
            'pricePerUnit' => 15,
            'currency' => 'EUR',
            'minOrder' => 50,
        ]);
        $offering2->setRelevantPhases([2]); // Phase 2: Analyse
        $offering2->setRequiredDataScopes(['employee_list', 'department_structure']);
        $offering2->setOutputDataTypes(['copsoq_analysis', 'benchmark_report', 'recommendations']);
        $offering2->setIntegrationPoints(['phase_2.analysis', 'kpi.psychische_belastung']);
        $offering2->setIsCertified(true);
        $offering2->setCertificationName('COPSOQ III');
        $offering2->setSortOrder(2);
        $provider->addOffering($offering2);

        // 3. Arbeitspsychologische Beratung
        $offering3 = new ServiceOffering();
        $offering3->setTitle('Arbeitspsychologische Beratung');
        $offering3->setDescription(
            'Individuelle arbeitspsychologische Beratung für Führungskräfte und Teams. ' .
            'Unsere zertifizierten Arbeitspsychologen unterstützen bei der Gestaltung gesunder ' .
            'Arbeitsbedingungen, Konfliktlösung und der Entwicklung einer gesundheitsförderlichen ' .
            'Unternehmenskultur.'
        );
        $offering3->setDeliveryModes([ServiceOffering::DELIVERY_ONSITE, ServiceOffering::DELIVERY_REMOTE]);
        $offering3->setDuration('Nach Bedarf');
        $offering3->setPricingInfo([
            'type' => 'hourly',
            'pricePerHour' => 180,
            'currency' => 'EUR',
        ]);
        $offering3->setRelevantPhases([3, 4, 5]); // Phase 3-5: Planung, Umsetzung, Evaluation
        $offering3->setRequiredDataScopes(['goals', 'survey_results']);
        $offering3->setOutputDataTypes(['consultation_report', 'recommendations']);
        $offering3->setIntegrationPoints(['phase_3.planning', 'phase_4.implementation']);
        $offering3->setSortOrder(3);
        $provider->addOffering($offering3);

        // 4. BGM-Strategieberatung
        $offering4 = new ServiceOffering();
        $offering4->setTitle('BGM-Strategieberatung');
        $offering4->setDescription(
            'Strategische Beratung zur Entwicklung und Implementierung eines ganzheitlichen ' .
            'Betrieblichen Gesundheitsmanagements. Von der Bedarfsanalyse über die Konzeption ' .
            'bis zur Implementierung und Evaluation begleiten wir Sie auf dem Weg zu einem ' .
            'nachhaltigen BGM.'
        );
        $offering4->setDeliveryModes([ServiceOffering::DELIVERY_ONSITE, ServiceOffering::DELIVERY_HYBRID]);
        $offering4->setDuration('3-12 Monate');
        $offering4->setPricingInfo([
            'type' => 'project',
            'startingFrom' => 10000,
            'currency' => 'EUR',
            'note' => 'Individuelles Angebot',
        ]);
        $offering4->setRelevantPhases([1, 2, 3, 4, 5, 6]); // Alle Phasen
        $offering4->setRequiredDataScopes(['organizational_structure', 'goals', 'existing_measures']);
        $offering4->setOutputDataTypes(['strategy_document', 'implementation_plan', 'kpi_framework']);
        $offering4->setIntegrationPoints(['phase_1.setup', 'phase_3.planning']);
        $offering4->setIsOrchestratorService(true);
        $offering4->setSortOrder(4);
        $provider->addOffering($offering4);

        if (!$isDryRun) {
            $this->entityManager->persist($provider);
        }

        $io->text('  [+] Created Ramboll with 4 offerings');
        return true;
    }

    /**
     * @param array<string, Category> $categories
     * @param array<string, Tag> $tags
     */
    private function createUpfit(SymfonyStyle $io, array $categories, array $tags, bool $isDryRun): bool
    {
        // Check if already exists
        $existing = $this->providerRepository->findOneBy(['companyName' => 'Upfit']);
        if ($existing) {
            $io->text('  [=] Upfit already exists, skipping.');
            return false;
        }

        $io->section('Creating Upfit');

        $provider = new ServiceProvider();
        $provider->setCompanyName('Upfit');
        $provider->setContactEmail('business@upfit.de');
        $provider->setContactPhone('+49 221 98658-0');
        $provider->setContactPerson('B2B-Team');
        $provider->setDescription(
            'Upfit ist Deutschlands führende Plattform für personalisierte Ernährungspläne und Firmenfitness. ' .
            'Mit über 1 Million Nutzern bieten wir wissenschaftlich fundierte Ernährungsprogramme, ' .
            'die individuell auf jeden Mitarbeiter zugeschnitten sind. Unsere digitale Lösung ermöglicht ' .
            'es Unternehmen, ihren Mitarbeitern ein modernes BGM-Benefit anzubieten, das Ernährung, ' .
            'Bewegung und mentale Gesundheit vereint. Die App-basierte Lösung macht gesunde Ernährung ' .
            'einfach und alltagstauglich.'
        );
        $provider->setShortDescription(
            'Digitale Plattform für personalisierte Ernährungspläne und Firmenfitness-Programme.'
        );
        $provider->setLogoUrl('https://upfit.de/wp-content/uploads/2021/01/upfit-logo.svg');
        $provider->setWebsite('https://upfit.de/business');
        $provider->setStatus(ServiceProvider::STATUS_APPROVED);
        $provider->setIsNationwide(true);
        $provider->setOffersRemote(true);
        $provider->setIsPremium(true);
        $provider->setLocation([
            'city' => 'Köln',
            'postalCode' => '50667',
            'street' => 'Im Mediapark 5',
            'country' => 'DE',
        ]);
        $provider->setServiceRegions([
            'DE-HH', 'DE-NI', 'DE-SH', 'DE-HB', 'DE-NW', 'DE-HE', 'DE-RP',
            'DE-BW', 'DE-BY', 'DE-SL', 'DE-BE', 'DE-BB', 'DE-MV', 'DE-SN',
            'DE-ST', 'DE-TH'
        ]);

        // Add categories
        if (isset($categories['ernaehrung'])) {
            $provider->addCategory($categories['ernaehrung']);
        }
        if (isset($categories['bewegung'])) {
            $provider->addCategory($categories['bewegung']);
        }

        // Add tags
        $upfitTags = ['firmenfitness', 'praeventionskurse', 'digitales-bgm', 'zertifiziert-20-sgb-v'];
        foreach ($upfitTags as $tagSlug) {
            if (isset($tags[$tagSlug])) {
                $provider->addTag($tags[$tagSlug]);
            }
        }

        // === Upfit Offerings ===

        // 1. Upfit Business - Ernährungscoaching
        $offering1 = new ServiceOffering();
        $offering1->setTitle('Upfit Business - Personalisierte Ernährungspläne');
        $offering1->setDescription(
            'Geben Sie Ihren Mitarbeitern Zugang zu personalisierten Ernährungsplänen über die Upfit App. ' .
            'Jeder Mitarbeiter erhält einen individuellen Plan basierend auf Zielen, Vorlieben und ' .
            'Allergien. Mit über 3.000 Rezepten, Einkaufslisten und Ernährungstipps wird gesunde ' .
            'Ernährung zum Kinderspiel.'
        );
        $offering1->setDeliveryModes([ServiceOffering::DELIVERY_REMOTE]);
        $offering1->setDuration('Laufzeit ab 3 Monate');
        $offering1->setPricingInfo([
            'type' => 'per_employee_monthly',
            'pricePerUnit' => 9.90,
            'currency' => 'EUR',
            'minOrder' => 20,
            'note' => 'Staffelpreise verfügbar',
        ]);
        $offering1->setRelevantPhases([4, 5]); // Phase 4-5: Umsetzung, Evaluation
        $offering1->setRequiredDataScopes(['employee_count']);
        $offering1->setOutputDataTypes(['participation_stats', 'usage_report']);
        $offering1->setIntegrationPoints(['phase_4.implementation', 'kpi.ernaehrung']);
        $offering1->setIsCertified(true);
        $offering1->setCertificationName('§20 SGB V');
        $offering1->setSortOrder(1);
        $provider->addOffering($offering1);

        // 2. Präventionskurs Ernährung
        $offering2 = new ServiceOffering();
        $offering2->setTitle('Präventionskurs "Gesunde Ernährung im Berufsalltag"');
        $offering2->setDescription(
            'Zertifizierter Präventionskurs nach §20 SGB V zum Thema Ernährung. ' .
            'Der 8-wöchige Online-Kurs vermittelt Grundlagen gesunder Ernährung, ' .
            'praktische Tipps für den Berufsalltag und individuelle Ernährungsstrategien. ' .
            'Die Krankenkasse erstattet bis zu 100% der Kursgebühren.'
        );
        $offering2->setDeliveryModes([ServiceOffering::DELIVERY_REMOTE]);
        $offering2->setDuration('8 Wochen');
        $offering2->setPricingInfo([
            'type' => 'per_participant',
            'pricePerUnit' => 89,
            'currency' => 'EUR',
            'note' => 'Erstattungsfähig durch Krankenkasse',
        ]);
        $offering2->setRelevantPhases([4]); // Phase 4: Umsetzung
        $offering2->setRequiredDataScopes(['employee_list']);
        $offering2->setOutputDataTypes(['completion_certificate', 'participation_stats']);
        $offering2->setIntegrationPoints(['phase_4.implementation']);
        $offering2->setIsCertified(true);
        $offering2->setCertificationName('§20 SGB V Präventionskurs');
        $offering2->setMinParticipants(1);
        $offering2->setSortOrder(2);
        $provider->addOffering($offering2);

        // 3. Firmenfitness Challenge
        $offering3 = new ServiceOffering();
        $offering3->setTitle('Team-Ernährungs-Challenge');
        $offering3->setDescription(
            'Motivieren Sie Ihre Mitarbeiter mit einer unternehmensweiten Ernährungs-Challenge. ' .
            'Teams treten gegeneinander an, sammeln Punkte für gesunde Mahlzeiten und ' .
            'sportliche Aktivitäten. Mit Gamification-Elementen, Leaderboards und ' .
            'attraktiven Preisen steigern Sie die Teilnahme und den Teamgeist.'
        );
        $offering3->setDeliveryModes([ServiceOffering::DELIVERY_REMOTE]);
        $offering3->setDuration('4-12 Wochen');
        $offering3->setPricingInfo([
            'type' => 'project',
            'startingFrom' => 2000,
            'currency' => 'EUR',
            'note' => 'Zzgl. Lizenzgebühr pro Teilnehmer',
        ]);
        $offering3->setRelevantPhases([4]); // Phase 4: Umsetzung
        $offering3->setRequiredDataScopes(['employee_count', 'department_structure']);
        $offering3->setOutputDataTypes(['participation_stats', 'engagement_report', 'leaderboard']);
        $offering3->setIntegrationPoints(['phase_4.implementation', 'kpi.teilnahme']);
        $offering3->setMinParticipants(20);
        $offering3->setSortOrder(3);
        $provider->addOffering($offering3);

        // 4. Ernährungsworkshop vor Ort
        $offering4 = new ServiceOffering();
        $offering4->setTitle('Ernährungs-Workshop "Fit im Büro"');
        $offering4->setDescription(
            'Interaktiver Workshop zum Thema gesunde Ernährung am Arbeitsplatz. ' .
            'Unsere zertifizierten Ernährungsberater vermitteln praxisnahe Tipps ' .
            'für die Mittagspause, gesunde Snacks und die optimale Flüssigkeitszufuhr. ' .
            'Inklusive Live-Cooking-Demo und Verkostung.'
        );
        $offering4->setDeliveryModes([ServiceOffering::DELIVERY_ONSITE, ServiceOffering::DELIVERY_HYBRID]);
        $offering4->setDuration('2-4 Stunden');
        $offering4->setPricingInfo([
            'type' => 'per_workshop',
            'pricePerUnit' => 1500,
            'currency' => 'EUR',
            'note' => 'Für bis zu 20 Teilnehmer',
        ]);
        $offering4->setRelevantPhases([4]); // Phase 4: Umsetzung
        $offering4->setRequiredDataScopes([]);
        $offering4->setOutputDataTypes(['workshop_materials', 'feedback_summary']);
        $offering4->setIntegrationPoints(['phase_4.implementation', 'gesundheitstag']);
        $offering4->setMinParticipants(8);
        $offering4->setMaxParticipants(20);
        $offering4->setSortOrder(4);
        $provider->addOffering($offering4);

        // 5. Betriebliche Gesundheitsförderung - Komplettpaket
        $offering5 = new ServiceOffering();
        $offering5->setTitle('BGF Komplettpaket Ernährung');
        $offering5->setDescription(
            'Ganzheitliches Ernährungsprogramm für Ihr Unternehmen. Das Paket beinhaltet: ' .
            '1) Upfit Business App-Zugang für alle Mitarbeiter, ' .
            '2) Quartalsweise Ernährungsworkshops, ' .
            '3) Team-Challenges mit Gamification, ' .
            '4) Monatliche Reporting und KPI-Tracking, ' .
            '5) Dedizierter Account Manager.'
        );
        $offering5->setDeliveryModes([ServiceOffering::DELIVERY_HYBRID]);
        $offering5->setDuration('12 Monate');
        $offering5->setPricingInfo([
            'type' => 'project',
            'startingFrom' => 15000,
            'currency' => 'EUR',
            'note' => 'Individuelles Angebot nach Mitarbeiterzahl',
        ]);
        $offering5->setRelevantPhases([3, 4, 5]); // Phase 3-5: Planung, Umsetzung, Evaluation
        $offering5->setRequiredDataScopes(['employee_count', 'goals', 'organizational_structure']);
        $offering5->setOutputDataTypes(['participation_stats', 'health_report', 'roi_analysis']);
        $offering5->setIntegrationPoints(['phase_3.planning', 'phase_4.implementation', 'phase_5.evaluation']);
        $offering5->setIsOrchestratorService(true);
        $offering5->setIsCertified(true);
        $offering5->setCertificationName('§20 SGB V / BGF');
        $offering5->setSortOrder(5);
        $provider->addOffering($offering5);

        if (!$isDryRun) {
            $this->entityManager->persist($provider);
        }

        $io->text('  [+] Created Upfit with 5 offerings');
        return true;
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = str_replace(
            ['ä', 'ö', 'ü', 'ß', ' ', '§'],
            ['ae', 'oe', 'ue', 'ss', '-', ''],
            $text
        );
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        return trim($text, '-');
    }
}


