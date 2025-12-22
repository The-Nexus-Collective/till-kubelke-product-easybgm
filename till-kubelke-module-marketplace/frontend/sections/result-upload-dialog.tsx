import type { IPartnerEngagement } from "src/types/marketplace";

import { useState } from "react";

import Box from "@mui/material/Box";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import Select from "@mui/material/Select";
import MenuItem from "@mui/material/MenuItem";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import InputLabel from "@mui/material/InputLabel";
import DialogTitle from "@mui/material/DialogTitle";
import FormControl from "@mui/material/FormControl";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";

import { uploadResult } from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";

// ----------------------------------------------------------------------

// Output types with their labels and integration points
const OUTPUT_TYPES = [
  {
    value: "copsoq_analysis",
    label: "COPSOQ-Auswertung",
    description: "Ergebnisse der Mitarbeiterbefragung",
    integrationPoint: "phase_2.analysis",
    formats: ["json", "pdf"],
  },
  {
    value: "intervention_plan",
    label: "Maßnahmenplan",
    description: "Vorschläge für BGM-Maßnahmen",
    integrationPoint: "phase_3.concept",
    formats: ["json", "pdf"],
  },
  {
    value: "participation_stats",
    label: "Teilnahme-Statistik",
    description: "Aggregierte Teilnahmedaten",
    integrationPoint: "kpi.custom",
    formats: ["json"],
  },
  {
    value: "health_report",
    label: "Gesundheitsbericht",
    description: "Umfassender Analysebericht",
    integrationPoint: "phase_2.analysis",
    formats: ["pdf"],
  },
  {
    value: "gefaehrdungsbeurteilung",
    label: "Gefährdungsbeurteilung",
    description: "Psychische Belastungsanalyse",
    integrationPoint: "legal.gefaehrdungsbeurteilung",
    formats: ["pdf"],
  },
  {
    value: "ergonomic_assessment",
    label: "Ergonomie-Bewertung",
    description: "Arbeitsplatzanalyse",
    integrationPoint: "phase_4.intervention",
    formats: ["json", "pdf"],
  },
];

type Props = {
  open: boolean;
  onClose: () => void;
  engagement: IPartnerEngagement;
  onSuccess?: () => void;
};

