import type {
  IPartnerReview,
  IMarketplaceTag,
  IServiceInquiry,
  IServiceOffering,
  IDataAccessStatus,
  ICreateReviewInput,
  ICanReviewResponse,
  IPartnerEngagement,
  IParticipationStats,
  IParticipationReport,
  IMarketplaceCategory,
  IProviderRatingStats,
  IServiceProviderDetails,
  IInterventionParticipation,
  IProviderRatingDistribution,
  IMarketplaceCatalogResponse,
  IMarketplaceSuggestionsResponse,
} from "src/types/marketplace";

import useSWR from "swr";
import { useMemo } from "react";

import axios, { fetcher, endpoints } from "src/lib/axios";

// ----------------------------------------------------------------------
// Categories and Tags
// ----------------------------------------------------------------------

export function useGetMarketplaceCategories() {
  const { data, isLoading, error, mutate } = useSWR<{
    categories: IMarketplaceCategory[];
  }>(endpoints.marketplace.categories, fetcher);

  const memoizedValue = useMemo(
    () => ({
      categories: data?.categories || [],
      categoriesLoading: isLoading,
      categoriesError: error,
      categoriesMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetMarketplaceTags(search?: string) {
  const url = search
    ? `${endpoints.marketplace.tags}?search=${encodeURIComponent(search)}`
    : endpoints.marketplace.tags;

  const { data, isLoading, error, mutate } = useSWR<{
    tags: IMarketplaceTag[];
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      tags: data?.tags || [],
      tagsLoading: isLoading,
      tagsError: error,
      tagsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Catalog Browse
// ----------------------------------------------------------------------

interface BrowseParams {
  categories?: number[];
  tags?: number[];
  search?: string;
  nationwide?: boolean;
  remote?: boolean;
  certified?: boolean;
  page?: number;
  limit?: number;
}

export function useGetMarketplaceCatalog(params: BrowseParams = {}) {
  const queryParams = new URLSearchParams();

  if (params.categories?.length) {
    queryParams.set("categories", params.categories.join(","));
  }
  if (params.tags?.length) {
    queryParams.set("tags", params.tags.join(","));
  }
  if (params.search) {
    queryParams.set("search", params.search);
  }
  if (params.nationwide) {
    queryParams.set("nationwide", "true");
  }
  if (params.remote) {
    queryParams.set("remote", "true");
  }
  if (params.certified) {
    queryParams.set("certified", "true");
  }
  if (params.page) {
    queryParams.set("page", String(params.page));
  }
  if (params.limit) {
    queryParams.set("limit", String(params.limit));
  }

  const url = `${endpoints.marketplace.browse}?${queryParams.toString()}`;

  const { data, isLoading, error, mutate } =
    useSWR<IMarketplaceCatalogResponse>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      providers: data?.providers || [],
      pagination: data?.pagination || {
        page: 1,
        limit: 20,
        total: 0,
        totalPages: 0,
      },
      filters: data?.filters,
      catalogLoading: isLoading,
      catalogEmpty: !isLoading && !data?.providers?.length,
      catalogError: error,
      catalogMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Offerings Browse (for intervention planning)
// ----------------------------------------------------------------------

interface OfferingWithProvider {
  offering: IServiceOffering;
  provider: {
    id: number;
    companyName: string;
    logoUrl: string | null;
    isPremium: boolean;
    isNationwide: boolean;
    offersRemote: boolean;
    categories: Array<{ id: number; name: string; slug: string }>;
  };
}

interface OfferingsResponse {
  offerings: OfferingWithProvider[];
  total: number;
  filters: {
    phases: number[];
    categories: number[];
    certified: boolean;
    search: string | null;
  };
}

interface OfferingsParams {
  phases?: number[];
  categories?: number[];
  certified?: boolean;
  search?: string;
  limit?: number;
}

export function useGetMarketplaceOfferings(params: OfferingsParams = {}) {
  const queryParams = new URLSearchParams();

  if (params.phases?.length) {
    queryParams.set("phases", params.phases.join(","));
  }
  if (params.categories?.length) {
    queryParams.set("categories", params.categories.join(","));
  }
  if (params.certified) {
    queryParams.set("certified", "true");
  }
  if (params.search) {
    queryParams.set("search", params.search);
  }
  if (params.limit) {
    queryParams.set("limit", String(params.limit));
  }

  const url = `${endpoints.marketplace.offerings}?${queryParams.toString()}`;

  const { data, isLoading, error, mutate } = useSWR<OfferingsResponse>(
    url,
    fetcher,
  );

  const memoizedValue = useMemo(
    () => ({
      offerings: data?.offerings || [],
      total: data?.total || 0,
      filters: data?.filters,
      offeringsLoading: isLoading,
      offeringsEmpty: !isLoading && !data?.offerings?.length,
      offeringsError: error,
      offeringsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Offerings by Integration Point (for legal requirements, analyses)
// ----------------------------------------------------------------------

interface OfferingWithProviderDetails {
  offering: IServiceOffering & {
    matchedIntegrationPoints: string[];
    matchedOutputTypes: string[];
  };
  provider: {
    id: number;
    companyName: string;
    shortDescription: string | null;
    logoUrl: string | null;
    isPremium: boolean;
    isNationwide: boolean;
    offersRemote: boolean;
    website: string | null;
    averageRating: number | null;
    reviewCount: number | null;
  };
}

interface OfferingsByIntegrationResponse {
  offerings: OfferingWithProviderDetails[];
  total: number;
  filters: {
    integrationPoints: string[];
    outputTypes: string[];
  };
}

interface OfferingsByIntegrationParams {
  integrationPoints?: string[];
  outputTypes?: string[];
  limit?: number;
}

export function useGetOfferingsByIntegration(
  params: OfferingsByIntegrationParams,
) {
  const queryParams = new URLSearchParams();

  if (params.integrationPoints?.length) {
    queryParams.set("integrationPoints", params.integrationPoints.join(","));
  }
  if (params.outputTypes?.length) {
    queryParams.set("outputTypes", params.outputTypes.join(","));
  }
  if (params.limit) {
    queryParams.set("limit", String(params.limit));
  }

  // Only fetch if we have at least one filter
  const shouldFetch =
    (params.integrationPoints?.length || params.outputTypes?.length) ?? false;
  const url = shouldFetch
    ? `${endpoints.marketplace.offeringsByIntegration}?${queryParams.toString()}`
    : null;

  const { data, isLoading, error, mutate } =
    useSWR<OfferingsByIntegrationResponse>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      offerings: data?.offerings || [],
      total: data?.total || 0,
      filters: data?.filters,
      offeringsLoading: isLoading,
      offeringsEmpty: !isLoading && !data?.offerings?.length,
      offeringsError: error,
      offeringsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// My Provider Profile (for authenticated providers)
// ----------------------------------------------------------------------

export function useGetMyProvider() {
  const { data, isLoading, error, mutate } = useSWR<{
    hasProvider: boolean;
    provider: IServiceProviderDetails | null;
  }>(endpoints.marketplace.providers.me, fetcher);

  const memoizedValue = useMemo(
    () => ({
      hasProvider: data?.hasProvider || false,
      provider: data?.provider || null,
      myProviderLoading: isLoading,
      myProviderError: error,
      myProviderMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Provider Details
// ----------------------------------------------------------------------

export function useGetProviderDetails(id: number | null) {
  const url = id ? endpoints.marketplace.providerDetail(id) : null;

  const { data, isLoading, error, mutate } = useSWR<{
    provider: IServiceProviderDetails;
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      provider: data?.provider || null,
      providerLoading: isLoading,
      providerError: error,
      providerMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Suggestions for BGM Phase Integration
// ----------------------------------------------------------------------

interface SuggestionParams {
  phase?: number;
  goalTags?: string[];
  limit?: number;
}

export function useGetMarketplaceSuggestions(params: SuggestionParams = {}) {
  const queryParams = new URLSearchParams();

  if (params.phase) {
    queryParams.set("phase", String(params.phase));
  }
  if (params.goalTags?.length) {
    queryParams.set("goalTags", params.goalTags.join(","));
  }
  if (params.limit) {
    queryParams.set("limit", String(params.limit));
  }

  const url = `${endpoints.marketplace.suggestions}?${queryParams.toString()}`;

  const { data, isLoading, error, mutate } =
    useSWR<IMarketplaceSuggestionsResponse>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      suggestions: data?.providers || [],
      context: data?.context,
      suggestionsLoading: isLoading,
      suggestionsError: error,
      suggestionsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Inquiries
// ----------------------------------------------------------------------

export function useGetInquiries() {
  const { data, isLoading, error, mutate } = useSWR<{
    inquiries: IServiceInquiry[];
  }>(endpoints.marketplace.inquiries.list, fetcher);

  const memoizedValue = useMemo(
    () => ({
      inquiries: data?.inquiries || [],
      inquiriesLoading: isLoading,
      inquiriesError: error,
      inquiriesMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetInquiriesByProject(projectId: number | null) {
  const url = projectId
    ? endpoints.marketplace.inquiries.byProject(projectId)
    : null;

  const { data, isLoading, error, mutate } = useSWR<{
    inquiries: IServiceInquiry[];
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      inquiries: data?.inquiries || [],
      inquiriesLoading: isLoading,
      inquiriesError: error,
      inquiriesMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Admin
// ----------------------------------------------------------------------

/**
 * @deprecated Use useGetAdminProviders with status filter instead
 */
export function useGetPendingProviders(page = 1, limit = 20) {
  return useGetAdminProviders({ status: "pending", page, limit });
}

/**
 * Get providers for admin view with status filter.
 * Uses /api/admin/marketplace/providers with optional status query param.
 */
export function useGetAdminProviders(
  params: {
    status?: "pending" | "approved" | "rejected";
    page?: number;
    limit?: number;
  } = {},
) {
  const { status, page = 1, limit = 20 } = params;

  const queryParams = new URLSearchParams();
  if (status) {
    queryParams.set("status", status);
  }
  queryParams.set("page", String(page));
  queryParams.set("limit", String(limit));

  const url = `${endpoints.marketplace.admin.list}?${queryParams.toString()}`;

  const { data, isLoading, error, mutate } = useSWR<{
    providers: IServiceProviderDetails[];
    pagination: {
      page: number;
      limit: number;
      total: number;
      totalPages: number;
    };
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      providers: data?.providers || [],
      pagination: data?.pagination || {
        page: 1,
        limit: 20,
        total: 0,
        totalPages: 0,
      },
      providersLoading: isLoading,
      providersError: error,
      providersMutate: mutate,
      // Backwards compatibility aliases
      pendingLoading: isLoading,
      pendingError: error,
      pendingMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetMarketplaceAdminStats() {
  const { data, isLoading, error, mutate } = useSWR<{
    stats: {
      pending: number;
      approved: number;
      rejected: number;
      total: number;
    };
  }>(endpoints.marketplace.admin.stats, fetcher);

  const memoizedValue = useMemo(
    () => ({
      stats: data?.stats || { pending: 0, approved: 0, rejected: 0, total: 0 },
      statsLoading: isLoading,
      statsError: error,
      statsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// ----------------------------------------------------------------------
// Mutations (non-SWR actions)
// ----------------------------------------------------------------------

export async function createInquiry(data: {
  providerId: number;
  offeringId?: number;
  bgmProjectId?: number;
  contactName: string;
  contactEmail: string;
  contactPhone?: string;
  message: string;
  metadata?: Record<string, any>;
}) {
  const response = await axios.post(
    endpoints.marketplace.inquiries.create,
    data,
  );
  return response.data;
}

export async function registerProvider(data: {
  companyName: string;
  contactEmail: string;
  contactPhone?: string;
  contactPerson?: string;
  description: string;
  shortDescription?: string;
  logoUrl?: string;
  website?: string;
  location?: Record<string, string>;
  serviceRegions?: string[];
  isNationwide: boolean;
  offersRemote: boolean;
  categoryIds: number[];
  tagIds: number[];
}) {
  const response = await axios.post(
    endpoints.marketplace.providers.register,
    data,
  );
  return response.data;
}

export async function approveProvider(id: number) {
  const response = await axios.post(endpoints.marketplace.admin.approve(id));
  return response.data;
}

export async function rejectProvider(id: number, reason: string) {
  const response = await axios.post(endpoints.marketplace.admin.reject(id), {
    reason,
  });
  return response.data;
}

export async function deleteProvider(id: number) {
  const response = await axios.delete(endpoints.marketplace.admin.delete(id));
  return response.data;
}

export async function createProvider(data: {
  companyName: string;
  contactEmail: string;
  contactPhone?: string;
  contactPerson?: string;
  description: string;
  shortDescription?: string;
  logoUrl?: string;
  website?: string;
  location?: Record<string, string>;
  serviceRegions?: string[];
  isNationwide: boolean;
  offersRemote: boolean;
  categoryIds: number[];
  tagIds: number[];
  status?: "pending" | "approved" | "rejected";
}) {
  const response = await axios.post(endpoints.marketplace.admin.create, data);
  return response.data;
}

export async function updateProvider(
  id: number,
  data: Partial<{
    companyName: string;
    contactEmail: string;
    contactPhone?: string;
    contactPerson?: string;
    description: string;
    shortDescription?: string;
    logoUrl?: string;
    website?: string;
    location?: Record<string, string>;
    serviceRegions?: string[];
    isNationwide: boolean;
    offersRemote: boolean;
    categoryIds: number[];
    tagIds: number[];
    status?: "pending" | "approved" | "rejected";
  }>,
) {
  const response = await axios.patch(
    endpoints.marketplace.admin.update(id),
    data,
  );
  return response.data;
}

// ----------------------------------------------------------------------
// Partner Bookmarks (Manual Partner Marking)
// ----------------------------------------------------------------------

export interface IPartnerBookmark {
  id: number;
  tenantId: number;
  providerId: number;
  providerName: string;
  providerLogo: string | null;
  note: string | null;
  createdAt: string;
}

export function useGetPartnerBookmarks() {
  const { data, isLoading, error, mutate } = useSWR<{
    bookmarks: IPartnerBookmark[];
    providerIds: number[];
  }>(endpoints.marketplace.bookmarks.list, fetcher);

  const memoizedValue = useMemo(
    () => ({
      bookmarks: data?.bookmarks || [],
      bookmarkedProviderIds: new Set<number>(data?.providerIds || []),
      bookmarksLoading: isLoading,
      bookmarksError: error,
      bookmarksMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export async function addPartnerBookmark(providerId: number, note?: string) {
  const response = await axios.post(
    endpoints.marketplace.bookmarks.add(providerId),
    { note },
  );
  return response.data;
}

export async function removePartnerBookmark(providerId: number) {
  const response = await axios.delete(
    endpoints.marketplace.bookmarks.remove(providerId),
  );
  return response.data;
}

export async function updatePartnerBookmark(providerId: number, note: string) {
  const response = await axios.patch(
    endpoints.marketplace.bookmarks.update(providerId),
    { note },
  );
  return response.data;
}

// ----------------------------------------------------------------------
// Partner Engagements
// ----------------------------------------------------------------------

export function useGetEngagements(activeOnly = false) {
  const url = `${endpoints.marketplace.engagements.list}${activeOnly ? "?active=true" : ""}`;

  const { data, isLoading, error, mutate } = useSWR<{
    engagements: IPartnerEngagement[];
    count: number;
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      engagements: data?.engagements || [],
      engagementsCount: data?.count || 0,
      engagementsLoading: isLoading,
      engagementsError: error,
      engagementsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetEngagement(id: number | null) {
  const url = id ? endpoints.marketplace.engagements.detail(id) : null;

  const { data, isLoading, error, mutate } = useSWR<{
    engagement: IPartnerEngagement;
    dataAccessStatus: IDataAccessStatus;
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      engagement: data?.engagement || null,
      dataAccessStatus: data?.dataAccessStatus || null,
      engagementLoading: isLoading,
      engagementError: error,
      engagementMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetEngagementStats() {
  const { data, isLoading, error, mutate } = useSWR<{
    activeEngagements: number;
    pendingIntegration: number;
  }>(endpoints.marketplace.engagements.stats, fetcher);

  const memoizedValue = useMemo(
    () => ({
      activeEngagements: data?.activeEngagements || 0,
      pendingIntegration: data?.pendingIntegration || 0,
      statsLoading: isLoading,
      statsError: error,
      statsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

// Engagement Mutations

export async function createEngagement(data: {
  providerId: number;
  offeringId: number;
  agreedPricing?: { amount?: number; currency?: string; type?: string };
  scheduledDate?: string;
}) {
  const response = await axios.post(
    endpoints.marketplace.engagements.list,
    data,
  );
  return response.data;
}

export async function activateEngagement(id: number) {
  const response = await axios.post(
    endpoints.marketplace.engagements.activate(id),
  );
  return response.data;
}

export async function cancelEngagement(id: number) {
  const response = await axios.post(
    endpoints.marketplace.engagements.cancel(id),
  );
  return response.data;
}

export async function completeEngagement(id: number) {
  const response = await axios.post(
    endpoints.marketplace.engagements.complete(id),
  );
  return response.data;
}

export async function grantDataScopes(id: number, scopes: string[]) {
  const response = await axios.post(
    endpoints.marketplace.engagements.grantData(id),
    { scopes },
  );
  return response.data;
}

export async function revokeDataScope(id: number, scope: string) {
  const response = await axios.post(
    endpoints.marketplace.engagements.revokeData(id),
    { scope },
  );
  return response.data;
}

export async function updateEngagementNotes(id: number, notes: string) {
  const response = await axios.patch(
    endpoints.marketplace.engagements.notes(id),
    { notes },
  );
  return response.data;
}

// Result Upload Mutations

export async function uploadResult(
  engagementId: number,
  data: {
    outputType: string;
    data?: Record<string, any>;
    fileUrl?: string;
    summary?: string;
  },
) {
  const response = await axios.post(
    endpoints.marketplace.engagements.results(engagementId),
    data,
  );
  return response.data;
}

export async function getEngagementResults(engagementId: number) {
  const response = await axios.get(
    endpoints.marketplace.engagements.results(engagementId),
  );
  return response.data;
}

export async function integrateResult(
  engagementId: number,
  outputType: string,
  integrationPoint: string,
) {
  const response = await axios.post(
    endpoints.marketplace.engagements.integrateResult(engagementId, outputType),
    { integrationPoint },
  );
  return response.data;
}

// ----------------------------------------------------------------------
// Intervention Participation
// ----------------------------------------------------------------------

export function useGetParticipations(engagementId?: number) {
  const queryParams = engagementId ? `?engagementId=${engagementId}` : "";
  const url = `${endpoints.marketplace.participations.list}${queryParams}`;

  const { data, isLoading, error, mutate } = useSWR<{
    participations: IInterventionParticipation[];
    count: number;
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      participations: data?.participations || [],
      participationsCount: data?.count || 0,
      participationsLoading: isLoading,
      participationsError: error,
      participationsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetParticipationStats(engagementId: number | null) {
  const url = engagementId
    ? endpoints.marketplace.participations.stats(engagementId)
    : null;

  const { data, isLoading, error, mutate } = useSWR<IParticipationStats>(
    url,
    fetcher,
  );

  const memoizedValue = useMemo(
    () => ({
      stats: data || null,
      statsLoading: isLoading,
      statsError: error,
      statsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetParticipationReport(year?: number) {
  const reportYear = year || new Date().getFullYear();
  const url = `${endpoints.marketplace.participations.internalReport}?year=${reportYear}`;

  const { data, isLoading, error, mutate } = useSWR<IParticipationReport>(
    url,
    fetcher,
  );

  const memoizedValue = useMemo(
    () => ({
      report: data || null,
      reportLoading: isLoading,
      reportError: error,
      reportMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetKpiTrend(year?: number) {
  const reportYear = year || new Date().getFullYear();
  const url = `${endpoints.marketplace.participations.kpiTrend}?year=${reportYear}`;

  const { data, isLoading, error } = useSWR<{
    year: number;
    trend: Array<{
      month: number;
      monthName: string;
      participations: number;
      uniqueParticipants: number;
    }>;
    totalUniqueParticipants: number;
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      trend: data?.trend || [],
      totalUniqueParticipants: data?.totalUniqueParticipants || 0,
      trendLoading: isLoading,
    }),
    [data, isLoading],
  );

  return memoizedValue;
}

// Participation Mutations

export async function registerParticipant(data: {
  engagementId?: number;
  interventionTitle?: string;
  interventionDescription?: string;
  employeeId?: number;
  employeeEmail?: string;
  employeeName?: string;
  department?: string;
  eventDate?: string;
  category?: string;
  specialRequirements?: string[];
}) {
  const response = await axios.post(
    endpoints.marketplace.participations.list,
    data,
  );
  return response.data;
}

export async function bulkRegisterParticipants(data: {
  engagementId?: number;
  interventionTitle?: string;
  eventDate?: string;
  category?: string;
  participants: Array<{
    employeeId?: number;
    employeeEmail?: string;
    employeeName?: string;
    department?: string;
    specialRequirements?: string[];
  }>;
}) {
  const response = await axios.post(
    endpoints.marketplace.participations.bulk,
    data,
  );
  return response.data;
}

export async function markParticipantAttended(id: number) {
  const response = await axios.post(
    endpoints.marketplace.participations.attend(id),
  );
  return response.data;
}

export async function markParticipantNoShow(id: number) {
  const response = await axios.post(
    endpoints.marketplace.participations.noShow(id),
  );
  return response.data;
}

export async function cancelParticipation(id: number) {
  const response = await axios.post(
    endpoints.marketplace.participations.cancel(id),
  );
  return response.data;
}

export async function addParticipationFeedback(
  id: number,
  feedback: { rating?: number; comment?: string },
) {
  const response = await axios.post(
    endpoints.marketplace.participations.feedback(id),
    feedback,
  );
  return response.data;
}

// ----------------------------------------------------------------------
// Partner Reviews
// ----------------------------------------------------------------------

export function useGetProviderReviews(providerId: number | null) {
  const url = providerId
    ? endpoints.marketplace.reviews.byProvider(providerId)
    : null;

  const { data, isLoading, error, mutate } = useSWR<{
    reviews: IPartnerReview[];
    stats: IProviderRatingStats;
    distribution: IProviderRatingDistribution;
  }>(url, fetcher);

  const memoizedValue = useMemo(
    () => ({
      reviews: data?.reviews || [],
      stats: data?.stats || null,
      distribution: data?.distribution || null,
      reviewsLoading: isLoading,
      reviewsError: error,
      reviewsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useGetMyReviews() {
  const { data, isLoading, error, mutate } = useSWR<{
    reviews: IPartnerReview[];
  }>(endpoints.marketplace.reviews.my, fetcher);

  const memoizedValue = useMemo(
    () => ({
      reviews: data?.reviews || [],
      reviewsLoading: isLoading,
      reviewsError: error,
      reviewsMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export function useCanReview(providerId: number | null) {
  const url = providerId
    ? endpoints.marketplace.reviews.canReview(providerId)
    : null;

  const { data, isLoading, error, mutate } = useSWR<ICanReviewResponse>(
    url,
    fetcher,
  );

  const memoizedValue = useMemo(
    () => ({
      canReview: data?.canReview ?? false,
      reason: data?.reason,
      message: data?.message,
      engagementId: data?.engagementId,
      engagementTitle: data?.engagementTitle,
      canReviewLoading: isLoading,
      canReviewError: error,
      canReviewMutate: mutate,
    }),
    [data, isLoading, error, mutate],
  );

  return memoizedValue;
}

export async function createReview(input: ICreateReviewInput) {
  const response = await axios.post(
    endpoints.marketplace.reviews.create,
    input,
  );
  return response.data;
}

export async function updateReview(
  reviewId: number,
  input: Partial<ICreateReviewInput>,
) {
  const response = await axios.patch(
    endpoints.marketplace.reviews.update(reviewId),
    input,
  );
  return response.data;
}

export async function deleteReview(reviewId: number) {
  const response = await axios.delete(
    endpoints.marketplace.reviews.delete(reviewId),
  );
  return response.data;
}

export async function getBatchRatingStats(providerIds: number[]): Promise<
  Record<
    number,
    {
      reviewCount: number;
      averageRating: number | null;
      recommendRate: number | null;
    }
  >
> {
  const response = await axios.post(endpoints.marketplace.reviews.batchStats, {
    providerIds,
  });
  return response.data.stats;
}
