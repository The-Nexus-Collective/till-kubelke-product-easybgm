<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use TillKubelke\ModuleMarketplace\Entity\Category;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Entity\Tag;
use TillKubelke\ModuleMarketplace\Repository\CategoryRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Repository\TagRepository;

/**
 * Public catalog controller for browsing BGM service providers.
 * Provides filtering by categories, tags, and search functionality.
 */
#[Route('/api/marketplace/catalog', name: 'api_marketplace_catalog_')]
class CatalogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
    ) {
    }

    /**
     * Get all available categories.
     */
    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $categories = $this->categoryRepository->findAllOrdered();

        return new JsonResponse([
            'categories' => array_map(fn(Category $c) => $c->toArray(), $categories),
        ]);
    }

    /**
     * Get all available tags.
     */
    #[Route('/tags', name: 'tags', methods: ['GET'])]
    public function getTags(Request $request): JsonResponse
    {
        $search = $request->query->get('search');

        if ($search) {
            $tags = $this->tagRepository->searchByName($search);
        } else {
            $tags = $this->tagRepository->findAllOrdered();
        }

        return new JsonResponse([
            'tags' => array_map(fn(Tag $t) => $t->toArray(), $tags),
        ]);
    }

    /**
     * Browse approved service providers with filtering.
     * 
     * Query parameters:
     * - categories: comma-separated category IDs or slugs
     * - tags: comma-separated tag IDs or slugs  
     * - search: search term for company name/description
     * - nationwide: boolean, filter for nationwide providers
     * - remote: boolean, filter for providers offering remote services
     * - certified: boolean, filter for providers with §20 SGB V certified offerings
     * - page: page number (default 1)
     * - limit: items per page (default 20, max 100)
     */
    #[Route('', name: 'browse', methods: ['GET'])]
    public function browseProviders(Request $request): JsonResponse
    {
        // Parse filter parameters
        $categoriesParam = $request->query->get('categories', '');
        $tagsParam = $request->query->get('tags', '');
        $search = $request->query->get('search');
        $nationwideOnly = $request->query->getBoolean('nationwide', false);
        $remoteOnly = $request->query->getBoolean('remote', false);
        $certifiedOnly = $request->query->getBoolean('certified', false);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Parse category and tag IDs (support both IDs and slugs)
        $categoryIds = $this->parseFilterParam($categoriesParam, 'category');
        $tagIds = $this->parseFilterParam($tagsParam, 'tag');

        // Get providers
        $providers = $this->providerRepository->findApprovedProviders(
            categoryIds: $categoryIds,
            tagIds: $tagIds,
            search: $search,
            nationwideOnly: $nationwideOnly,
            remoteOnly: $remoteOnly,
            certifiedOnly: $certifiedOnly,
            limit: $limit,
            offset: $offset,
        );

        // Get total count
        $total = $this->providerRepository->countApprovedProviders(
            categoryIds: $categoryIds,
            tagIds: $tagIds,
            search: $search,
            nationwideOnly: $nationwideOnly,
            remoteOnly: $remoteOnly,
            certifiedOnly: $certifiedOnly,
        );

        return new JsonResponse([
            'providers' => array_map(fn(ServiceProvider $p) => $p->toArray(), $providers),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
            'filters' => [
                'categories' => $categoryIds,
                'tags' => $tagIds,
                'search' => $search,
                'nationwide' => $nationwideOnly,
                'remote' => $remoteOnly,
                'certified' => $certifiedOnly,
            ],
        ]);
    }

    /**
     * Get details for a specific provider.
     */
    #[Route('/providers/{id}', name: 'provider_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProviderDetails(int $id): JsonResponse
    {
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        // Only show approved providers publicly
        if (!$provider->isApproved()) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'provider' => $provider->toArray(includeDetails: true),
        ]);
    }

    /**
     * Get providers by category slug (convenience endpoint).
     */
    #[Route('/category/{slug}', name: 'by_category', methods: ['GET'])]
    public function getProvidersByCategory(string $slug, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if (!$category) {
            return new JsonResponse(['error' => 'Kategorie nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        // Redirect to browse with category filter
        $request->query->set('categories', (string) $category->getId());
        return $this->browseProviders($request);
    }

    /**
     * Get suggested providers based on BGM phase context.
     * 
     * Query parameters:
     * - phase: BGM phase number (1-6)
     * - goalTags: comma-separated tag slugs based on active goals
     * - limit: max results (default 5)
     */
    #[Route('/suggestions', name: 'suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request): JsonResponse
    {
        $phase = $request->query->getInt('phase');
        $goalTagsParam = $request->query->get('goalTags', '');
        $limit = min(20, max(1, $request->query->getInt('limit', 5)));

        // Map BGM phases to relevant category/tag slugs
        $phaseMappings = [
            1 => ['categorySlugs' => [], 'tagSlugs' => ['bgm-beratung']],
            2 => ['categorySlugs' => ['mentale-gesundheit'], 'tagSlugs' => ['gb-psych', 'check-ups', 'bgm-beratung']],
            3 => ['categorySlugs' => [], 'tagSlugs' => ['bgm-beratung', 'gesund-fuehren']],
            4 => ['categorySlugs' => [], 'tagSlugs' => ['workshops', 'gesundheitstage', 'firmenfitness']],
            5 => ['categorySlugs' => [], 'tagSlugs' => ['check-ups', 'workshops']],
            6 => ['categorySlugs' => [], 'tagSlugs' => ['bgm-beratung']],
        ];

        $categorySlugs = [];
        $tagSlugs = [];

        // Add phase-based suggestions
        if (isset($phaseMappings[$phase])) {
            $categorySlugs = $phaseMappings[$phase]['categorySlugs'];
            $tagSlugs = $phaseMappings[$phase]['tagSlugs'];
        }

        // Add goal-based tag slugs
        if ($goalTagsParam !== '') {
            $goalTags = array_filter(array_map('trim', explode(',', $goalTagsParam)));
            $tagSlugs = array_unique(array_merge($tagSlugs, $goalTags));
        }

        // Find matching providers
        $providers = $this->providerRepository->findByCategorySlugsAndTagSlugs(
            categorySlugs: $categorySlugs,
            tagSlugs: $tagSlugs,
            limit: $limit,
        );

        return new JsonResponse([
            'providers' => array_map(fn(ServiceProvider $p) => $p->toArray(), $providers),
            'context' => [
                'phase' => $phase,
                'categorySlugs' => $categorySlugs,
                'tagSlugs' => $tagSlugs,
            ],
        ]);
    }

    /**
     * Parse comma-separated filter parameter (supports IDs or slugs).
     *
     * @return int[]
     */
    private function parseFilterParam(string $param, string $type): array
    {
        if ($param === '') {
            return [];
        }

        $values = array_filter(array_map('trim', explode(',', $param)));
        $ids = [];

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            } else {
                // It's a slug, look up the ID
                if ($type === 'category') {
                    $category = $this->categoryRepository->findBySlug($value);
                    if ($category) {
                        $ids[] = $category->getId();
                    }
                } elseif ($type === 'tag') {
                    $tag = $this->tagRepository->findBySlug($value);
                    if ($tag) {
                        $ids[] = $tag->getId();
                    }
                }
            }
        }

        return array_unique($ids);
    }
}


