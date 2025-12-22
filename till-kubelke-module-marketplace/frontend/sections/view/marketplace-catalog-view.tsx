import type {
  IServiceProvider,
  IServiceOffering,
  IServiceProviderDetails,
} from "src/types/marketplace";

import { useBoolean } from "minimal-shared/hooks";
import { useMemo, useState, useCallback } from "react";

import Box from "@mui/material/Box";
import Card from "@mui/material/Card";
import Chip from "@mui/material/Chip";
import Grid from "@mui/material/Grid";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Switch from "@mui/material/Switch";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import InputAdornment from "@mui/material/InputAdornment";
import TablePagination from "@mui/material/TablePagination";
import FormControlLabel from "@mui/material/FormControlLabel";
import CircularProgress from "@mui/material/CircularProgress";

import { paths } from "src/routes/paths";

import { useTranslate } from "src/locales";
import { DashboardContent } from "src/layouts/dashboard";
import {
  useGetEngagements,
  addPartnerBookmark,
  useGetProviderDetails,
  removePartnerBookmark,
  useGetPartnerBookmarks,
  useGetMarketplaceCatalog,
  useGetMarketplaceCategories,
} from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";
import { CrudViewHeader } from "src/components/crud-view-header";
import { CustomBreadcrumbs } from "src/components/custom-breadcrumbs";

import { CATEGORY_ICONS } from "src/types/marketplace";

import { InquiryForm } from "../inquiry-form";
import { ProviderCard } from "../provider-card";
import { CompareBar, CompareDrawer } from "../compare-drawer";
import { ProviderDetailDialog } from "../provider-detail-dialog";

// ----------------------------------------------------------------------

