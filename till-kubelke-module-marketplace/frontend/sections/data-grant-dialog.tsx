import type {
  IPartnerEngagement,
  TDataScopeSensitivity,
} from "src/types/marketplace";

import { useState, useCallback } from "react";

import Box from "@mui/material/Box";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import Switch from "@mui/material/Switch";
import Divider from "@mui/material/Divider";
import Typography from "@mui/material/Typography";
import DialogTitle from "@mui/material/DialogTitle";
import DialogContent from "@mui/material/DialogContent";
import DialogActions from "@mui/material/DialogActions";
import FormControlLabel from "@mui/material/FormControlLabel";
import CircularProgress from "@mui/material/CircularProgress";

import { Iconify } from "src/components/iconify";

import { DATA_SENSITIVITY_OPTIONS } from "src/types/marketplace";

// ----------------------------------------------------------------------

// Data scope definitions (should match backend DataScopeRegistry)
const DATA_SCOPES: Record<
  string,
  {
    label: string;
    description: string;
    sensitivity: TDataScopeSensitivity;
    icon: string;
  }
> = {
  employee_count: {
    label: "Mitarbeiteranzahl",
    description: "Anzahl der Mitarbeiter (keine Namen)",
    sensitivity: "low",
    icon: "solar:users-group-two-rounded-bold",
  },
  location: {
    label: "Standort",
    description: "Firmenstandort für Vor-Ort-Termine",
    sensitivity: "low",
    icon: "solar:map-point-bold",
  },
  goals: {
    label: "BGM-Ziele",
    description: "Definierte Gesundheitsziele aus Phase 1",
    sensitivity: "low",
    icon: "solar:target-bold",
  },
  budget: {
    label: "Budget",
    description: "Verfügbares Budget für Maßnahmen",
    sensitivity: "low",
    icon: "solar:wallet-bold",
  },
  dietary_preferences: {
    label: "Ernährungspräferenzen",
    description: "Aggregierte Diätanforderungen (vegan, vegetarisch, etc.)",
    sensitivity: "low",
    icon: "solar:chef-hat-bold",
  },
  workstation_types: {
    label: "Arbeitsplatztypen",
    description: "Arten von Arbeitsplätzen (Büro, Produktion, Homeoffice)",
    sensitivity: "low",
    icon: "solar:monitor-bold",
  },
  survey_results: {
    label: "Umfrage-Ergebnisse",
    description: "Anonymisierte Umfrageergebnisse für Analyse",
    sensitivity: "medium",
    icon: "solar:chart-2-bold",
  },
  kpi_baseline: {
    label: "KPI-Ausgangswerte",
    description: "Krankenstand, Fluktuation als Baseline",
    sensitivity: "medium",
    icon: "solar:graph-up-bold",
  },
  complaint_data: {
    label: "Beschwerdedaten",
    description: "Anonymisierte Gesundheitsbeschwerden",
    sensitivity: "medium",
    icon: "solar:danger-triangle-bold",
  },
  previous_events: {
    label: "Frühere Maßnahmen",
    description: "Welche Maßnahmen wurden bereits durchgeführt",
    sensitivity: "medium",
    icon: "solar:history-bold",
  },
  floor_plan: {
    label: "Grundriss",
    description: "Grundriss für Begehungsplanung",
    sensitivity: "medium",
    icon: "solar:map-bold",
  },
  employee_list: {
    label: "Mitarbeiterliste",
    description: "Namen und E-Mail-Adressen für Einladungen",
    sensitivity: "high",
    icon: "solar:users-group-rounded-bold",
  },
  employee_emails: {
    label: "Mitarbeiter-E-Mails",
    description: "E-Mail-Adressen für Umfrage-Einladungen",
    sensitivity: "high",
    icon: "solar:letter-bold",
  },
};

type Props = {
  open: boolean;
  onClose: () => void;
  engagement: IPartnerEngagement;
  onGrant?: (scopes: string[]) => Promise<void>;
};

