// ----------------------------------------------------------------------
// Marketplace Types - BGM Service Provider Directory
// ----------------------------------------------------------------------

export interface IMarketplaceCategory {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  description: string | null;
  sortOrder: number;
}

export interface IMarketplaceTag {
  id: number;
  name: string;
  slug: string;
}

export interface IServiceOffering {
  id: number;
  providerId: number;
  title: string;
  description: string;
  pricingInfo: {
    type?: 'fixed' | 'hourly' | 'project' | 'on_request';
    amount?: number;
    currency?: string;
    note?: string;
  } | null;
  deliveryModes: ('onsite' | 'remote' | 'hybrid')[];
  isCertified: boolean;
  certificationName: string | null;
  duration: string | null;
  minParticipants: number | null;
  maxParticipants: number | null;
  isActive: boolean;
  sortOrder: number;
  createdAt: string;
  updatedAt: string | null;
}

export interface IServiceProvider {
  id: number;
  companyName: string;
  shortDescription: string | null;
  logoUrl: string | null;
  coverImageUrl: string | null;
  website: string | null;
  status: 'pending' | 'approved' | 'rejected';
  isNationwide: boolean;
  offersRemote: boolean;
  isPremium: boolean;
  categories: IMarketplaceCategory[];
  tags: IMarketplaceTag[];
  createdAt: string;
  // Computed properties from offerings
  relevantPhases: number[];
  hasCertifiedOfferings: boolean;
  certifications: string[];
  // Rating stats (populated by API)
  averageRating: number | null;
  reviewCount: number | null;
  recommendRate: number | null;
}

export interface IServiceProviderDetails extends IServiceProvider {
  contactEmail: string;
  contactPhone: string | null;
  contactPerson: string | null;
  description: string;
  location: {
    city?: string;
    region?: string;
    country?: string;
    postalCode?: string;
  } | null;
  serviceRegions: string[] | null;
  offerings: IServiceOffering[];
  approvedAt: string | null;
  updatedAt: string | null;
  rejectionReason: string | null;
}

export interface IServiceInquiry {
  id: number;
  tenantId: number;
  tenantName: string | null;
  providerId: number;
  providerName: string | null;
  offeringId: number | null;
  offeringTitle: string | null;
  bgmProjectId: number | null;
  contactName: string;
  contactEmail: string;
  contactPhone: string | null;
  message: string;
  status: 'new' | 'contacted' | 'in_progress' | 'completed' | 'declined';
  metadata: Record<string, any> | null;
  providerNotes: string | null;
  createdAt: string;
  updatedAt: string | null;
  respondedAt: string | null;
}

// API Response Types
export interface IMarketplaceCatalogResponse {
  providers: IServiceProvider[];
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
  filters: {
    categories: number[];
    tags: number[];
    search: string | null;
    nationwide: boolean;
    remote: boolean;
  };
}

export interface IMarketplaceSuggestionsResponse {
  providers: IServiceProvider[];
  context: {
    phase: number;
    categorySlugs: string[];
    tagSlugs: string[];
  };
}

// Filter Types
export interface IMarketplaceFilters {
  categories: number[];
  tags: number[];
  search: string;
  nationwide: boolean;
  remote: boolean;
}

// Registration Form Data
export interface IProviderRegistrationData {
  companyName: string;
  contactEmail: string;
  contactPhone?: string;
  contactPerson?: string;
  description: string;
  shortDescription?: string;
  logoUrl?: string;
  website?: string;
  location?: {
    city?: string;
    region?: string;
    country?: string;
    postalCode?: string;
  };
  serviceRegions?: string[];
  isNationwide: boolean;
  offersRemote: boolean;
  categoryIds: number[];
  tagIds: number[];
  offerings?: Array<{
    title: string;
    description: string;
    pricingInfo?: IServiceOffering['pricingInfo'];
    deliveryModes: ('onsite' | 'remote' | 'hybrid')[];
    isCertified?: boolean;
    certificationName?: string;
    duration?: string;
    minParticipants?: number;
    maxParticipants?: number;
  }>;
}

// Inquiry Form Data
export interface IInquiryFormData {
  providerId: number;
  offeringId?: number;
  bgmProjectId?: number;
  contactName: string;
  contactEmail: string;
  contactPhone?: string;
  message: string;
  metadata?: Record<string, any>;
}

// ----------------------------------------------------------------------
// Partner Engagement Types
// ----------------------------------------------------------------------

