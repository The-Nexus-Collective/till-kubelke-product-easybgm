import type { IPartnerBookmark } from "src/actions/marketplace";
import type {
  IServiceProvider,
  IPartnerEngagement,
} from "src/types/marketplace";

import { useBoolean } from "minimal-shared/hooks";
import { useMemo, useState, useCallback } from "react";

import Box from "@mui/material/Box";
import Card from "@mui/material/Card";
import Chip from "@mui/material/Chip";
import Grid from "@mui/material/Grid";
import Stack from "@mui/material/Stack";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Avatar from "@mui/material/Avatar";
import Divider from "@mui/material/Divider";
import Skeleton from "@mui/material/Skeleton";
import Typography from "@mui/material/Typography";
import CardContent from "@mui/material/CardContent";

import { paths } from "src/routes/paths";
import { RouterLink } from "src/routes/components";

import { useTranslate } from "src/locales";
import { DashboardContent } from "src/layouts/dashboard";
import {
  useGetEngagements,
  useGetProviderDetails,
  removePartnerBookmark,
  useGetPartnerBookmarks,
} from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";
import { CrudViewHeader } from "src/components/crud-view-header";
import { CustomBreadcrumbs } from "src/components/custom-breadcrumbs";

import { InquiryForm } from "../inquiry-form";
import { ProviderDetailDialog } from "../provider-detail-dialog";

// ----------------------------------------------------------------------

