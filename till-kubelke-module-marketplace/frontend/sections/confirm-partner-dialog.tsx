import type { IServiceProvider } from "src/types/marketplace";

import { useState } from "react";

import Box from "@mui/material/Box";
import Stack from "@mui/material/Stack";
import Alert from "@mui/material/Alert";
import Avatar from "@mui/material/Avatar";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import { alpha } from "@mui/material/styles";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import LoadingButton from "@mui/lab/LoadingButton";
import DialogTitle from "@mui/material/DialogTitle";
import DialogContent from "@mui/material/DialogContent";
import DialogActions from "@mui/material/DialogActions";

import { createEngagement } from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";

// ----------------------------------------------------------------------

interface Props {
  open: boolean;
  onClose: () => void;
  provider: IServiceProvider | null;
  onSuccess?: () => void;
}

export function ConfirmPartnerDialog({
  open,
  onClose,
  provider,
  onSuccess,
}: Props) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [notes, setNotes] = useState("");

  const handleConfirm = async () => {
    if (!provider) return;

    setIsSubmitting(true);
    setError(null);

    try {
      // Create engagement without specific offering (backend should handle)
      await createEngagement({
        providerId: provider.id,
        offeringId: 0, // Will use default/first offering in backend
        agreedPricing: undefined,
        scheduledDate: undefined,
      });

      setSuccess(true);
      onSuccess?.();

      setTimeout(() => {
        onClose();
        setSuccess(false);
        setNotes("");
      }, 1500);
    } catch (err: any) {
      setError(err.message || "Fehler beim Bestätigen der Partnerschaft");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      onClose();
      setError(null);
      setSuccess(false);
      setNotes("");
    }
  };

  if (!provider) return null;

  return (
    <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
      <DialogTitle>
        <Stack direction="row" alignItems="center" spacing={2}>
          <Avatar
            src={provider.logoUrl || undefined}
            sx={{
              width: 48,
              height: 48,
              bgcolor: (theme) => alpha(theme.palette.success.main, 0.12),
              color: "success.main",
            }}
          >
            {provider.companyName.charAt(0)}
          </Avatar>
          <Box>
            <Typography variant="h6">Partnerschaft bestätigen</Typography>
            <Typography variant="body2" color="text.secondary">
              {provider.companyName}
            </Typography>
          </Box>
        </Stack>
      </DialogTitle>

      <DialogContent dividers>
        {success ? (
          <Alert
            severity="success"
            icon={<Iconify icon="solar:check-circle-bold" />}
            sx={{ mb: 0 }}
          >
            <Typography variant="subtitle2">
              Partnerschaft bestätigt! 🎉
            </Typography>
            <Typography variant="body2">
              Sie können den Partner jetzt unter &quot;Meine Partner&quot;
              verwalten.
            </Typography>
          </Alert>
        ) : (
          <Stack spacing={3}>
            {error && (
              <Alert severity="error" onClose={() => setError(null)}>
                {error}
              </Alert>
            )}

            <Alert
              severity="info"
              icon={<Iconify icon="solar:info-circle-bold" />}
            >
              <Typography variant="body2">
                <strong>Ohne Anfrage bestätigen:</strong> Nutzen Sie diese
                Option, wenn Sie bereits eine Vereinbarung mit diesem Partner
                haben (z.B. durch direkte Kontaktaufnahme).
              </Typography>
            </Alert>

            <Box
              sx={{
                p: 2,
                borderRadius: 1.5,
                bgcolor: (theme) => alpha(theme.palette.success.main, 0.08),
                border: (theme) =>
                  `1px solid ${alpha(theme.palette.success.main, 0.24)}`,
              }}
            >
              <Stack direction="row" spacing={2} alignItems="center">
                <Iconify
                  icon="solar:handshake-bold"
                  width={32}
                  color="success.main"
                />
                <Box>
                  <Typography variant="subtitle2">
                    {provider.companyName} wird als Partner hinzugefügt
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    Der Partner erscheint in Ihrer Partnerliste und Sie können
                    Teilnahmen dokumentieren.
                  </Typography>
                </Box>
              </Stack>
            </Box>

            <TextField
              label="Interne Notizen (optional)"
              placeholder="z.B. Vereinbarung per E-Mail vom 15.12.2024"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              multiline
              rows={2}
              fullWidth
            />
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
            color="success"
            onClick={handleConfirm}
            loading={isSubmitting}
            startIcon={<Iconify icon="solar:check-circle-bold" />}
          >
            Partnerschaft bestätigen
          </LoadingButton>
        )}
      </DialogActions>
    </Dialog>
  );
}

