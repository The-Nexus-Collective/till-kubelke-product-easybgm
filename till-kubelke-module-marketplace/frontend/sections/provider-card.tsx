import type { IServiceProvider } from "src/types/marketplace";

import Box from "@mui/material/Box";
import Card from "@mui/material/Card";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Rating from "@mui/material/Rating";
import Avatar from "@mui/material/Avatar";
import Tooltip from "@mui/material/Tooltip";
import { alpha } from "@mui/material/styles";
import IconButton from "@mui/material/IconButton";
import Typography from "@mui/material/Typography";
import CardContent from "@mui/material/CardContent";
import CardActions from "@mui/material/CardActions";
import CardActionArea from "@mui/material/CardActionArea";

import { useTranslate } from "src/locales";

import { Iconify } from "src/components/iconify";

import { CATEGORY_ICONS } from "src/types/marketplace";

// ----------------------------------------------------------------------

type Props = {
  provider: IServiceProvider;
  isMyPartner?: boolean;
  isBookmarked?: boolean;
  isSelected?: boolean;
  onViewDetails: (provider: IServiceProvider) => void;
  onInquiry: (provider: IServiceProvider) => void;
  onToggleBookmark?: () => void;
  onToggleCompare?: () => void;
};

export function ProviderCard({
  provider,
  isMyPartner = false,
  isBookmarked = false,
  isSelected = false,
  onViewDetails,
  onInquiry,
  onToggleBookmark,
  onToggleCompare,
}: Props) {
  const { t } = useTranslate("marketplace");

  // Phase names for tooltips
  const PHASE_NAMES: Record<number, string> = {
    1: t("catalog.phases.1"),
    2: t("catalog.phases.2"),
    3: t("catalog.phases.3"),
    4: t("catalog.phases.4"),
    5: t("catalog.phases.5"),
    6: t("catalog.phases.6"),
  };

  const {
    companyName,
    shortDescription,
    logoUrl,
    coverImageUrl,
    categories,
    tags,
    isNationwide,
    offersRemote,
    isPremium = false,
    relevantPhases = [],
    hasCertifiedOfferings = false,
    certifications = [],
    averageRating,
    reviewCount,
    recommendRate,
  } = provider;

  // Default gradient background if no cover image
  const defaultGradient = isPremium
    ? "linear-gradient(135deg, #f6d365 0%, #fda085 100%)"
    : "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";

  return (
    <Card
      sx={{
        height: "100%",
        display: "flex",
        flexDirection: "column",
        transition: "all 0.2s ease-in-out",
        overflow: "hidden",
        position: "relative",
        ...(isPremium && {
          border: (theme) => `2px solid ${theme.palette.warning.main}`,
          boxShadow: (theme) =>
            `0 0 0 1px ${alpha(theme.palette.warning.main, 0.24)}`,
        }),
        ...(!isPremium &&
          isMyPartner && {
            border: (theme) => `2px solid ${theme.palette.success.main}`,
            boxShadow: (theme) =>
              `0 0 0 1px ${alpha(theme.palette.success.main, 0.24)}`,
          }),
        "&:hover": {
          boxShadow: (theme) => theme.shadows[8],
          transform: "translateY(-4px)",
        },
      }}
    >
      <CardActionArea
        onClick={() => onViewDetails(provider)}
        sx={{
          flex: 1,
          display: "flex",
          flexDirection: "column",
          alignItems: "stretch",
          justifyContent: "flex-start",
        }}
      >
        {/* Cover Image with Logo Overlay */}
        <Box sx={{ position: "relative" }}>
          {/* Cover Image / Gradient */}
          <Box
            sx={{
              height: 80,
              width: "100%",
              background: coverImageUrl
                ? `url(${coverImageUrl}) center/cover no-repeat`
                : defaultGradient,
            }}
          />

          {/* Premium Badge */}
          {isPremium && (
            <Chip
              size="small"
              label={t("provider.card.premiumPartner")}
              icon={<Iconify icon="solar:crown-bold" width={14} />}
              sx={{
                position: "absolute",
                top: 8,
                right: 8,
                bgcolor: "warning.main",
                color: "warning.contrastText",
                fontWeight: "bold",
                fontSize: "0.7rem",
                height: 24,
                "& .MuiChip-icon": {
                  color: "inherit",
                },
              }}
            />
          )}

          {/* My Partner Badge */}
          {isMyPartner && !isPremium && (
            <Chip
              size="small"
              label={t("provider.card.myPartner")}
              icon={<Iconify icon="solar:handshake-bold" width={14} />}
              sx={{
                position: "absolute",
                top: 8,
                right: 8,
                bgcolor: "success.main",
                color: "success.contrastText",
                fontWeight: "bold",
                fontSize: "0.7rem",
                height: 24,
                "& .MuiChip-icon": {
                  color: "inherit",
                },
              }}
            />
          )}

          {/* My Partner Badge (if also premium - show both) */}
          {isMyPartner && isPremium && (
            <Chip
              size="small"
              label={t("provider.card.myPartner")}
              icon={<Iconify icon="solar:handshake-bold" width={14} />}
              sx={{
                position: "absolute",
                top: 36,
                right: 8,
                bgcolor: "success.main",
                color: "success.contrastText",
                fontWeight: "bold",
                fontSize: "0.7rem",
                height: 24,
                "& .MuiChip-icon": {
                  color: "inherit",
                },
              }}
            />
          )}

          {/* Logo - positioned to overlap the cover */}
          <Avatar
            src={logoUrl || undefined}
            alt={companyName}
            sx={{
              position: "absolute",
              bottom: -24,
              left: 16,
              width: 56,
              height: 56,
              border: (theme) =>
                `3px solid ${isPremium ? theme.palette.warning.main : theme.palette.background.paper}`,
              bgcolor: "background.paper",
              color: "primary.main",
              fontSize: "1.5rem",
              fontWeight: "bold",
              boxShadow: 2,
            }}
          >
            {companyName.charAt(0).toUpperCase()}
          </Avatar>
        </Box>

        <CardContent sx={{ flex: 1, pb: 1, pt: 4 }}>
          {/* Company Name and Badges */}
          <Box sx={{ mb: 1.5 }}>
            <Typography variant="subtitle1" fontWeight="bold" noWrap>
              {companyName}
            </Typography>

            {/* Rating Display */}
            {reviewCount && reviewCount > 0 && (
              <Stack
                direction="row"
                spacing={0.5}
                alignItems="center"
                sx={{ mt: 0.5 }}
              >
                <Rating
                  value={averageRating || 0}
                  precision={0.5}
                  size="small"
                  readOnly
                  sx={{ fontSize: "0.9rem" }}
                />
                <Typography
                  variant="caption"
                  color="text.secondary"
                  fontWeight="medium"
                >
                  {averageRating?.toFixed(1)}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  ({reviewCount}{" "}
                  {reviewCount === 1
                    ? t("provider.card.review")
                    : t("provider.card.reviews")}
                  )
                </Typography>
                {recommendRate && recommendRate >= 80 && (
                  <Tooltip
                    title={t("provider.card.recommendRate", {
                      rate: recommendRate,
                    })}
                  >
                    <Chip
                      size="small"
                      label={`${recommendRate}%`}
                      icon={<Iconify icon="solar:like-bold" width={10} />}
                      color="success"
                      variant="soft"
                      sx={{ height: 18, fontSize: "0.65rem", ml: 0.5 }}
                    />
                  </Tooltip>
                )}
              </Stack>
            )}

            <Stack
              direction="row"
              spacing={0.5}
              flexWrap="wrap"
              useFlexGap
              sx={{ mt: 0.5 }}
            >
              {hasCertifiedOfferings && (
                <Tooltip
                  title={
                    certifications.length > 0
                      ? certifications.join(", ")
                      : "§20 SGB V zertifiziert"
                  }
                >
                  <Chip
                    size="small"
                    label="§20"
                    icon={
                      <Iconify icon="solar:verified-check-bold" width={12} />
                    }
                    color="success"
                    variant="soft"
                    sx={{ height: 20, fontSize: "0.7rem" }}
                  />
                </Tooltip>
              )}
              {isNationwide && (
                <Chip
                  size="small"
                  label={t("provider.card.nationwide")}
                  color="info"
                  variant="soft"
                  sx={{ height: 20, fontSize: "0.7rem" }}
                />
              )}
              {offersRemote && (
                <Chip
                  size="small"
                  label={t("provider.card.remote")}
                  icon={<Iconify icon="solar:monitor-bold" width={12} />}
                  variant="outlined"
                  sx={{ height: 20, fontSize: "0.7rem" }}
                />
              )}
            </Stack>
          </Box>

          {/* Short Description */}
          {shortDescription && (
            <Typography
              variant="body2"
              color="text.secondary"
              sx={{
                mb: 2,
                overflow: "hidden",
                textOverflow: "ellipsis",
                display: "-webkit-box",
                WebkitLineClamp: 3,
                WebkitBoxOrient: "vertical",
              }}
            >
              {shortDescription}
            </Typography>
          )}

          {/* Phase Indicators */}
          {relevantPhases.length > 0 && (
            <Box sx={{ mb: 1.5 }}>
              <Stack direction="row" spacing={0.5} alignItems="center">
                <Typography
                  variant="caption"
                  color="text.secondary"
                  sx={{ mr: 0.5 }}
                >
                  {t("catalog.phasesLabel")}:
                </Typography>
                {[1, 2, 3, 4, 5, 6].map((phase) => {
                  const isActive = relevantPhases.includes(phase);
                  return (
                    <Tooltip
                      key={phase}
                      title={`Phase ${phase}: ${PHASE_NAMES[phase]}`}
                    >
                      <Box
                        sx={{
                          width: 22,
                          height: 22,
                          borderRadius: "50%",
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          fontSize: "0.7rem",
                          fontWeight: 600,
                          cursor: "default",
                          transition: "all 0.2s",
                          ...(isActive
                            ? {
                                bgcolor: "primary.main",
                                color: "primary.contrastText",
                                boxShadow: (theme) =>
                                  `0 0 0 2px ${alpha(theme.palette.primary.main, 0.24)}`,
                              }
                            : {
                                bgcolor: (theme) =>
                                  alpha(theme.palette.grey[500], 0.12),
                                color: "text.disabled",
                              }),
                        }}
                      >
                        {phase}
                      </Box>
                    </Tooltip>
                  );
                })}
              </Stack>
            </Box>
          )}

          {/* Categories */}
          {categories.length > 0 && (
            <Stack
              direction="row"
              spacing={0.5}
              flexWrap="wrap"
              useFlexGap
              sx={{ mb: 1.5 }}
            >
              {categories.map((category) => (
                <Tooltip
                  key={category.id}
                  title={category.description || category.name}
                >
                  <Chip
                    size="small"
                    label={category.name}
                    icon={
                      <Iconify
                        icon={
                          CATEGORY_ICONS[category.slug] || "solar:health-bold"
                        }
                        width={14}
                      />
                    }
                    color="primary"
                    variant="soft"
                    sx={{ height: 24, fontSize: "0.75rem" }}
                  />
                </Tooltip>
              ))}
            </Stack>
          )}

          {/* Tags */}
          {tags.length > 0 && (
            <Stack direction="row" spacing={0.5} flexWrap="wrap" useFlexGap>
              {tags.slice(0, 4).map((tag) => (
                <Chip
                  key={tag.id}
                  size="small"
                  label={tag.name}
                  variant="outlined"
                  sx={{ height: 22, fontSize: "0.7rem" }}
                />
              ))}
              {tags.length > 4 && (
                <Tooltip
                  title={tags
                    .slice(4)
                    .map((tag) => tag.name)
                    .join(", ")}
                >
                  <Chip
                    size="small"
                    label={`+${tags.length - 4}`}
                    color="default"
                    sx={{ height: 22, fontSize: "0.7rem" }}
                  />
                </Tooltip>
              )}
            </Stack>
          )}
        </CardContent>
      </CardActionArea>

      <CardActions sx={{ p: 2, pt: 0, gap: 1, flexWrap: "wrap" }}>
        {/* Bookmark Toggle */}
        {onToggleBookmark && (
          <Tooltip
            title={
              isBookmarked
                ? t("provider.card.removeBookmark")
                : t("provider.card.addBookmark")
            }
          >
            <IconButton
              size="small"
              onClick={(e) => {
                e.stopPropagation();
                onToggleBookmark();
              }}
              sx={{
                color: isBookmarked ? "success.main" : "text.secondary",
                "&:hover": {
                  bgcolor: isBookmarked
                    ? (theme) => alpha(theme.palette.success.main, 0.08)
                    : (theme) => alpha(theme.palette.grey[500], 0.08),
                },
              }}
            >
              <Iconify
                icon={
                  isBookmarked ? "solar:bookmark-bold" : "solar:bookmark-linear"
                }
                width={20}
              />
            </IconButton>
          </Tooltip>
        )}

        {/* Compare Toggle - more visible */}
        {onToggleCompare && (
          <Tooltip
            title={
              isSelected
                ? t("provider.card.removeFromCompare")
                : t("provider.card.addToCompare")
            }
          >
            <IconButton
              size="small"
              onClick={(e) => {
                e.stopPropagation();
                onToggleCompare();
              }}
              sx={{
                color: isSelected ? "primary.main" : "text.secondary",
                bgcolor: isSelected
                  ? (theme) => alpha(theme.palette.primary.main, 0.12)
                  : "transparent",
                "&:hover": {
                  bgcolor: (theme) => alpha(theme.palette.primary.main, 0.16),
                },
              }}
            >
              <Iconify
                icon={
                  isSelected
                    ? "solar:check-square-bold"
                    : "solar:add-square-linear"
                }
                width={20}
              />
            </IconButton>
          </Tooltip>
        )}

        <Button
          size="small"
          variant="outlined"
          onClick={() => onViewDetails(provider)}
          startIcon={<Iconify icon="solar:eye-bold" width={16} />}
        >
          {t("provider.card.details")}
        </Button>
        <Button
          size="small"
          variant="contained"
          onClick={() => onInquiry(provider)}
          startIcon={<Iconify icon="solar:letter-bold" width={16} />}
          sx={{ ml: "auto" }}
        >
          {t("provider.card.inquiry")}
        </Button>
      </CardActions>
    </Card>
  );
}