export function MyPartnersView() {
  const { t } = useTranslate("marketplace");

  // Data fetching
  const { bookmarks, bookmarksLoading, bookmarksMutate } =
    useGetPartnerBookmarks();
  const { engagements, engagementsLoading } = useGetEngagements();

  // Dialogs
  const detailDialog = useBoolean();
  const inquiryDialog = useBoolean();
  const [selectedProvider, setSelectedProvider] =
    useState<IServiceProvider | null>(null);

  // Provider details for dialog
  const { provider: providerDetails, providerLoading } = useGetProviderDetails(
    detailDialog.value ? selectedProvider?.id || null : null,
  );

  // Categorize partners
  const { activeEngagements, pendingInquiries, bookmarkedPartners } =
    useMemo(() => {
      const active = engagements.filter((e) =>
        ["active", "in_progress", "completed"].includes(e.status),
      );
      const pending = engagements.filter((e) =>
        ["draft", "inquiry_sent", "proposal_received"].includes(e.status),
      );

      // Filter bookmarks that don't have an active engagement
      const engagementProviderIds = new Set(
        engagements.map((e) => e.providerId),
      );
      const onlyBookmarked = bookmarks.filter(
        (b) => !engagementProviderIds.has(b.providerId),
      );

      return {
        activeEngagements: active,
        pendingInquiries: pending,
        bookmarkedPartners: onlyBookmarked,
      };
    }, [engagements, bookmarks]);

  const isLoading = bookmarksLoading || engagementsLoading;
  const isEmpty =
    !isLoading &&
    activeEngagements.length === 0 &&
    pendingInquiries.length === 0 &&
    bookmarkedPartners.length === 0;

  // Handlers
  const handleViewDetails = useCallback(
    (provider: IServiceProvider) => {
      setSelectedProvider(provider);
      detailDialog.onTrue();
    },
    [detailDialog],
  );

  const handleInquiry = useCallback(
    (provider: IServiceProvider) => {
      setSelectedProvider(provider);
      inquiryDialog.onTrue();
    },
    [inquiryDialog],
  );

  const handleRemoveBookmark = useCallback(
    async (providerId: number) => {
      await removePartnerBookmark(providerId);
      bookmarksMutate();
    },
    [bookmarksMutate],
  );

  // Stats
  const totalPartners = activeEngagements.length + bookmarkedPartners.length;

  return (
    <>
      <DashboardContent>
        <CustomBreadcrumbs
          heading={t("partners.title")}
          links={[
            { name: t("breadcrumbs.dashboard"), href: paths.dashboard.root },
            {
              name: t("breadcrumbs.marketplace"),
              href: paths.dashboard.marketplace?.catalog,
            },
            { name: t("partners.title") },
          ]}
          action={
            <Button
              component={RouterLink}
              href={paths.dashboard.marketplace?.catalog || "#"}
              variant="contained"
              startIcon={<Iconify icon="solar:shop-2-bold" />}
            >
              {t("partners.goToMarketplace")}
            </Button>
          }
          sx={{ mb: { xs: 3, md: 5 } }}
        />

        {/* Header with gradient */}
        <CrudViewHeader
          title={t("partners.title")}
          subtitle={t("partners.info.title")}
          icon="solar:users-group-two-rounded-bold"
          gradient="success"
          stats={[
            { value: totalPartners, label: t("partners.title") },
            {
              value: activeEngagements.length,
              label: t("partners.sections.active"),
            },
            ...(pendingInquiries.length > 0
              ? [
                  {
                    value: pendingInquiries.length,
                    label: t("partners.sections.pendingInquiries"),
                  },
                ]
              : []),
          ]}
          infoTitle={t("partners.info.title")}
          infoDescription={t("partners.info.description")}
          storageKey="marketplace-partners-header-info"
        />

        {/* Loading State */}
        {isLoading && (
          <Grid container spacing={3}>
            {[1, 2, 3].map((i) => (
              <Grid key={i} size={{ xs: 12, sm: 6, md: 4 }}>
                <Card>
                  <CardContent>
                    <Stack
                      direction="row"
                      spacing={2}
                      alignItems="center"
                      sx={{ mb: 2 }}
                    >
                      <Skeleton variant="circular" width={56} height={56} />
                      <Box sx={{ flexGrow: 1 }}>
                        <Skeleton width="70%" height={24} />
                        <Skeleton width="40%" height={20} />
                      </Box>
                    </Stack>
                    <Skeleton width="100%" height={40} />
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        )}

        {/* Empty State */}
        {isEmpty && (
          <Card
            sx={{
              py: 10,
              px: 3,
              textAlign: "center",
              bgcolor: "background.neutral",
            }}
          >
            <Box
              component="img"
              src="/assets/icons/empty/ic-partners.svg"
              alt="No partners"
              sx={{
                width: 200,
                height: 200,
                mx: "auto",
                mb: 3,
                opacity: 0.6,
              }}
              onError={(e: React.SyntheticEvent<HTMLImageElement>) => {
                e.currentTarget.style.display = "none";
              }}
            />

            <Iconify
              icon="solar:users-group-two-rounded-bold-duotone"
              width={80}
              sx={{ color: "text.disabled", mb: 3 }}
            />

            <Typography variant="h5" sx={{ mb: 1 }}>
              {t("partners.empty.title")}
            </Typography>

            <Typography
              variant="body1"
              sx={{
                color: "text.secondary",
                mb: 4,
                maxWidth: 400,
                mx: "auto",
              }}
            >
              {t("partners.empty.description")}
            </Typography>

            <Button
              component={RouterLink}
              href={paths.dashboard.marketplace?.catalog || "#"}
              variant="contained"
              size="large"
              startIcon={<Iconify icon="solar:magnifer-bold" />}
            >
              {t("partners.empty.action")}
            </Button>
          </Card>
        )}

        {/* Content */}
        {!isLoading && !isEmpty && (
          <Stack spacing={4}>
            {/* Pending Inquiries Section */}
            {pendingInquiries.length > 0 && (
              <Box>
                <Typography variant="h6" sx={{ mb: 2 }}>
                  <Iconify
                    icon="solar:hourglass-bold"
                    sx={{ mr: 1, verticalAlign: "middle" }}
                  />
                  {t("partners.sections.pendingInquiries")}
                </Typography>
                <Grid container spacing={3}>
                  {pendingInquiries.map((engagement) => (
                    <Grid key={engagement.id} size={{ xs: 12, sm: 6, md: 4 }}>
                      <EngagementCard
                        engagement={engagement}
                        onViewDetails={() =>
                          handleViewDetails({
                            id: engagement.providerId,
                            companyName: engagement.providerName,
                            logoUrl: null,
                          } as IServiceProvider)
                        }
                      />
                    </Grid>
                  ))}
                </Grid>
              </Box>
            )}

            {/* Active Partners Section */}
            {activeEngagements.length > 0 && (
              <Box>
                <Typography variant="h6" sx={{ mb: 2 }}>
                  <Iconify
                    icon="solar:handshake-bold"
                    sx={{
                      mr: 1,
                      verticalAlign: "middle",
                      color: "success.main",
                    }}
                  />
                  {t("partners.sections.active")}
                </Typography>
                <Grid container spacing={3}>
                  {activeEngagements.map((engagement) => (
                    <Grid key={engagement.id} size={{ xs: 12, sm: 6, md: 4 }}>
                      <EngagementCard
                        engagement={engagement}
                        onViewDetails={() =>
                          handleViewDetails({
                            id: engagement.providerId,
                            companyName: engagement.providerName,
                            logoUrl: null,
                          } as IServiceProvider)
                        }
                        isActive
                      />
                    </Grid>
                  ))}
                </Grid>
              </Box>
            )}

            {/* Bookmarked Partners Section */}
            {bookmarkedPartners.length > 0 && (
              <Box>
                <Typography variant="h6" sx={{ mb: 1 }}>
                  <Iconify
                    icon="solar:bookmark-bold"
                    sx={{ mr: 1, verticalAlign: "middle", color: "info.main" }}
                  />
                  {t("partners.sections.bookmarked")}
                </Typography>
                <Alert severity="info" sx={{ mb: 2 }}>
                  {t("partners.bookmarkedInfo")}
                </Alert>
                <Grid container spacing={3}>
                  {bookmarkedPartners.map((bookmark) => (
                    <Grid key={bookmark.id} size={{ xs: 12, sm: 6, md: 4 }}>
                      <BookmarkCard
                        bookmark={bookmark}
                        onViewDetails={() =>
                          handleViewDetails({
                            id: bookmark.providerId,
                            companyName: bookmark.providerName,
                            logoUrl: bookmark.providerLogo,
                          } as IServiceProvider)
                        }
                        onInquiry={() =>
                          handleInquiry({
                            id: bookmark.providerId,
                            companyName: bookmark.providerName,
                            logoUrl: bookmark.providerLogo,
                          } as IServiceProvider)
                        }
                        onRemove={() =>
                          handleRemoveBookmark(bookmark.providerId)
                        }
                      />
                    </Grid>
                  ))}
                </Grid>
              </Box>
            )}
          </Stack>
        )}
      </DashboardContent>

      {/* Provider Detail Dialog */}
      <ProviderDetailDialog
        open={detailDialog.value}
        onClose={detailDialog.onFalse}
        provider={providerDetails}
        loading={providerLoading}
        onInquiry={(p) => {
          detailDialog.onFalse();
          handleInquiry(p as unknown as IServiceProvider);
        }}
      />

      {/* Inquiry Form Dialog */}
      <InquiryForm
        open={inquiryDialog.value}
        onClose={inquiryDialog.onFalse}
        provider={selectedProvider}
      />
    </>
  );
}

// ----------------------------------------------------------------------

interface EngagementCardProps {
  engagement: IPartnerEngagement;
  onViewDetails: () => void;
  isActive?: boolean;
}

function EngagementCard({
  engagement,
  onViewDetails,
  isActive,
}: EngagementCardProps) {
  const { t } = useTranslate("marketplace");

  const statusConfig: Record<
    string,
    {
      color: "default" | "primary" | "success" | "warning" | "error" | "info";
      label: string;
    }
  > = {
    draft: { color: "default", label: "Entwurf" },
    inquiry_sent: { color: "info", label: "Anfrage gesendet" },
    proposal_received: { color: "warning", label: "Angebot erhalten" },
    active: { color: "success", label: "Aktiv" },
    in_progress: { color: "primary", label: "In Bearbeitung" },
    completed: { color: "success", label: "Abgeschlossen" },
    cancelled: { color: "error", label: "Abgebrochen" },
  };

  const status = statusConfig[engagement.status] || {
    color: "default",
    label: engagement.status,
  };

  return (
    <Card
      sx={{
        height: "100%",
        transition: "all 0.2s",
        "&:hover": {
          boxShadow: (theme) => theme.shadows[8],
          transform: "translateY(-2px)",
        },
      }}
    >
      <CardContent>
        <Stack
          direction="row"
          spacing={2}
          alignItems="flex-start"
          sx={{ mb: 2 }}
        >
          <Avatar
            src={undefined}
            alt={engagement.providerName}
            sx={{
              width: 56,
              height: 56,
              bgcolor: "primary.lighter",
              color: "primary.main",
            }}
          >
            {engagement.providerName?.charAt(0)}
          </Avatar>
          <Box sx={{ flexGrow: 1, minWidth: 0 }}>
            <Typography variant="subtitle1" noWrap>
              {engagement.providerName || t("partners.unknown")}
            </Typography>
            <Typography variant="body2" color="text.secondary" noWrap>
              {engagement.offeringTitle || t("partners.generalInquiry")}
            </Typography>
          </Box>
          <Chip label={status.label} color={status.color} size="small" />
        </Stack>

        {isActive && engagement.scheduledDate && (
          <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 1 }}>
            <Iconify
              icon="solar:calendar-bold"
              width={16}
              sx={{ color: "text.secondary" }}
            />
            <Typography variant="caption" color="text.secondary">
              {t("partners.scheduledFor")}:{" "}
              {new Date(engagement.scheduledDate).toLocaleDateString("de-DE")}
            </Typography>
          </Stack>
        )}

        <Divider sx={{ my: 2 }} />

        <Button
          variant="outlined"
          size="small"
          fullWidth
          onClick={onViewDetails}
          startIcon={<Iconify icon="solar:eye-bold" />}
        >
          Details
        </Button>
      </CardContent>
    </Card>
  );
}

// ----------------------------------------------------------------------

interface BookmarkCardProps {
  bookmark: IPartnerBookmark;
  onViewDetails: () => void;
  onInquiry: () => void;
  onRemove: () => void;
}

function BookmarkCard({
  bookmark,
  onViewDetails,
  onInquiry,
  onRemove,
}: BookmarkCardProps) {
  const { t } = useTranslate("marketplace");

  return (
    <Card
      sx={{
        height: "100%",
        transition: "all 0.2s",
        "&:hover": {
          boxShadow: (theme) => theme.shadows[8],
          transform: "translateY(-2px)",
        },
      }}
    >
      <CardContent>
        <Stack
          direction="row"
          spacing={2}
          alignItems="flex-start"
          sx={{ mb: 2 }}
        >
          <Avatar
            src={bookmark.providerLogo || undefined}
            alt={bookmark.providerName}
            sx={{
              width: 56,
              height: 56,
              bgcolor: "info.lighter",
              color: "info.main",
            }}
          >
            {bookmark.providerName?.charAt(0)}
          </Avatar>
          <Box sx={{ flexGrow: 1, minWidth: 0 }}>
            <Typography variant="subtitle1" noWrap>
              {bookmark.providerName}
            </Typography>
            <Stack direction="row" spacing={0.5} alignItems="center">
              <Iconify
                icon="solar:bookmark-bold"
                width={14}
                sx={{ color: "info.main" }}
              />
              <Typography variant="caption" color="text.secondary">
                {t("partners.bookmarked")}
              </Typography>
            </Stack>
          </Box>
          <Button
            size="small"
            color="inherit"
            onClick={onRemove}
            sx={{ minWidth: "auto", p: 0.5 }}
          >
            <Iconify icon="solar:close-circle-bold" width={20} />
          </Button>
        </Stack>

        {bookmark.note && (
          <Typography
            variant="body2"
            color="text.secondary"
            sx={{
              mb: 2,
              display: "-webkit-box",
              WebkitLineClamp: 2,
              WebkitBoxOrient: "vertical",
              overflow: "hidden",
            }}
          >
            {bookmark.note}
          </Typography>
        )}

        <Typography
          variant="caption"
          color="text.disabled"
          sx={{ display: "block", mb: 2 }}
        >
          {t("partners.bookmarkedOn")}:{" "}
          {new Date(bookmark.createdAt).toLocaleDateString("de-DE")}
        </Typography>

        <Divider sx={{ my: 2 }} />

        <Stack direction="row" spacing={1}>
          <Button
            variant="outlined"
            size="small"
            fullWidth
            onClick={onViewDetails}
            startIcon={<Iconify icon="solar:eye-bold" />}
          >
            Details
          </Button>
          <Button
            variant="contained"
            size="small"
            fullWidth
            onClick={onInquiry}
            startIcon={<Iconify icon="solar:letter-bold" />}
          >
            {t("partners.sendInquiry")}
          </Button>
        </Stack>
      </CardContent>
    </Card>
  );
}

