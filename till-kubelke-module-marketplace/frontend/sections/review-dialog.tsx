import type { ICreateReviewInput } from "src/types/marketplace";

import { useState } from "react";

import Box from "@mui/material/Box";
import Stack from "@mui/material/Stack";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import Rating from "@mui/material/Rating";
import Switch from "@mui/material/Switch";
import Divider from "@mui/material/Divider";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import LoadingButton from "@mui/lab/LoadingButton";
import DialogTitle from "@mui/material/DialogTitle";
import DialogContent from "@mui/material/DialogContent";
import DialogActions from "@mui/material/DialogActions";
import FormControlLabel from "@mui/material/FormControlLabel";

import { createReview } from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";

// ----------------------------------------------------------------------

interface Props {
  open: boolean;
  onClose: () => void;
  providerId: number;
  providerName: string;
  engagementId?: number;
  engagementTitle?: string;
  onSuccess?: () => void;
}

const RATING_LABELS: Record<number, string> = {
  1: "Sehr schlecht",
  2: "Schlecht",
  3: "Mittelmäßig",
  4: "Gut",
  5: "Ausgezeichnet",
};

export function ReviewDialog({
  open,
  onClose,
  providerId,
  providerName,
  engagementId,
  engagementTitle,
  onSuccess,
}: Props) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  // Rating states
  const [overallRating, setOverallRating] = useState<number | null>(null);
  const [communicationRating, setCommunicationRating] = useState<number | null>(
    null,
  );
  const [qualityRating, setQualityRating] = useState<number | null>(null);
  const [valueRating, setValueRating] = useState<number | null>(null);
  const [reliabilityRating, setReliabilityRating] = useState<number | null>(
    null,
  );

  // Text content
  const [title, setTitle] = useState("");
  const [comment, setComment] = useState("");
  const [pros, setPros] = useState("");
  const [cons, setCons] = useState("");

  // Meta
  const [wouldRecommend, setWouldRecommend] = useState(true);
  const [showCompanyName, setShowCompanyName] = useState(false);

  const handleSubmit = async () => {
    if (!overallRating) {
      setError("Bitte geben Sie eine Gesamtbewertung ab.");
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      const input: ICreateReviewInput = {
        providerId,
        engagementId,
        overallRating,
        communicationRating: communicationRating ?? undefined,
        qualityRating: qualityRating ?? undefined,
        valueRating: valueRating ?? undefined,
        reliabilityRating: reliabilityRating ?? undefined,
        title: title || undefined,
        comment: comment || undefined,
        pros: pros ? pros.split("\n").filter(Boolean) : undefined,
        cons: cons ? cons.split("\n").filter(Boolean) : undefined,
        wouldRecommend,
        showCompanyName,
      };

      await createReview(input);
      setSuccess(true);
      onSuccess?.();

      // Close after short delay
      setTimeout(() => {
        onClose();
      }, 1500);
    } catch (err: any) {
      setError(err.message || "Fehler beim Speichern der Bewertung");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      onClose();
    }
  };

  return (
    <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
      <DialogTitle>
        <Stack direction="row" alignItems="center" spacing={1}>
          <Iconify icon="solar:star-bold" width={24} color="warning.main" />
          <Box>
            <Typography variant="h6">Bewertung abgeben</Typography>
            <Typography variant="body2" color="text.secondary">
              {providerName}
              {engagementTitle && ` – ${engagementTitle}`}
            </Typography>
          </Box>
        </Stack>
      </DialogTitle>

      <DialogContent dividers>
        {success ? (
          <Alert
            severity="success"
            icon={<Iconify icon="solar:check-circle-bold" />}
          >
            Vielen Dank für Ihre Bewertung! Sie hilft anderen Unternehmen bei
            der Auswahl.
          </Alert>
        ) : (
          <Stack spacing={3}>
            {error && (
              <Alert severity="error" onClose={() => setError(null)}>
                {error}
              </Alert>
            )}

            {/* Overall Rating */}
            <Box>
              <Typography variant="subtitle2" gutterBottom>
                Gesamtbewertung *
              </Typography>
              <Stack direction="row" spacing={2} alignItems="center">
                <Rating
                  value={overallRating}
                  onChange={(_, value) => setOverallRating(value)}
                  size="large"
                  sx={{ fontSize: "2rem" }}
                />
                {overallRating && (
                  <Typography variant="body2" color="text.secondary">
                    {RATING_LABELS[overallRating]}
                  </Typography>
                )}
              </Stack>
            </Box>

            <Divider />

            {/* Sub-ratings */}
            <Box>
              <Typography variant="subtitle2" gutterBottom>
                Detailbewertungen (optional)
              </Typography>
              <Stack spacing={2}>
                <Stack
                  direction="row"
                  justifyContent="space-between"
                  alignItems="center"
                >
                  <Typography variant="body2">Kommunikation</Typography>
                  <Rating
                    value={communicationRating}
                    onChange={(_, value) => setCommunicationRating(value)}
                    size="small"
                  />
                </Stack>
                <Stack
                  direction="row"
                  justifyContent="space-between"
                  alignItems="center"
                >
                  <Typography variant="body2">Qualität</Typography>
                  <Rating
                    value={qualityRating}
                    onChange={(_, value) => setQualityRating(value)}
                    size="small"
                  />
                </Stack>
                <Stack
                  direction="row"
                  justifyContent="space-between"
                  alignItems="center"
                >
                  <Typography variant="body2">Preis-Leistung</Typography>
                  <Rating
                    value={valueRating}
                    onChange={(_, value) => setValueRating(value)}
                    size="small"
                  />
                </Stack>
                <Stack
                  direction="row"
                  justifyContent="space-between"
                  alignItems="center"
                >
                  <Typography variant="body2">Zuverlässigkeit</Typography>
                  <Rating
                    value={reliabilityRating}
                    onChange={(_, value) => setReliabilityRating(value)}
                    size="small"
                  />
                </Stack>
              </Stack>
            </Box>

            <Divider />

            {/* Text Content */}
            <TextField
              label="Titel (optional)"
              placeholder="Zusammenfassung Ihrer Erfahrung"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              fullWidth
            />

            <TextField
              label="Ihre Bewertung (optional)"
              placeholder="Beschreiben Sie Ihre Erfahrung mit diesem Anbieter..."
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              multiline
              rows={4}
              fullWidth
            />

            <Stack direction={{ xs: "column", sm: "row" }} spacing={2}>
              <TextField
                label="Vorteile"
                placeholder="Ein Vorteil pro Zeile"
                value={pros}
                onChange={(e) => setPros(e.target.value)}
                multiline
                rows={3}
                fullWidth
                helperText="Pro Zeile ein Punkt"
              />
              <TextField
                label="Nachteile"
                placeholder="Ein Nachteil pro Zeile"
                value={cons}
                onChange={(e) => setCons(e.target.value)}
                multiline
                rows={3}
                fullWidth
                helperText="Pro Zeile ein Punkt"
              />
            </Stack>

            <Divider />

            {/* Toggles */}
            <Stack spacing={1}>
              <FormControlLabel
                control={
                  <Switch
                    checked={wouldRecommend}
                    onChange={(e) => setWouldRecommend(e.target.checked)}
                  />
                }
                label={
                  <Stack direction="row" alignItems="center" spacing={1}>
                    <Iconify
                      icon={
                        wouldRecommend
                          ? "solar:like-bold"
                          : "solar:dislike-bold"
                      }
                      width={20}
                      color={wouldRecommend ? "success.main" : "error.main"}
                    />
                    <Typography variant="body2">
                      Ich würde diesen Anbieter weiterempfehlen
                    </Typography>
                  </Stack>
                }
              />
              <FormControlLabel
                control={
                  <Switch
                    checked={showCompanyName}
                    onChange={(e) => setShowCompanyName(e.target.checked)}
                  />
                }
                label={
                  <Typography variant="body2">
                    Unternehmensname öffentlich anzeigen (sonst:
                    &quot;Verifizierter Kunde&quot;)
                  </Typography>
                }
              />
            </Stack>
          </Stack>
        )}
      </DialogContent>

      <DialogActions>
        <Button onClick={handleClose} disabled={isSubmitting}>
          {success ? "Schließen" : "Abbrechen"}
        </Button>
        {!success && (
          <LoadingButton
            variant="contained"
            onClick={handleSubmit}
            loading={isSubmitting}
            disabled={!overallRating}
            startIcon={<Iconify icon="solar:star-bold" />}
          >
            Bewertung abgeben
          </LoadingButton>
        )}
      </DialogActions>
    </Dialog>
  );
}