export function MarketplaceCatalogView() {
  const { t } = useTranslate("marketplace");

  // Filters
  const [search, setSearch] = useState("");
  const [selectedCategories, setSelectedCategories] = useState<number[]>([]);
  const [nationwide, setNationwide] = useState(false);
  const [remote, setRemote] = useState(false);
  const [certified, setCertified] = useState(false);
  const [myPartnersOnly, setMyPartnersOnly] = useState(false);
  const [premiumOnly, setPremiumOnly] = useState(false);
  const [selectedPhases, setSelectedPhases] = useState<number[]>([]);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(12);

  // Dialogs
  const detailDialog = useBoolean();
  const inquiryDialog = useBoolean();
  const compareDrawer = useBoolean();
  const [selectedProvider, setSelectedProvider] =
    useState<IServiceProvider | null>(null);
  const [selectedOffering, setSelectedOffering] =
    useState<IServiceOffering | null>(null);

  // Compare functionality
  const [compareProviderIds, setCompareProviderIds] = useState<number[]>([]);

  // Data
  const { categories } = useGetMarketplaceCategories();
  const { engagements } = useGetEngagements();
  const { bookmarkedProviderIds, bookmarksMutate } = useGetPartnerBookmarks();
  const { providers, pagination, catalogLoading, catalogEmpty } =
    useGetMarketplaceCatalog({
      categories: selectedCategories,
      search: search || undefined,
      nationwide,
      remote,
      certified,
      page: page + 1,
      limit: rowsPerPage,
    });

  // Get partner provider IDs from active engagements + manual bookmarks
  const partnerProviderIds = useMemo(() => {
    const ids = new Set<number>();
    // Add from engagements
    if (engagements) {
      engagements
        .filter((e) => !["draft", "cancelled"].includes(e.status))
        .forEach((e) => ids.add(e.providerId));
    }
    // Add from bookmarks
    bookmarkedProviderIds.forEach((id) => ids.add(id));
    return ids;
  }, [engagements, bookmarkedProviderIds]);

  // Filter providers based on active filters
  const filteredProviders = useMemo(() => {
    let result = providers;
    if (myPartnersOnly) {
      result = result.filter((p) => partnerProviderIds.has(p.id));
    }
    if (premiumOnly) {
      result = result.filter((p) => p.isPremium);
    }
    if (selectedPhases.length > 0) {
      result = result.filter((p) =>
        p.relevantPhases?.some((phase) => selectedPhases.includes(phase)),
      );
    }
    return result;
  }, [
    providers,
    myPartnersOnly,
    premiumOnly,
    selectedPhases,
    partnerProviderIds,
  ]);

  // Provider details for dialog
  const { provider: providerDetails, providerLoading } = useGetProviderDetails(
    detailDialog.value ? selectedProvider?.id || null : null,
  );

  // Handlers
  const handleCategoryToggle = useCallback((categoryId: number) => {
    setSelectedCategories((prev) =>
      prev.includes(categoryId)
        ? prev.filter((id) => id !== categoryId)
        : [...prev, categoryId],
    );
    setPage(0);
  }, []);

  const handleClearFilters = useCallback(() => {
    setSearch("");
    setSelectedCategories([]);
    setSelectedPhases([]);
    setNationwide(false);
    setRemote(false);
    setCertified(false);
    setMyPartnersOnly(false);
    setPremiumOnly(false);
    setPage(0);
  }, []);

  const handlePhaseToggle = useCallback((phase: number) => {
    setSelectedPhases((prev) =>
      prev.includes(phase) ? prev.filter((p) => p !== phase) : [...prev, phase],
    );
    setPage(0);
  }, []);

  const handleViewDetails = useCallback(
    (provider: IServiceProvider) => {
      setSelectedProvider(provider);
      detailDialog.onTrue();
    },
    [detailDialog],
  );

  const handleInquiry = useCallback(
    (provider: IServiceProvider, offering?: IServiceOffering) => {
      setSelectedProvider(provider);
      setSelectedOffering(offering || null);
      detailDialog.onFalse();
      inquiryDialog.onTrue();
    },
    [detailDialog, inquiryDialog],
  );

  const handleInquiryFromDetail = useCallback(
    (provider: IServiceProviderDetails, offering?: IServiceOffering) => {
      setSelectedProvider(provider);
      setSelectedOffering(offering || null);
      detailDialog.onFalse();
      inquiryDialog.onTrue();
    },
    [detailDialog, inquiryDialog],
  );

  const handleChangePage = useCallback((_: unknown, newPage: number) => {
    setPage(newPage);
  }, []);

  const handleChangeRowsPerPage = useCallback(
    (event: React.ChangeEvent<HTMLInputElement>) => {
      setRowsPerPage(parseInt(event.target.value, 10));
      setPage(0);
    },
    [],
  );

  // Compare handlers
  const handleToggleCompare = useCallback((providerId: number) => {
    setCompareProviderIds((prev) =>
      prev.includes(providerId)
        ? prev.filter((id) => id !== providerId)
        : prev.length < 4 // Max 4 providers for comparison
          ? [...prev, providerId]
          : prev,
    );
  }, []);

  const handleRemoveFromCompare = useCallback((providerId: number) => {
    setCompareProviderIds((prev) => prev.filter((id) => id !== providerId));
  }, []);

  const handleClearCompare = useCallback(() => {
    setCompareProviderIds([]);
    compareDrawer.onFalse();
  }, [compareDrawer]);

  // Get compare providers from current providers list
  const compareProviders = useMemo(
    () => providers.filter((p) => compareProviderIds.includes(p.id)),
    [providers, compareProviderIds],
  );

  const isFiltered =
    search !== "" ||
    selectedCategories.length > 0 ||
    selectedPhases.length > 0 ||
    nationwide ||
    remote ||
    certified ||
    myPartnersOnly ||
    premiumOnly;

  // Build stats array for header
  const headerStats = [
    { value: pagination.total, label: t("catalog.stats.providers") },
    { value: categories.length, label: t("catalog.stats.categories") },
    ...(partnerProviderIds.size > 0
      ? [
          {
            value: partnerProviderIds.size,
            label: t("catalog.stats.myPartners"),
          },
        ]
      : []),
  ];

  return (
    <>
      <DashboardContent>
        <CustomBreadcrumbs
          heading={t("catalog.title")}
          links={[
            { name: t("breadcrumbs.dashboard"), href: paths.dashboard.root },
            { name: t("breadcrumbs.marketplace") },
          ]}
          sx={{ mb: { xs: 3, md: 5 } }}
        />

        {/* Header with Stats */}
        <CrudViewHeader
          title={t("catalog.title")}
          subtitle={t("catalog.info.subtitle")}
          icon="solar:shop-2-bold"
          gradient="info"
          stats={headerStats}
          infoTitle={t("catalog.info.title")}
          infoDescription={t("catalog.info.description")}
          storageKey="marketplace-catalog-header-info"
        />

        {/* Filters */}
        <Card sx={{ mb: 3 }}>
          <Stack spacing={2.5} sx={{ p: 2.5 }}>
            {/* Search and Toggles */}
            <Stack
              direction={{ xs: "column", md: "row" }}
              spacing={2}
              alignItems={{ xs: "stretch", md: "center" }}
            >
              <TextField
                fullWidth
                placeholder={t("catalog.search")}
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  setPage(0);
                }}
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <Iconify
                        icon="eva:search-fill"
                        sx={{ color: "text.disabled" }}
                      />
                    </InputAdornment>
                  ),
                }}
                sx={{ maxWidth: { md: 320 } }}
              />

              <FormControlLabel
                control={
                  <Switch
                    checked={nationwide}
                    onChange={(e) => {
                      setNationwide(e.target.checked);
                      setPage(0);
                    }}
                  />
                }
                label={t("catalog.filters.nationwide")}
              />

              <FormControlLabel
                control={
                  <Switch
                    checked={remote}
                    onChange={(e) => {
                      setRemote(e.target.checked);
                      setPage(0);
                    }}
                  />
                }
                label={t("catalog.filters.remote")}
              />

              <FormControlLabel
                control={
                  <Switch
                    checked={certified}
                    onChange={(e) => {
                      setCertified(e.target.checked);
                      setPage(0);
                    }}
                    color="success"
                  />
                }
                label={
                  <Stack direction="row" alignItems="center" spacing={0.5}>
                    <Iconify
                      icon="solar:verified-check-bold"
                      width={18}
                      sx={{ color: "success.main" }}
                    />
                    <span>§20-zertifiziert</span>
                  </Stack>
                }
              />

              <FormControlLabel
                control={
                  <Switch
                    checked={premiumOnly}
                    onChange={(e) => {
                      setPremiumOnly(e.target.checked);
                      setPage(0);
                    }}
                    sx={{
                      "& .MuiSwitch-switchBase.Mui-checked": {
                        color: "warning.main",
                      },
                      "& .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track":
                        {
                          backgroundColor: "warning.main",
                        },
                    }}
                  />
                }
                label={
                  <Stack direction="row" alignItems="center" spacing={0.5}>
                    <Iconify
                      icon="solar:crown-bold"
                      width={18}
                      sx={{ color: "warning.main" }}
                    />
                    <span>Nur Premium</span>
                  </Stack>
                }
              />

              {partnerProviderIds.size > 0 && (
                <FormControlLabel
                  control={
                    <Switch
                      checked={myPartnersOnly}
                      onChange={(e) => {
                        setMyPartnersOnly(e.target.checked);
                        setPage(0);
                      }}
                      color="success"
                    />
                  }
                  label={
                    <Stack direction="row" alignItems="center" spacing={0.5}>
                      <Iconify
                        icon="solar:handshake-bold"
                        width={18}
                        sx={{ color: "success.main" }}
                      />
                      <span>Nur meine Partner</span>
                    </Stack>
                  }
                />
              )}

              <Box sx={{ flexGrow: 1 }} />

              {isFiltered && (
                <Button
                  color="error"
                  startIcon={<Iconify icon="solar:trash-bin-trash-bold" />}
                  onClick={handleClearFilters}
                >
                  {t("catalog.resetFilters")}
                </Button>
              )}
            </Stack>

            {/* Category Chips */}
            <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
              {categories.map((category) => {
                const isSelected = selectedCategories.includes(category.id);
                return (
                  <Chip
                    key={category.id}
                    label={category.name}
                    icon={
                      <Iconify
                        icon={
                          CATEGORY_ICONS[category.slug] || "solar:health-bold"
                        }
                        width={18}
                      />
                    }
                    color={isSelected ? "primary" : "default"}
                    variant={isSelected ? "filled" : "outlined"}
                    onClick={() => handleCategoryToggle(category.id)}
                    sx={{
                      cursor: "pointer",
                      transition: "all 0.2s",
                      "&:hover": {
                        transform: "scale(1.05)",
                      },
                    }}
                  />
                );
              })}
            </Stack>

            {/* Phase Filter Chips */}
            <Stack
              direction="row"
              spacing={1}
              alignItems="center"
              flexWrap="wrap"
              useFlexGap
            >
              <Typography variant="body2" color="text.secondary" sx={{ mr: 1 }}>
                {t("catalog.phasesLabel")}:
              </Typography>
              {[
                {
                  phase: 1,
                  label: t("catalog.phases.1"),
                  icon: "solar:buildings-bold",
                },
                {
                  phase: 2,
                  label: t("catalog.phases.2"),
                  icon: "solar:chart-2-bold",
                },
                {
                  phase: 3,
                  label: t("catalog.phases.3"),
                  icon: "solar:calendar-mark-bold",
                },
                {
                  phase: 4,
                  label: t("catalog.phases.4"),
                  icon: "solar:rocket-2-bold",
                },
                {
                  phase: 5,
                  label: t("catalog.phases.5"),
                  icon: "solar:graph-up-bold",
                },
                {
                  phase: 6,
                  label: t("catalog.phases.6"),
                  icon: "solar:refresh-bold",
                },
              ].map(({ phase, label, icon }) => {
                const isSelected = selectedPhases.includes(phase);
                return (
                  <Chip
                    key={phase}
                    label={`${phase}. ${label}`}
                    icon={<Iconify icon={icon} width={16} />}
                    color={isSelected ? "secondary" : "default"}
                    variant={isSelected ? "filled" : "outlined"}
                    onClick={() => handlePhaseToggle(phase)}
                    size="small"
                    sx={{
                      cursor: "pointer",
                      transition: "all 0.2s",
                      "&:hover": {
                        transform: "scale(1.05)",
                      },
                    }}
                  />
                );
              })}
            </Stack>

            {/* Active Filters */}
            {isFiltered && (
              <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                {search && (
                  <Chip
                    label={`${t("catalog.searchLabel")}: ${search}`}
                    size="small"
                    onDelete={() => setSearch("")}
                  />
                )}
                {selectedCategories.map((categoryId) => {
                  const category = categories.find((c) => c.id === categoryId);
                  return category ? (
                    <Chip
                      key={categoryId}
                      label={category.name}
                      size="small"
                      color="primary"
                      variant="soft"
                      onDelete={() => handleCategoryToggle(categoryId)}
                    />
                  ) : null;
                })}
                {selectedPhases.map((phase) => (
                  <Chip
                    key={`phase-${phase}`}
                    label={`Phase ${phase}`}
                    size="small"
                    color="secondary"
                    variant="soft"
                    onDelete={() => handlePhaseToggle(phase)}
                  />
                ))}
                {nationwide && (
                  <Chip
                    label={t("catalog.filters.nationwide")}
                    size="small"
                    color="info"
                    variant="soft"
                    onDelete={() => setNationwide(false)}
                  />
                )}
                {remote && (
                  <Chip
                    label="Remote"
                    size="small"
                    color="secondary"
                    variant="soft"
                    onDelete={() => setRemote(false)}
                  />
                )}
                {premiumOnly && (
                  <Chip
                    label={t("catalog.filters.premium")}
                    size="small"
                    color="warning"
                    variant="soft"
                    icon={<Iconify icon="solar:crown-bold" width={14} />}
                    onDelete={() => setPremiumOnly(false)}
                  />
                )}
                {myPartnersOnly && (
                  <Chip
                    label={t("catalog.filters.myPartners")}
                    size="small"
                    color="success"
                    variant="soft"
                    icon={<Iconify icon="solar:handshake-bold" width={14} />}
                    onDelete={() => setMyPartnersOnly(false)}
                  />
                )}
              </Stack>
            )}
          </Stack>
        </Card>

        {/* Results */}
        {catalogLoading ? (
          <Box sx={{ py: 8, textAlign: "center" }}>
            <CircularProgress />
            <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
              {t("catalog.loading")}
            </Typography>
          </Box>
        ) : catalogEmpty ? (
          <Box sx={{ py: 8, textAlign: "center" }}>
            <Iconify
              icon="solar:box-minimalistic-bold"
              width={64}
              sx={{ color: "text.disabled", mb: 2 }}
            />
            <Typography variant="h6" color="text.secondary">
              {t("catalog.empty.title")}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {t("catalog.empty.description")}
            </Typography>
            {isFiltered && (
              <Button
                sx={{ mt: 2 }}
                onClick={handleClearFilters}
                startIcon={<Iconify icon="solar:refresh-bold" />}
              >
                {t("catalog.resetFilters")}
              </Button>
            )}
          </Box>
        ) : (
          <>
            <Grid container spacing={3}>
              {filteredProviders.map((provider) => (
                <Grid key={provider.id} size={{ xs: 12, sm: 6, md: 4, lg: 3 }}>
                  <ProviderCard
                    provider={provider}
                    isMyPartner={partnerProviderIds.has(provider.id)}
                    isBookmarked={bookmarkedProviderIds.has(provider.id)}
                    isSelected={compareProviderIds.includes(provider.id)}
                    onViewDetails={handleViewDetails}
                    onInquiry={handleInquiry}
                    onToggleBookmark={async () => {
                      if (bookmarkedProviderIds.has(provider.id)) {
                        await removePartnerBookmark(provider.id);
                      } else {
                        await addPartnerBookmark(provider.id);
                      }
                      bookmarksMutate();
                    }}
                    onToggleCompare={() => handleToggleCompare(provider.id)}
                  />
                </Grid>
              ))}
            </Grid>

            <TablePagination
              component="div"
              count={pagination.total}
              page={page}
              rowsPerPage={rowsPerPage}
              onPageChange={handleChangePage}
              onRowsPerPageChange={handleChangeRowsPerPage}
              rowsPerPageOptions={[12, 24, 48]}
              labelRowsPerPage="Pro Seite:"
              labelDisplayedRows={({ from, to, count }) =>
                `${from}–${to} von ${count}`
              }
              sx={{ mt: 3 }}
            />
          </>
        )}
      </DashboardContent>

      {/* Provider Detail Dialog */}
      <ProviderDetailDialog
        open={detailDialog.value}
        onClose={detailDialog.onFalse}
        provider={providerDetails}
        loading={providerLoading}
        onInquiry={handleInquiryFromDetail}
      />

      {/* Inquiry Form Dialog */}
      <InquiryForm
        open={inquiryDialog.value}
        onClose={inquiryDialog.onFalse}
        provider={selectedProvider}
        offering={selectedOffering}
      />

      {/* Compare Drawer */}
      <CompareDrawer
        open={compareDrawer.value}
        onClose={compareDrawer.onFalse}
        providers={compareProviders}
        onRemoveProvider={handleRemoveFromCompare}
        onViewDetails={(p) => {
          compareDrawer.onFalse();
          handleViewDetails(p);
        }}
        onInquiry={(p) => {
          compareDrawer.onFalse();
          handleInquiry(p);
        }}
        onClearAll={handleClearCompare}
      />

      {/* Floating Compare Bar */}
      <CompareBar
        count={compareProviderIds.length}
        onCompare={compareDrawer.onTrue}
        onClear={handleClearCompare}
      />
    </>
  );
}