export type TEngagementStatus =
  | 'draft'
  | 'active'
  | 'data_shared'
  | 'processing'
  | 'delivered'
  | 'completed'
  | 'cancelled';

export type TParticipationStatus = 'registered' | 'attended' | 'no_show' | 'cancelled';

export type TDataScopeSensitivity = 'low' | 'medium' | 'high';

export interface IDataScope {
  key: string;
  label: string;
  description: string;
  sensitivity: TDataScopeSensitivity;
  granted: boolean;
}

export interface IPartnerEngagement {
  id: number;
  tenantId: number;
  providerId: number;
  providerName: string;
  offeringId: number;
  offeringTitle: string;
  inquiryId: number | null;
  status: TEngagementStatus;
  grantedDataScopes: string[];
  deliveredOutputs: string[];
  integrationStatus: Record<string, { integrated_at: string; integration_point: string }> | null;
  partnerContact: {
    name: string | null;
    email: string | null;
    phone: string | null;
  };
  agreedPricing: {
    amount?: number;
    currency?: string;
    type?: 'fixed' | 'hourly' | 'per_participant';
  } | null;
  scheduledDate: string | null;
  createdAt: string;
  updatedAt: string | null;
  activatedAt: string | null;
  completedAt: string | null;
  cancelledAt: string | null;
  customerNotes?: string;
  partnerNotes?: string;
}

export interface IDataAccessStatus {
  requiredScopes: Record<string, IDataScope>;
  allGranted: boolean;
  grantedCount: number;
  requiredCount: number;
}

export interface IInterventionParticipation {
  id: number;
  tenantId: number;
  interventionType: 'partner_engagement' | 'health_day_module' | 'internal';
  engagementId: number | null;
  interventionTitle: string | null;
  employeeId: number | null;
  employeeEmail: string | null;
  employeeName: string | null;
  department: string | null;
  eventDate: string | null;
  category: string | null;
  status: TParticipationStatus;
  rating: number | null;
  feedbackComment: string | null;
  specialRequirements: string[] | null;
  registeredAt: string | null;
  attendedAt: string | null;
  createdAt: string;
}

export interface IParticipationStats {
  registeredCount: number;
  attendedCount: number;
  noShowCount: number;
  cancelledCount: number;
  attendanceRate: number;
  averageRating: number | null;
  ratingCount: number;
  dietaryRequirements: Record<string, number>;
  aggregatedAt: string;
}

export interface IParticipationReport {
  year: number;
  summary: {
    uniqueParticipants: number;
    totalParticipations: number;
  };
  byCategory: Array<{
    category: string;
    totalParticipations: number;
    uniqueParticipants: number;
  }>;
  byDepartment: Array<{
    department: string;
    totalParticipations: number;
    uniqueParticipants: number;
  }>;
  monthlyTrend: Array<{
    month: number;
    monthName: string;
    participations: number;
    uniqueParticipants: number;
  }>;
  generatedAt: string;
}

// Extended ServiceOffering with partner integration fields
export interface IServiceOfferingExtended extends IServiceOffering {
  requiredDataScopes: string[] | null;
  outputDataTypes: string[] | null;
  integrationPoints: string[] | null;
  relevantPhases: number[] | null;
  isOrchestratorService: boolean;
}

// Status Labels
export const PROVIDER_STATUS_OPTIONS = [
  { value: 'pending', label: 'Ausstehend', color: 'warning' as const },
  { value: 'approved', label: 'Freigeschaltet', color: 'success' as const },
  { value: 'rejected', label: 'Abgelehnt', color: 'error' as const },
];

export const INQUIRY_STATUS_OPTIONS = [
  { value: 'new', label: 'Neu', color: 'info' as const },
  { value: 'contacted', label: 'Kontaktiert', color: 'primary' as const },
  { value: 'in_progress', label: 'In Bearbeitung', color: 'warning' as const },
  { value: 'completed', label: 'Abgeschlossen', color: 'success' as const },
  { value: 'declined', label: 'Abgelehnt', color: 'error' as const },
];

export const DELIVERY_MODE_OPTIONS = [
  { value: 'onsite', label: 'Vor Ort', icon: 'solar:buildings-bold' },
  { value: 'remote', label: 'Remote', icon: 'solar:monitor-bold' },
  { value: 'hybrid', label: 'Hybrid', icon: 'solar:widget-bold' },
];

