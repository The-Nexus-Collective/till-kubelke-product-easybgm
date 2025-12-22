import type { IServiceProvider } from "src/types/marketplace";

import Box from "@mui/material/Box";
import Chip from "@mui/material/Chip";
import Table from "@mui/material/Table";
import Stack from "@mui/material/Stack";
import Badge from "@mui/material/Badge";
import Button from "@mui/material/Button";
import Drawer from "@mui/material/Drawer";
import Avatar from "@mui/material/Avatar";
import Rating from "@mui/material/Rating";
import Tooltip from "@mui/material/Tooltip";
import TableRow from "@mui/material/TableRow";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableHead from "@mui/material/TableHead";
import IconButton from "@mui/material/IconButton";
import Typography from "@mui/material/Typography";
import { alpha, useTheme } from "@mui/material/styles";
import TableContainer from "@mui/material/TableContainer";

import { Iconify } from "src/components/iconify";

import { CATEGORY_ICONS } from "src/types/marketplace";

// ----------------------------------------------------------------------

interface Props {
  open: boolean;
  onClose: () => void;
  providers: IServiceProvider[];
  onRemoveProvider: (providerId: number) => void;
  onViewDetails: (provider: IServiceProvider) => void;
  onInquiry: (provider: IServiceProvider) => void;
  onClearAll: () => void;
}

// Phase names for display
const PHASE_NAMES: Record<number, string> = {
  1: "Strukturen",
  2: "Analyse",
  3: "Planung",
  4: "Umsetzung",
  5: "Evaluation",
  6: "Nachhaltigkeit",
};