export function ResultUploadDialog({
  open,
  onClose,
  engagement,
  onSuccess,
}: Props) {
  const [selectedType, setSelectedType] = useState("");
  const [summary, setSummary] = useState("");
  const [fileUrl, setFileUrl] = useState("");
  const [jsonData, setJsonData] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selectedOutputType = OUTPUT_TYPES.find((t) => t.value === selectedType);

  const handleSubmit = async () => {
    if (!selectedType) {
      setError("Bitte wählen Sie einen Ergebnistyp");
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      let parsedData = {};

      // Parse JSON data if provided
      if (jsonData.trim()) {
        try {
          parsedData = JSON.parse(jsonData);
        } catch (e) {
          setError("Ungültiges JSON-Format");
          setIsSubmitting(false);
          return;
        }
      }

      await uploadResult(engagement.id, {
        outputType: selectedType,
        data: parsedData,
        fileUrl: fileUrl || undefined,
        summary: summary || undefined,
      });

      // Reset form
      setSelectedType("");
      setSummary("");
      setFileUrl("");
      setJsonData("");

      onSuccess?.();
      onClose();
    } catch (err: any) {
      setError(err.message || "Fehler beim Hochladen");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      setError(null);
      onClose();
    }
  };

  return (
    <Dialog open={open} onClose={handleClose} maxWidth="md" fullWidth>
      <DialogTitle>
        <Stack direction="row" alignItems="center" spacing={2}>
          <Iconify icon="solar:upload-bold" width={28} />
          <Box>
            <Typography variant="h6">Ergebnis hochladen</Typography>
            <Typography variant="body2" color="text.secondary">
              {engagement.offeringTitle} – {engagement.providerName}
            </Typography>
          </Box>
        </Stack>
      </DialogTitle>

      <DialogContent dividers>
        <Stack spacing={3} sx={{ pt: 1 }}>
          {error && (
            <Alert severity="error" onClose={() => setError(null)}>
              {error}
            </Alert>
          )}

          {/* Output Type Selection */}
          <FormControl fullWidth>
            <InputLabel>Ergebnistyp</InputLabel>
            <Select
              value={selectedType}
              label="Ergebnistyp"
              onChange={(e) => setSelectedType(e.target.value)}
            >
              {OUTPUT_TYPES.map((type) => (
                <MenuItem key={type.value} value={type.value}>
                  <Stack>
                    <Typography variant="body1">{type.label}</Typography>
                    <Typography variant="caption" color="text.secondary">
                      {type.description}
                    </Typography>
                  </Stack>
                </MenuItem>
              ))}
            </Select>
          </FormControl>

          {/* Selected type info */}
          {selectedOutputType && (
            <Alert
              severity="info"
              icon={<Iconify icon="solar:info-circle-bold" />}
            >
              <Typography variant="body2">
                Dieses Ergebnis wird integriert in:{" "}
                <strong>
                  {selectedOutputType.integrationPoint.replace(".", " → ")}
                </strong>
              </Typography>
              <Stack direction="row" spacing={0.5} sx={{ mt: 1 }}>
                <Typography variant="caption" color="text.secondary">
                  Unterstützte Formate:
                </Typography>
                {selectedOutputType.formats.map((format) => (
                  <Chip
                    key={format}
                    size="small"
                    label={format.toUpperCase()}
                    variant="outlined"
                  />
                ))}
              </Stack>
            </Alert>
          )}

          {/* Summary */}
          <TextField
            label="Zusammenfassung"
            placeholder="Kurze Beschreibung des Ergebnisses..."
            multiline
            rows={2}
            value={summary}
            onChange={(e) => setSummary(e.target.value)}
            helperText="Optional: Eine kurze Zusammenfassung für den BGM-Manager"
          />

          {/* File URL */}
          <TextField
            label="Datei-URL"
            placeholder="https://example.com/result.pdf"
            value={fileUrl}
            onChange={(e) => setFileUrl(e.target.value)}
            helperText="Optional: Link zu einer PDF oder anderen Datei"
            InputProps={{
              startAdornment: (
                <Iconify
                  icon="solar:link-bold"
                  width={20}
                  sx={{ mr: 1, color: "text.secondary" }}
                />
              ),
            }}
          />

          {/* JSON Data */}
          <TextField
            label="Strukturierte Daten (JSON)"
            placeholder='{ "score": 85, "categories": [...] }'
            multiline
            rows={6}
            value={jsonData}
            onChange={(e) => setJsonData(e.target.value)}
            helperText="Optional: Strukturierte Daten im JSON-Format für automatische Integration"
            InputProps={{
              sx: { fontFamily: "monospace", fontSize: "0.875rem" },
            }}
          />

          {/* Already uploaded results */}
          {engagement.deliveredOutputs.length > 0 && (
            <Box>
              <Typography variant="subtitle2" sx={{ mb: 1 }}>
                Bereits hochgeladene Ergebnisse:
              </Typography>
              <Stack direction="row" spacing={0.5} flexWrap="wrap" useFlexGap>
                {engagement.deliveredOutputs.map((output) => (
                  <Chip
                    key={output}
                    size="small"
                    label={
                      OUTPUT_TYPES.find((t) => t.value === output)?.label ||
                      output
                    }
                    color="success"
                    variant="soft"
                    icon={<Iconify icon="solar:check-circle-bold" width={16} />}
                  />
                ))}
              </Stack>
            </Box>
          )}
        </Stack>
      </DialogContent>

      <DialogActions>
        <Button
          variant="outlined"
          onClick={handleClose}
          disabled={isSubmitting}
        >
          Abbrechen
        </Button>
        <Button
          variant="contained"
          onClick={handleSubmit}
          disabled={isSubmitting || !selectedType}
          startIcon={
            <Iconify
              icon={isSubmitting ? "solar:hourglass-bold" : "solar:upload-bold"}
            />
          }
        >
          {isSubmitting ? "Wird hochgeladen..." : "Hochladen"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