// Category Icons Mapping
export const CATEGORY_ICONS: Record<string, string> = {
  bewegung: 'solar:running-round-bold',
  ernaehrung: 'solar:chef-hat-heart-bold',
  'mentale-gesundheit': 'solar:brain-bold',
  suchtpraevention: 'solar:shield-warning-bold',
  ergonomie: 'solar:armchair-bold',
  'bgm-allgemein': 'solar:health-bold',
};

// Partner Engagement Status Options
export const ENGAGEMENT_STATUS_OPTIONS = [
  { value: 'draft', label: 'Entwurf', color: 'default' as const, icon: 'solar:document-linear' },
  { value: 'active', label: 'Aktiv', color: 'info' as const, icon: 'solar:play-bold' },
  { value: 'data_shared', label: 'Daten geteilt', color: 'primary' as const, icon: 'solar:share-bold' },
  { value: 'processing', label: 'In Bearbeitung', color: 'warning' as const, icon: 'solar:refresh-bold' },
  { value: 'delivered', label: 'Geliefert', color: 'secondary' as const, icon: 'solar:inbox-in-bold' },
  { value: 'completed', label: 'Abgeschlossen', color: 'success' as const, icon: 'solar:check-circle-bold' },
  { value: 'cancelled', label: 'Abgebrochen', color: 'error' as const, icon: 'solar:close-circle-bold' },
];

export const PARTICIPATION_STATUS_OPTIONS = [
  { value: 'registered', label: 'Angemeldet', color: 'info' as const, icon: 'solar:user-check-bold' },
  { value: 'attended', label: 'Teilgenommen', color: 'success' as const, icon: 'solar:check-circle-bold' },
  { value: 'no_show', label: 'Nicht erschienen', color: 'warning' as const, icon: 'solar:user-cross-bold' },
  { value: 'cancelled', label: 'Abgesagt', color: 'error' as const, icon: 'solar:close-circle-bold' },
];

export const DATA_SENSITIVITY_OPTIONS = [
  { value: 'low', label: 'Gering', color: 'success' as const, description: 'Allgemeine Metadaten' },
  { value: 'medium', label: 'Mittel', color: 'warning' as const, description: 'Anonymisierte Daten' },
  { value: 'high', label: 'Hoch', color: 'error' as const, description: 'Personenbezogene Daten' },
];

export const BGM_PHASE_LABELS: Record<number, string> = {
  1: 'Phase 1: Vorbereitung',
  2: 'Phase 2: Analyse',
  3: 'Phase 3: Planung',
  4: 'Phase 4: Maßnahmen',
  5: 'Phase 5: Evaluation',
  6: 'Phase 6: Nachhaltigkeit',
};

// ----------------------------------------------------------------------
// Partner Reviews
// ----------------------------------------------------------------------

export interface IPartnerReview {
  id: number;
  providerId: number;
  providerName: string;
  overallRating: number;
  communicationRating: number | null;
  qualityRating: number | null;
  valueRating: number | null;
  reliabilityRating: number | null;
  averageSubRating: number | null;
  title: string | null;
  comment: string | null;
  pros: string[] | null;
  cons: string[] | null;
  serviceUsed: string | null;
  wouldRecommend: boolean;
  reviewerName: string;
  isVerified: boolean;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: string;
  // Private fields (only for own reviews)
  tenantId?: number;
  engagementId?: number;
  authorId?: number;
  showCompanyName?: boolean;
  rejectionReason?: string | null;
  updatedAt?: string | null;
  approvedAt?: string | null;
}

export interface IProviderRatingStats {
  reviewCount: number;
  averageRating: number | null;
  avgCommunication: number | null;
  avgQuality: number | null;
  avgValue: number | null;
  avgReliability: number | null;
  recommendRate: number | null;
}

export interface IProviderRatingDistribution {
  1: number;
  2: number;
  3: number;
  4: number;
  5: number;
}

export interface ICanReviewResponse {
  canReview: boolean;
  reason?: 'already_reviewed' | 'no_engagement' | 'all_reviewed';
  message?: string;
  engagementId?: number;
  engagementTitle?: string;
}

export interface ICreateReviewInput {
  providerId: number;
  engagementId?: number;
  overallRating: number;
  communicationRating?: number;
  qualityRating?: number;
  valueRating?: number;
  reliabilityRating?: number;
  title?: string;
  comment?: string;
  pros?: string[];
  cons?: string[];
  serviceUsed?: string;
  wouldRecommend?: boolean;
  showCompanyName?: boolean;
}