export function DataGrantDialog({ open, onClose, engagement, onGrant }: Props) {
  const [selectedScopes, setSelectedScopes] = useState<string[]>(
    engagement.grantedDataScopes || [],
  );
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Mock required scopes from offering (in real app, this comes from the offering)
  const requiredScopes = ["employee_count", "goals", "survey_results"];

  const handleToggleScope = useCallback((scope: string) => {
    setSelectedScopes((prev) =>
      prev.includes(scope) ? prev.filter((s) => s !== scope) : [...prev, scope],
    );
    setError(null);
  }, []);

  const handleSelectAll = useCallback(() => {
    setSelectedScopes(requiredScopes);
    setError(null);
  }, [requiredScopes]);

  const handleSubmit = useCallback(async () => {
    if (!onGrant) return;

    setIsSubmitting(true);
    setError(null);

    try {
      await onGrant(selectedScopes);
      onClose();
    } catch (err) {
      setError(
        err instanceof Error ? err.message : "Fehler beim Freigeben der Daten",
      );
    } finally {
      setIsSubmitting(false);
    }
  }, [onGrant, selectedScopes, onClose]);

  const getSensitivityConfig = (sensitivity: TDataScopeSensitivity) =>
    DATA_SENSITIVITY_OPTIONS.find((opt) => opt.value === sensitivity) ||
    DATA_SENSITIVITY_OPTIONS[0];

  const renderScopeItem = (scopeKey: string) => {
    const scope = DATA_SCOPES[scopeKey];
    if (!scope) return null;

    const sensitivityConfig = getSensitivityConfig(scope.sensitivity);
    const isSelected = selectedScopes.includes(scopeKey);
    const isAlreadyGranted = engagement.grantedDataScopes?.includes(scopeKey);

    return (
      <Box
        key={scopeKey}
        sx={{
          p: 2,
          border: 1,
          borderColor: isSelected ? "primary.main" : "divider",
          borderRadius: 1.5,
          bgcolor: isSelected ? "primary.lighter" : "background.paper",
          opacity: isAlreadyGranted ? 0.7 : 1,
          transition: "all 0.2s",
        }}
      >
        <Stack
          direction="row"
          alignItems="flex-start"
          justifyContent="space-between"
        >
          <Stack
            direction="row"
            spacing={1.5}
            alignItems="flex-start"
            sx={{ flex: 1 }}
          >
            <Iconify
              icon={scope.icon}
              width={24}
              color="primary.main"
              sx={{ mt: 0.5 }}
            />
            <Box>
              <Stack direction="row" spacing={1} alignItems="center">
                <Typography variant="subtitle2">{scope.label}</Typography>
                <Chip
                  size="small"
                  label={sensitivityConfig.label}
                  color={sensitivityConfig.color}
                  sx={{ height: 18, fontSize: "0.65rem" }}
                />
              </Stack>
              <Typography
                variant="caption"
                color="text.secondary"
                sx={{ mt: 0.5, display: "block" }}
              >
                {scope.description}
              </Typography>
            </Box>
          </Stack>
          <FormControlLabel
            control={
              <Switch
                checked={isSelected}
                onChange={() => handleToggleScope(scopeKey)}
                disabled={isAlreadyGranted}
                color="primary"
              />
            }
            label=""
            sx={{ m: 0 }}
          />
        </Stack>
      </Box>
    );
  };

  const groupedScopes = {
    low: requiredScopes.filter((s) => DATA_SCOPES[s]?.sensitivity === "low"),
    medium: requiredScopes.filter(
      (s) => DATA_SCOPES[s]?.sensitivity === "medium",
    ),
    high: requiredScopes.filter((s) => DATA_SCOPES[s]?.sensitivity === "high"),
  };

  const hasHighSensitivity = selectedScopes.some(
    (s) => DATA_SCOPES[s]?.sensitivity === "high",
  );

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="sm"
      fullWidth
      PaperProps={{ sx: { borderRadius: 2 } }}
    >
      <DialogTitle>
        <Stack direction="row" alignItems="center" spacing={1}>
          <Iconify icon="solar:share-bold" width={24} color="primary.main" />
          <span>Daten für Partner freigeben</span>
        </Stack>
      </DialogTitle>

      <DialogContent dividers>
        <Stack spacing={3}>
          <Alert
            severity="info"
            icon={<Iconify icon="solar:info-circle-bold" width={24} />}
          >
            <Typography variant="body2">
              Der Partner <strong>{engagement.providerName}</strong> benötigt
              folgende Daten für die Dienstleistung &quot;
              {engagement.offeringTitle}&quot;.
            </Typography>
          </Alert>

          {/* Low Sensitivity */}
          {groupedScopes.low.length > 0 && (
            <Box>
              <Typography
                variant="overline"
                color="success.main"
                sx={{ mb: 1.5, display: "block" }}
              >
                <Iconify
                  icon="solar:shield-check-bold"
                  width={16}
                  sx={{ mr: 0.5, verticalAlign: "text-bottom" }}
                />
                Allgemeine Metadaten
              </Typography>
              <Stack spacing={1.5}>
                {groupedScopes.low.map(renderScopeItem)}
              </Stack>
            </Box>
          )}

          {/* Medium Sensitivity */}
          {groupedScopes.medium.length > 0 && (
            <Box>
              <Typography
                variant="overline"
                color="warning.main"
                sx={{ mb: 1.5, display: "block" }}
              >
                <Iconify
                  icon="solar:shield-warning-bold"
                  width={16}
                  sx={{ mr: 0.5, verticalAlign: "text-bottom" }}
                />
                Anonymisierte Daten
              </Typography>
              <Stack spacing={1.5}>
                {groupedScopes.medium.map(renderScopeItem)}
              </Stack>
            </Box>
          )}

          {/* High Sensitivity */}
          {groupedScopes.high.length > 0 && (
            <Box>
              <Typography
                variant="overline"
                color="error.main"
                sx={{ mb: 1.5, display: "block" }}
              >
                <Iconify
                  icon="solar:shield-bold"
                  width={16}
                  sx={{ mr: 0.5, verticalAlign: "text-bottom" }}
                />
                Personenbezogene Daten
              </Typography>
              <Stack spacing={1.5}>
                {groupedScopes.high.map(renderScopeItem)}
              </Stack>
            </Box>
          )}

          {/* GDPR Warning */}
          {hasHighSensitivity && (
            <Alert
              severity="warning"
              icon={<Iconify icon="solar:danger-triangle-bold" width={24} />}
            >
              <Typography variant="body2">
                <strong>Hinweis:</strong> Sie sind dabei, personenbezogene Daten
                zu teilen. Stellen Sie sicher, dass entsprechende Vereinbarungen
                (AV-Vertrag) mit dem Partner bestehen.
              </Typography>
            </Alert>
          )}

          {error && <Alert severity="error">{error}</Alert>}
        </Stack>
      </DialogContent>

      <Divider />

      <DialogActions sx={{ px: 3, py: 2 }}>
        <Stack
          direction="row"
          justifyContent="space-between"
          sx={{ width: "100%" }}
        >
          <Button variant="text" onClick={handleSelectAll}>
            Alle auswählen
          </Button>
          <Stack direction="row" spacing={1}>
            <Button
              variant="outlined"
              onClick={onClose}
              disabled={isSubmitting}
            >
              Abbrechen
            </Button>
            <Button
              variant="contained"
              onClick={handleSubmit}
              disabled={isSubmitting || selectedScopes.length === 0}
              startIcon={
                isSubmitting ? (
                  <CircularProgress size={18} color="inherit" />
                ) : (
                  <Iconify icon="solar:share-bold" width={18} />
                )
              }
            >
              {selectedScopes.length} Bereiche freigeben
            </Button>
          </Stack>
        </Stack>
      </DialogActions>
    </Dialog>
  );
}