export function CompareDrawer({
  open,
  onClose,
  providers,
  onRemoveProvider,
  onViewDetails,
  onInquiry,
  onClearAll,
}: Props) {
  const theme = useTheme();

  if (providers.length === 0) {
    return null;
  }

  // Helper to render a comparison row
  const renderRow = (
    label: string,
    getValue: (p: IServiceProvider) => React.ReactNode,
  ) => (
    <TableRow>
      <TableCell
        component="th"
        scope="row"
        sx={{
          fontWeight: 600,
          bgcolor: (t) => alpha(t.palette.grey[500], 0.08),
          position: "sticky",
          left: 0,
          zIndex: 1,
          borderRight: 1,
          borderColor: "divider",
        }}
      >
        {label}
      </TableCell>
      {providers.map((provider) => (
        <TableCell
          key={provider.id}
          align="center"
          sx={{ minWidth: 200, maxWidth: 280 }}
        >
          {getValue(provider)}
        </TableCell>
      ))}
    </TableRow>
  );

  return (
    <Drawer
      anchor="bottom"
      open={open}
      onClose={onClose}
      PaperProps={{
        sx: {
          maxHeight: "80vh",
          borderTopLeftRadius: 16,
          borderTopRightRadius: 16,
        },
      }}
    >
      {/* Header */}
      <Box sx={{ p: 2, borderBottom: 1, borderColor: "divider" }}>
        <Stack
          direction="row"
          justifyContent="space-between"
          alignItems="center"
        >
          <Stack direction="row" spacing={2} alignItems="center">
            <Badge badgeContent={providers.length} color="primary">
              <Iconify icon="solar:sort-by-alphabet-bold" width={28} />
            </Badge>
            <Typography variant="h6">Anbieter vergleichen</Typography>
          </Stack>
          <Stack direction="row" spacing={1}>
            <Button
              size="small"
              color="inherit"
              onClick={onClearAll}
              startIcon={<Iconify icon="solar:trash-bin-2-bold" />}
            >
              Alle entfernen
            </Button>
            <IconButton onClick={onClose}>
              <Iconify icon="solar:close-circle-bold" />
            </IconButton>
          </Stack>
        </Stack>
      </Box>

      {/* Comparison Table */}
      <TableContainer sx={{ maxHeight: "calc(80vh - 80px)" }}>
        <Table stickyHeader size="small">
          <TableHead>
            <TableRow>
              <TableCell
                sx={{
                  width: 150,
                  bgcolor: "background.paper",
                  position: "sticky",
                  left: 0,
                  zIndex: 3,
                  borderRight: 1,
                  borderColor: "divider",
                }}
              >
                Kriterium
              </TableCell>
              {providers.map((provider) => (
                <TableCell
                  key={provider.id}
                  align="center"
                  sx={{
                    minWidth: 200,
                    maxWidth: 280,
                    bgcolor: provider.isPremium
                      ? alpha(theme.palette.warning.main, 0.08)
                      : "background.paper",
                  }}
                >
                  <Stack spacing={1} alignItems="center">
                    <Box sx={{ position: "relative" }}>
                      <Avatar
                        src={provider.logoUrl || undefined}
                        sx={{ width: 48, height: 48 }}
                      >
                        {provider.companyName.charAt(0)}
                      </Avatar>
                      <IconButton
                        size="small"
                        onClick={() => onRemoveProvider(provider.id)}
                        sx={{
                          position: "absolute",
                          top: -8,
                          right: -8,
                          bgcolor: "error.main",
                          color: "error.contrastText",
                          width: 20,
                          height: 20,
                          "&:hover": { bgcolor: "error.dark" },
                        }}
                      >
                        <Iconify icon="solar:close-circle-bold" width={14} />
                      </IconButton>
                    </Box>
                    <Typography
                      variant="subtitle2"
                      noWrap
                      sx={{ maxWidth: 180 }}
                    >
                      {provider.companyName}
                    </Typography>
                    {provider.isPremium && (
                      <Chip
                        size="small"
                        label="Premium"
                        icon={<Iconify icon="solar:crown-bold" width={12} />}
                        color="warning"
                        sx={{ height: 20, fontSize: "0.65rem" }}
                      />
                    )}
                  </Stack>
                </TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {/* Rating */}
            {renderRow("Bewertung", (p) =>
              p.reviewCount && p.reviewCount > 0 ? (
                <Stack
                  direction="row"
                  spacing={0.5}
                  justifyContent="center"
                  alignItems="center"
                >
                  <Rating
                    value={p.averageRating || 0}
                    precision={0.5}
                    size="small"
                    readOnly
                  />
                  <Typography variant="caption">({p.reviewCount})</Typography>
                </Stack>
              ) : (
                <Typography variant="caption" color="text.disabled">
                  Noch keine
                </Typography>
              ),
            )}

            {/* Recommend Rate */}
            {renderRow("Empfehlungsrate", (p) =>
              p.recommendRate ? (
                <Stack
                  direction="row"
                  spacing={0.5}
                  justifyContent="center"
                  alignItems="center"
                >
                  <Iconify
                    icon="solar:like-bold"
                    width={16}
                    color={
                      p.recommendRate >= 80 ? "success.main" : "text.secondary"
                    }
                  />
                  <Typography
                    variant="body2"
                    fontWeight={p.recommendRate >= 80 ? 600 : 400}
                    color={
                      p.recommendRate >= 80 ? "success.main" : "text.secondary"
                    }
                  >
                    {p.recommendRate}%
                  </Typography>
                </Stack>
              ) : (
                <Typography variant="caption" color="text.disabled">
                  –
                </Typography>
              ),
            )}

            {/* Certified */}
            {renderRow("§20 Zertifizierung", (p) =>
              p.hasCertifiedOfferings ? (
                <Chip
                  size="small"
                  label="Zertifiziert"
                  icon={<Iconify icon="solar:verified-check-bold" width={14} />}
                  color="success"
                  variant="soft"
                  sx={{ height: 24 }}
                />
              ) : (
                <Typography variant="caption" color="text.disabled">
                  –
                </Typography>
              ),
            )}

            {/* Nationwide */}
            {renderRow("Bundesweit", (p) => (
              <Iconify
                icon={
                  p.isNationwide
                    ? "solar:check-circle-bold"
                    : "solar:close-circle-linear"
                }
                width={20}
                color={p.isNationwide ? "success.main" : "text.disabled"}
              />
            ))}

            {/* Remote */}
            {renderRow("Remote möglich", (p) => (
              <Iconify
                icon={
                  p.offersRemote
                    ? "solar:check-circle-bold"
                    : "solar:close-circle-linear"
                }
                width={20}
                color={p.offersRemote ? "success.main" : "text.disabled"}
              />
            ))}

            {/* Categories */}
            {renderRow("Kategorien", (p) => (
              <Stack
                direction="row"
                spacing={0.5}
                flexWrap="wrap"
                justifyContent="center"
                useFlexGap
              >
                {p.categories.map((cat) => (
                  <Tooltip key={cat.id} title={cat.name}>
                    <Chip
                      size="small"
                      icon={
                        <Iconify
                          icon={CATEGORY_ICONS[cat.slug] || "solar:health-bold"}
                          width={12}
                        />
                      }
                      label={cat.name}
                      variant="soft"
                      color="primary"
                      sx={{ height: 22, fontSize: "0.65rem" }}
                    />
                  </Tooltip>
                ))}
              </Stack>
            ))}

            {/* Phases */}
            {renderRow("BGM-Phasen", (p) => (
              <Stack direction="row" spacing={0.5} justifyContent="center">
                {[1, 2, 3, 4, 5, 6].map((phase) => {
                  const isActive = p.relevantPhases.includes(phase);
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
                          fontSize: "0.65rem",
                          fontWeight: 600,
                          ...(isActive
                            ? {
                                bgcolor: "primary.main",
                                color: "primary.contrastText",
                              }
                            : {
                                bgcolor: alpha(theme.palette.grey[500], 0.16),
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
            ))}

            {/* Certifications */}
            {renderRow("Zertifikate", (p) =>
              p.certifications.length > 0 ? (
                <Stack
                  direction="row"
                  spacing={0.5}
                  flexWrap="wrap"
                  justifyContent="center"
                  useFlexGap
                >
                  {p.certifications.slice(0, 3).map((cert, idx) => (
                    <Chip
                      key={idx}
                      size="small"
                      label={cert}
                      variant="outlined"
                      sx={{ height: 20, fontSize: "0.6rem" }}
                    />
                  ))}
                  {p.certifications.length > 3 && (
                    <Chip
                      size="small"
                      label={`+${p.certifications.length - 3}`}
                      sx={{ height: 20, fontSize: "0.6rem" }}
                    />
                  )}
                </Stack>
              ) : (
                <Typography variant="caption" color="text.disabled">
                  –
                </Typography>
              ),
            )}

            {/* Actions Row */}
            <TableRow>
              <TableCell
                component="th"
                scope="row"
                sx={{
                  fontWeight: 600,
                  bgcolor: (t) => alpha(t.palette.grey[500], 0.08),
                  position: "sticky",
                  left: 0,
                  zIndex: 1,
                  borderRight: 1,
                  borderColor: "divider",
                }}
              >
                Aktionen
              </TableCell>
              {providers.map((provider) => (
                <TableCell key={provider.id} align="center" sx={{ py: 2 }}>
                  <Stack direction="row" spacing={1} justifyContent="center">
                    <Button
                      size="small"
                      variant="outlined"
                      onClick={() => onViewDetails(provider)}
                      startIcon={<Iconify icon="solar:eye-bold" width={16} />}
                    >
                      Details
                    </Button>
                    <Button
                      size="small"
                      variant="contained"
                      onClick={() => onInquiry(provider)}
                      startIcon={
                        <Iconify icon="solar:letter-bold" width={16} />
                      }
                    >
                      Anfragen
                    </Button>
                  </Stack>
                </TableCell>
              ))}
            </TableRow>
          </TableBody>
        </Table>
      </TableContainer>
    </Drawer>
  );
}

// ----------------------------------------------------------------------
// Floating Compare Bar (shows when providers are selected)
// ----------------------------------------------------------------------

interface CompareBarProps {
  count: number;
  onCompare: () => void;
  onClear: () => void;
}

export function CompareBar({ count, onCompare, onClear }: CompareBarProps) {
  if (count === 0) {
    return null;
  }

  return (
    <Box
      sx={{
        position: "fixed",
        bottom: 24,
        left: "50%",
        transform: "translateX(-50%)",
        zIndex: 1200,
        bgcolor: "background.paper",
        borderRadius: 2,
        boxShadow: (theme) => theme.shadows[12],
        border: 1,
        borderColor: "divider",
        p: 1.5,
        pr: 2,
      }}
    >
      <Stack direction="row" spacing={2} alignItems="center">
        <Badge badgeContent={count} color="primary">
          <Iconify icon="solar:sort-by-alphabet-bold" width={24} />
        </Badge>
        <Typography variant="body2" fontWeight={600}>
          {count} {count === 1 ? "Anbieter" : "Anbieter"} ausgewählt
        </Typography>
        <Button
          size="small"
          variant="contained"
          onClick={onCompare}
          disabled={count < 2}
          startIcon={<Iconify icon="solar:chart-square-bold" />}
        >
          Vergleichen
        </Button>
        <IconButton size="small" onClick={onClear} color="error">
          <Iconify icon="solar:trash-bin-2-bold" width={18} />
        </IconButton>
      </Stack>
    </Box>
  );
}

